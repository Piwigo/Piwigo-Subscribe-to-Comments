<?php
if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

include_once(PHPWG_ROOT_PATH.'include/functions_mail.inc.php');

/**
 * Send comment to subscribers
 * @param: array comment (author, content, image_id|category_id)
 */
function send_comment_to_subscribers($comm)
{
  if ( empty($comm) or !is_array($comm) )
  {
    trigger_error('send_comment_to_subscribers: undefineded comm', E_USER_WARNING);
    return false;
  }
  
  global $conf, $page, $user, $template;
  
  // create search clauses
  $where_clauses = array();
  if (isset($comm['image_id']))
  {
    $element_id = $comm['image_id'];
    $element_type = 'image';
    
    array_push($where_clauses, 'type = "image" AND element_id = '.$element_id.'');
    if (!empty($page['category']['id'])) array_push($where_clauses, 'type = "album-images" AND element_id = '.$page['category']['id'].'');
    array_push($where_clauses, 'type = "all-images"');
  }
  else if (isset($comm['category_id']))
  {
    $element_id = $comm['category_id'];
    $element_type = 'category';
    
    array_push($where_clauses, 'type = "album" AND element_id = '.$element_id.'');
    array_push($where_clauses, 'type = "all-albums"');
  }
  else
  {
    return;
  }
  
  // exclude current user
  $exclude = null;
  if (!empty($_POST['stc_mail']))
  {
    $exclude = pwg_db_real_escape_string($_POST['stc_mail']);
  }
  else if (!is_a_guest())
  {
    $exclude = $user['email'];
  }
  
  // get subscribers datas
  $query = '
SELECT
    id,
    email,
    language
  FROM '.SUBSCRIBE_TO_TABLE.'
  WHERE (
      ('.implode(")\n      OR (", $where_clauses).')
    )
    AND validated = true
    AND email != "'.$exclude.'"
';
  $subscriptions = hash_from_query($query, 'email');
  
  set_make_full_url();
  
  // get element infos
  if ($element_type == 'image')
  {
    $element = get_picture_infos($comm['image_id']);
  }
  else
  {
    $element = get_category_infos($comm['category_id']);
  }
  
  // format comment
  if ($comm['author'] == 'guest') $comm['author'] = l10n('guest');
  $comm['author'] = trigger_event('render_comment_author', $comm['author']);
  $comm['content'] = trigger_event('render_comment_content', $comm['content']);
  
  // mail content
  $subject = '['.strip_tags($conf['gallery_title']).'] Re:'.$element['name'];
    
  $template->set_filename('stc_mail', dirname(__FILE__).'/../template/mail/notification.tpl');

  foreach ($subscriptions as $row)
  {
    // get subscriber id
    if ( ($uid = get_userid_by_email($row['email'])) !== false )
    {
      $row['user_id'] = $uid;
    }
    else
    {
      $row['user_id'] = $conf['guest_id'];
    }
    
    // check permissions
    if (!user_can_view_element($row['user_id'], $element_id, $element_type))
    {
      continue;
    }
    
    // send mail
    switch_lang_to($row['language']);
    load_language('plugin.lang', SUBSCRIBE_TO_PATH);
    
    $comm['caption'] = sprintf('<b>%s</b> wrote on <i>%s</i>', $comm['author'], format_date(date('Y-d-m H:i:s')));
    
    $template->assign('STC', array(
      'element' => $element,
      'comment' => $comm,
      'UNSUB_URL' => make_stc_url('unsubscribe', $row['email'], $row['id']),
      'MANAGE_URL' => make_stc_url('manage', $row['email']),
      'GALLERY_TITLE' => $conf['gallery_title'],
      ));
    
    $content = $template->parse('stc_mail', true);

    stc_send_mail($row['email'], $content, $subject);
    switch_lang_back();
  }
  
  load_language('plugin.lang', SUBSCRIBE_TO_PATH);
  unset_make_full_url();
}


/**
 * add an email to subscribers list
 * @param: string email
 * @param: string type (image|album-images|all-images|album|all-albums)
 * @param: int element_id
 * @return: bool
 */
function subscribe_to_comments($email, $type, $element_id='NULL')
{
  if (empty($type))
  {
    trigger_error('subscribe_to_comment: missing type', E_USER_WARNING);
    return false;
  }
  
  if ( !in_array($type, array('all-images','all-albums')) and $element_id == 'NULL' )
  {
    trigger_error('subscribe_to_comment: missing element_id', E_USER_WARNING);
    return false;
  }
  
  global $page, $conf, $user, $template, $picture;
  
  // check email
  if ( !empty($email) and !is_valid_email($email) )
  {
    array_push($page['errors'], l10n('mail address must be like xxx@yyy.eee (example : jack@altern.org)'));
    return false;
  }
  if ( ( is_a_guest() or empty($user['email']) ) and empty($email) )
  {
    array_push($page['errors'], l10n('Invalid email adress, your are not subscribed to comments.'));
    return false;
  }
  else if ( !is_a_guest() and empty($email) )
  {
    $email = $user['email'];
  }
  
  // search if already registered (can use ODKU because we want to get the id of inserted OR updated row)
  $query = '
SELECT id
  FROM '.SUBSCRIBE_TO_TABLE.'
  WHERE
    type = "'.$type.'"
    AND element_id = '.$element_id.'
    AND email = "'.pwg_db_real_escape_string($email).'"
;';
  $result = pwg_query($query);
  
  if (pwg_db_num_rows($result))
  {
    list($inserted_id) = pwg_db_fetch_row($result);
  }
  else
  {
    $query = '
INSERT INTO '.SUBSCRIBE_TO_TABLE.'(
    type,
    element_id,
    language,
    email,
    registration_date,
    validated
  )
  VALUES(
    "'.$type.'",
    '.$element_id.',
    "'.$user['language'].'",
    "'.pwg_db_real_escape_string($email).'",
    NOW(),
    "'.(is_a_guest() ? "false" : "true").'"
  )
;';
    pwg_query($query);
    
    $inserted_id = pwg_db_insert_id();
  }
  
  // notify admins
  if ( pwg_db_changes(null) != 0 and $conf['Subscribe_to_Comments']['notify_admin_on_subscribe'] )
  {
    stc_mail_notification_admins($email, $type, $element_id, $inserted_id);
  }
  
  // send validation mail
  if ( is_a_guest() and pwg_db_changes(null) != 0 )
  {
    set_make_full_url();
    
    $template->set_filename('stc_mail', dirname(__FILE__).'/../template/mail/confirm.tpl');
    
    $subject = '['.strip_tags($conf['gallery_title']).'] '.l10n('Confirm your subscribtion to comments');
      
    switch ($type)
    {
      case 'image':
        $element = get_picture_infos($element_id);
        $element['on'] = sprintf(l10n('the picture <a href="%s">%s</a>'), $element['url'], $element['name']);
        break;
      case 'album-images':
        $element = get_category_infos($element_id);
        $element['on'] = sprintf(l10n('all pictures of the album <a href="%s">%s</a>'), $element['url'], $element['name']);
        break;
      case 'all-images':
        $element['thumbnail'] = null;
        $element['on'] = l10n('all pictures of the gallery');
        break;
      case 'album':
        $element = get_category_infos($element_id);
        $element['on'] = sprintf(l10n('the album <a href="%s">%s</a>'), $element['url'], $element['name']);
        break;
      case 'all-albums':
        $element['thumbnail'] = null;
        $element['on'] = l10n('all albums of the gallery');
        break;
    }
    
    $template->assign('STC', array(
      'element' => $element,
      'VALIDATE_URL' => make_stc_url('validate', $email, $inserted_id),
      'MANAGE_URL' => make_stc_url('manage', $email),
      'GALLERY_TITLE' => $conf['gallery_title'],
      ));
    
    $content = $template->parse('stc_mail', true);

    stc_send_mail($email, $content, $subject);
    unset_make_full_url();
    
    array_push($page['infos'], l10n('Please check your email inbox to confirm your subscription.'));
    return true;
  }
  // just display confirmation message
  else if (pwg_db_changes(null) != 0)
  {
    array_push($page['infos'], l10n('You have been added to the list of subscribers.'));
    return true;
  }
  
  return false;
}


/**
 * remove an email from subscribers list
 * @param: string email
 * @param: int subscription id
 * @return: bool
 */
function un_subscribe_to_comments($email, $id)
{  
  if (empty($id))
  {
    trigger_error('un_subscribe_to_comment: missing id', E_USER_WARNING);
    return false;
  }
  
  global $template, $user;
  
  // check email
  if ( ( is_a_guest() or empty($user['email']) ) and empty($email) )
  {
    return false;
  }
  else if ( !is_a_guest() and empty($email) )
  {
    $email = $user['email'];
  }
  
  // delete subscription
  $query = '
DELETE FROM '.SUBSCRIBE_TO_TABLE.'
  WHERE 
    email = "'.pwg_db_real_escape_string($email).'"
    AND id = "'.pwg_db_real_escape_string($id).'"
;';
  pwg_query($query);
      
  if (pwg_db_changes(null) != 0) return true;
  return false;
}


/**
 * validate a subscription
 * @param: string email
 * @param: int subscription id
 * @return: bool
 */
function validate_subscriptions($email, $id)
{
  if (empty($email))
  {
    trigger_error('validate_subscriptions: missing email', E_USER_WARNING);
    return false;
  }
  
  if (empty($id))
  {
    trigger_error('validate_subscriptions: missing id', E_USER_WARNING);
    return false;
  }
  
  $query = '
UPDATE '.SUBSCRIBE_TO_TABLE.'
  SET validated = "true"
  WHERE 
    email = "'.pwg_db_real_escape_string($email).'"
    AND id = '.pwg_db_real_escape_string($id).'
;';
  pwg_query($query);
      
  if (pwg_db_changes(null) != 0) return true;
  return false;
}


/**
 * send notification to admins
 * @param: string email
 * @param: string type (image|album-images|all-images|album|all-albums)
 * @param: int element_id
 * @param: int subscription id
 */
function stc_mail_notification_admins($email, $type, $element_id, $inserted_id)
{
  global $user, $conf, $template;
  
  $admins = get_admins_email();
  if (empty($admins)) return;
  
  set_make_full_url();
  switch_lang_to(get_default_language());
  load_language('plugin.lang', SUBSCRIBE_TO_PATH);
  
  $template->set_filename('stc_mail', dirname(__FILE__).'/../template/mail/admin.tpl');
    
  $subject = '['.strip_tags($conf['gallery_title']).'] '.sprintf(l10n('%s has subscribed to comments on'), is_a_guest()?$email:$user['username']);
    
  switch ($type)
  {
    case 'image':
      $element = get_picture_infos($element_id, false);
      $element['on'] = sprintf(l10n('the picture <a href="%s">%s</a>'), $element['url'], $element['name']);
      break;
    case 'album-images':
      $element = get_category_infos($element_id, false);
      $element['on'] = sprintf(l10n('all pictures of the album <a href="%s">%s</a>'), $element['url'], $element['name']);
      break;
    case 'all-images':
      $element['on'] = l10n('all pictures of the gallery');
      break;
    case 'album':
      $element = get_category_infos($element_id, false);
      $element['on'] = sprintf(l10n('the album <a href="%s">%s</a>'), $element['url'], $element['name']);
      break;
    case 'all-albums':
      $element['on'] = l10n('all albums of the gallery');
      break;
  }
  
  $technical_infos[] = sprintf(l10n('Connected user: %s'), stripslashes($user['username']));
  $technical_infos[] = sprintf(l10n('IP: %s'), $_SERVER['REMOTE_ADDR']);
  $technical_infos[] = sprintf(l10n('Browser: %s'), $_SERVER['HTTP_USER_AGENT']);
  
  $template->assign('STC', array(
    'ELEMENT' => $element['on'],
    'USER' => sprintf(l10n('%s has subscribed to comments on'), is_a_guest() ? '<b>'.$email.'</b>' : '<b>'.$user['username'].'</b> ('.$email.')'), 
    'GALLERY_TITLE' => $conf['gallery_title'],
    'TECHNICAL' => implode('<br>', $technical_infos),
    ));
  
  $content = $template->parse('stc_mail', true);

  stc_send_mail($admins, $content, $subject);
  
  unset_make_full_url();
  switch_lang_back();
  load_language('plugin.lang', SUBSCRIBE_TO_PATH);
}


/**
 * create absolute url to subscriptions section
 * @param: string action
 * @param: string email
 * @param: int optional
 * @return: string
 */
function make_stc_url($action, $email, $id=null)
{
  if ( empty($action) or empty($email) )
  {
    trigger_error('make_stc_url: missing action and/or mail', E_USER_WARNING);
    return null;
  }
  
  global $conf;
  set_make_full_url();
  
  $url_params = array(
    'action' => $action,
    'email' => $email,
    );
  
  if (!empty($id))
  {
    $url_params['id'] = $id;
  }
  
  $url_params['key'] = crypt_value(
    $action.$email.(isset($url_params['id'])?$url_params['id']:null), 
    $conf['secret_key']
    );
  
  $url = add_url_params(
    make_index_url( array('section' => 'subscriptions') ),
    $url_params
    );
    
  unset_make_full_url();
  return $url;
}


/**
 * send mail with STC style
 * @param: string to
 * @param: string content
 * @param: string subject
 * @return: bool
 */
function stc_send_mail($to, $content, $subject)
{
  global $conf, $conf_mail, $page, $template;
  
  // inputs
  if (empty($to))
  {
    return false;
  }

  if (empty($content))
  {
    return false;
  }
  
  if (empty($subject))
  {
    $subject = 'Piwigo';
  }
  else
  {
    $subject = trim(preg_replace('#[\n\r]+#s', '', $subject));
    $subject = encode_mime_header($subject);
  }
  
  if (!isset($conf_mail))
  {
    $conf_mail = get_mail_configuration();
  }

  $args['from'] = $conf_mail['formated_email_webmaster'];
  
  set_make_full_url();
  
  // hearders
  $headers = 'From: '.$args['from']."\n";  
  $headers.= 'MIME-Version: 1.0'."\n";
  $headers.= 'X-Mailer: Piwigo Mailer'."\n";
  // $headers.= 'Content-Transfer-Encoding: Quoted-Printable'."\n";
  $headers.= 'Content-Transfer-Encoding: 8bit'."\n";
  $headers.= 'Content-Type: text/html; charset="'.get_pwg_charset().'";'."\n";
  
  // template
  $template->set_filenames(array(
    'stc_mail_header' => dirname(__FILE__).'/../template/mail/header.tpl',
    'stc_mail_footer' => dirname(__FILE__).'/../template/mail/footer.tpl',
    ));
  $stc_mail_css = file_get_contents(dirname(__FILE__).'/../template/mail/style.css');
    
  $template->assign(array(
    'GALLERY_URL' => get_gallery_home_url(),
    'PHPWG_URL' => PHPWG_URL,
    'STC_MAIL_CSS' => str_replace("\n", null, $stc_mail_css),
    ));
  
  $content = $template->parse('stc_mail_header', true) . $content . $template->parse('stc_mail_footer', true);
  
  // $content = quoted_printable_encode($content);
  $content = wordwrap($content, 70, "\n", true);

  unset_make_full_url();
  
  // send mail
  return
    trigger_event('send_mail',
      false, /* Result */
      trigger_event('send_mail_to', get_strict_email_list($to)),
      trigger_event('send_mail_subject', $subject),
      trigger_event('send_mail_content', $content),
      trigger_event('send_mail_headers', $headers),
      $args
    );
}


/**
 * get name, url and thumbnail of a picture
 * @param: int image_id
 * @param: bool return thumbnail
 * @return: array (id, name, url, thumbnail)
 */
function get_picture_infos($image_id, $with_thumb=true)
{
  $query = '
SELECT
    id,
    file,
    name,
    path
  FROM '.IMAGES_TABLE.'
  WHERE id = '.$image_id.'
;';
  $element = pwg_db_fetch_assoc(pwg_query($query));
    
  if (empty($element['name']))
  {
    $element['name'] = get_name_from_file($element['file']);
  }
  
  $url_params = array('image_id' => $element['id']);
  $element['url'] = make_picture_url($url_params);
  
  if ($with_thumb)
  {
    $element['thumbnail'] = DerivativeImage::thumb_url($element);
  }
  
  return $element;
}

/**
 * get name, url and thumbnail of a category
 * @param: int cat_id
 * @param: int return thumbnail
 * @return: array (id, name, url, thumbnail)
 */
function get_category_infos($cat_id, $with_thumb=true)
{
  global $conf;
  
  $query = '
SELECT
    cat.id,
    cat.name,
    cat.permalink,
    img.id AS image_id,
    img.path
  FROM '.CATEGORIES_TABLE.' AS cat
    LEFT JOIN '.USER_CACHE_CATEGORIES_TABLE.' AS ucc 
      ON ucc.cat_id = cat.id AND ucc.user_id = '.$conf['guest_id'].'
    LEFT JOIN '.IMAGES_TABLE.' AS img
      ON img.id = ucc.user_representative_picture_id
  WHERE cat.id = '.$cat_id.'
;';
  $element = pwg_db_fetch_assoc(pwg_query($query));
  // we use guest_id for user_cache because we don't know the status of recipient
  
  $element['url'] = make_index_url(array(
    'section'=>'categories',
    'category'=>$element,
    ));
  
  if ($with_thumb)
  {
    $element['thumbnail'] = DerivativeImage::thumb_url(array(
      'id'=>$element['image_id'],
      'path'=>$element['path'],
      ));
  }
  
  return $element;
}

/**
 * get list of admins email
 * @return: string
 */
function get_admins_email()
{
  global $conf, $user;
  
  $admins = array();
  
  $query = '
SELECT
    u.'.$conf['user_fields']['username'].' AS username,
    u.'.$conf['user_fields']['email'].' AS email
  FROM '.USERS_TABLE.' AS u
    JOIN '.USER_INFOS_TABLE.' AS i 
      ON i.user_id =  u.'.$conf['user_fields']['id'].'
  WHERE i.status IN ("webmaster", "admin")
    AND '.$conf['user_fields']['email'].' IS NOT NULL
    AND i.user_id != '.$user['id'].'
  ORDER BY username
;';

  $datas = pwg_query($query);
  if (!empty($datas))
  {
    while ($admin = pwg_db_fetch_assoc($datas))
    {
      array_push($admins, format_email($admin['username'], $admin['email']));
    }
  }

  return implode(',', $admins);
}


/**
 * check if the given user can view the category/image
 * @param: int user_id
 * @param: int element_id
 * @param: string type (image|category)
 * @return: bool
 */
function user_can_view_element($user_id, $element_id, $type)
{
  global $conf;
  
  $old_conf = $conf['external_authentification'];
  $conf['external_authentification'] = false;
  $user = getuserdata($user_id, true);
  $conf['external_authentification'] = $old_conf;
  
  if ($type == 'image')
  {
    return !in_array($element_id, explode(',', $user['image_access_list']));
  }
  else if ($type == 'category')
  {
    return !in_array($element_id, explode(',', $user['forbidden_categories']));
  }
  else
  {
    return false;
  }
}


/**
 * check if mail adress is valid
 * @param: string email
 * @return: bool
 */
if (!function_exists('is_valid_email'))
{
  function is_valid_email($mail_address)
  {
    if (version_compare(PHP_VERSION, '5.2.0') >= 0)
    {
      return filter_var($mail_address, FILTER_VALIDATE_EMAIL)!==false;
    }
    else
    {
      $atom   = '[-a-z0-9!#$%&\'*+\\/=?^_`{|}~]';   // before  arobase
      $domain = '([a-z0-9]([-a-z0-9]*[a-z0-9]+)?)'; // domain name
      $regex = '/^' . $atom . '+' . '(\.' . $atom . '+)*' . '@' . '(' . $domain . '{1,63}\.)+' . $domain . '{2,63}$/i';

      if (!preg_match($regex, $mail_address)) return false;
      return true;
    }
  }
}


/**
 * crypt a string using mcrypt extension or
 * http://stackoverflow.com/questions/800922/how-to-encrypt-string-without-mcrypt-library-in-php/802957#802957
 * @param: string value to crypt
 * @param: string key
 * @return: string
 */
function crypt_value($value, $key)
{  
  if (extension_loaded('mcrypt'))
  {
    $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
    $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
    $result = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $value, MCRYPT_MODE_ECB, $iv);
  }
  else
  {
    $result = null;
    for($i = 0; $i < strlen($value); $i++)
    {
      $char = substr($value, $i, 1);
      $keychar = substr($key, ($i % strlen($key))-1, 1);
      $char = chr(ord($char) + ord($keychar));
      $result .= $char;
    }
  }
  
  $result = base64url_encode($result);
  return trim($result); 
}

/**
 * decrypt a string crypted with previous function
 * @param: string value to decrypt
 * @param: string key
 * @return: string
 */
function decrypt_value($value, $key)
{
  $value = base64url_decode($value); 
  
  if (extension_loaded('mcrypt'))
  {
    $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
    $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
    $result = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $key, $value, MCRYPT_MODE_ECB, $iv);
  }
  else
  {
    $result = null;
    for($i = 0; $i < strlen($value); $i++)
    {
      $char = substr($value, $i, 1);
      $keychar = substr($key, ($i % strlen($key))-1, 1);
      $char = chr(ord($char) - ord($keychar));
      $result .= $char;
    }
  }
  
  return trim($result);
}

/**
 * variant of base64 functions usable into url
 * http://php.net/manual/en/function.base64-encode.php#103849
 */
function base64url_encode($data)
{
  return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}
function base64url_decode($data)
{
  return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
} 

?>