<?php
defined('SUBSCRIBE_TO_PATH') or die('Hacking attempt!');

/**
 * Send comment to subscribers
 * @param: array comment (author, content, image_id|category_id)
 */
function send_comment_to_subscribers($comm)
{
  if (empty($comm) or !is_array($comm))
  {
    trigger_error('send_comment_to_subscribers: undefined comm', E_USER_WARNING);
    return false;
  }

  global $conf, $page, $user, $template;

  // create search clauses
  $where_clauses = array();
  if (isset($comm['image_id']))
  {
    $element_id = $comm['image_id'];
    $element_type = 'image';

    $where_clauses[] = '(type = "image" AND element_id = '.$element_id.')';
    $where_clauses[] = 'type = "all-images"';
    if (!empty($page['category']['id']))
    {
      $where_clauses[] = '(type = "album-images" AND element_id = '.$page['category']['id'].')';
    }
  }
  else if (isset($comm['category_id']))
  {
    $element_id = $comm['category_id'];
    $element_type = 'category';

    $where_clauses[] = '(type = "album" AND element_id = '.$element_id.')';
    $where_clauses[] = 'type = "all-albums"';
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
      '.implode("\n      OR ", $where_clauses).'
    )
    AND validated = true
    AND email != "'.$exclude.'"
  GROUP BY email
';
  $subscriptions = query2array($query);

  if (count($subscriptions)==0)
  {
    return;
  }

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
  if ($comm['author'] == 'guest')
  {
    $comm['author'] = l10n('guest');
  }

  $comm['author'] = trigger_event('render_comment_author', $comm['author']);
  $comm['content'] = trigger_event('render_comment_content', $comm['content']);

  include_once(PHPWG_ROOT_PATH.'include/functions_mail.inc.php');

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

    switch_lang_to($language);

    $comm['date'] = format_date(date('Y-m-d H:i:s'));

    pwg_mail(
      $row['email'],
      array(
        'subject' => '['.strip_tags($conf['gallery_title']).'] '.l10n('New comment on %s', $element['name']),
        ),
      array(
        'filename' => 'notification',
        'dirname' => SUBSCRIBE_TO_PATH . 'template',
        'assign' => array(
          'ELEMENT' => $element,
          'COMMENT' => $comm,
          'UNSUB_URL' => make_stc_url('unsubscribe', $row['email'], $row['id']),
          'MANAGE_URL' => make_stc_url('manage', $row['email']),
          ),
        )
      );

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

  if (!in_array($type, array('all-images','all-albums')) and $element_id == 'NULL')
  {
    trigger_error('subscribe_to_comment: missing element_id', E_USER_WARNING);
    return false;
  }

  global $page, $conf, $user, $template, $picture;

  // check email
  if (!empty($email) and !email_check_format($email))
  {
    $page['errors'][] = l10n('mail address must be like xxx@yyy.eee (example : jack@altern.org)');
    return false;
  }
  if ( (is_a_guest() or empty($user['email'])) and empty($email) )
  {
    $page['errors'][] = l10n('Invalid email address, your are not subscribed to comments.');
    return false;
  }
  else if (!is_a_guest() and empty($email))
  {
    $email = $user['email'];
  }

  // search if already registered
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
    return false;
  }

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

  $stc_id = pwg_db_insert_id();

  include_once(PHPWG_ROOT_PATH.'include/functions_mail.inc.php');

  set_make_full_url();

  if (!is_a_guest() or $conf['Subscribe_to_Comments']['notify_admin_on_subscribe'])
  {
    switch ($type)
    {
      case 'image':
        $element = get_picture_infos($element_id);
        $element['on'] = l10n('the picture <a href="%s">%s</a>', $element['url'], $element['name']);
        break;
      case 'album-images':
        $element = get_category_infos($element_id);
        $element['on'] = l10n('all pictures of the album <a href="%s">%s</a>', $element['url'], $element['name']);
        break;
      case 'all-images':
        $element['thumbnail'] = null;
        $element['on'] = l10n('all pictures of the gallery');
        break;
      case 'album':
        $element = get_category_infos($element_id);
        $element['on'] = l10n('the album <a href="%s">%s</a>', $element['url'], $element['name']);
        break;
      case 'all-albums':
        $element['thumbnail'] = null;
        $element['on'] = l10n('all albums of the gallery');
        break;
    }
  }

  // send validation mail
  if (is_a_guest())
  {
    pwg_mail(
      $email,
      array(
        'subject' => '['.strip_tags($conf['gallery_title']).'] '.l10n('Confirm your subscription to comments'),
        ),
      array(
        'filename' => 'confirm',
        'dirname' => SUBSCRIBE_TO_PATH . 'template',
        'assign' => array(
          'ELEMENT' => $element,
          'VALIDATE_URL' => make_stc_url('validate', $email, $stc_id),
          'MANAGE_URL' => make_stc_url('manage', $email),
          ),
        )
      );

    $page['infos'][] = l10n('Please check your email in-box to confirm your subscription.');
  }
  // just display confirmation message
  else
  {
    $page['infos'][] = l10n('You have been added to the list of subscribers.');
  }

  // notify admins
  if ($conf['Subscribe_to_Comments']['notify_admin_on_subscribe'])
  {
    pwg_mail_notification_admins(
      get_l10n_args('New subscription on %s', strip_tags($element['on'])),
      array(
        get_l10n_args('%s has subscribed to comments on %s.', array($email, $element['on'])),
        )
      );
  }

  unset_make_full_url();

  return true;
}


/**
 * remove an email from subscribers list
 * @param: string email
 * @param: int subscription id
 * @return: bool
 */
function un_subscribe_to_comments($email, $ids)
{
  if (!empty($email) and !email_check_format($email))
  {
    trigger_error('un_subscribe_to_comment: bad email', E_USER_WARNING);
    return false;
  }
  if (empty($ids))
  {
    trigger_error('un_subscribe_to_comment: bad id', E_USER_WARNING);
    return false;
  }

  global $user;

  // check email
  if ( (is_a_guest() or empty($user['email'])) and empty($email) )
  {
    return false;
  }
  else if (!is_a_guest() and empty($email))
  {
    $email = $user['email'];
  }

  if (!is_array($ids))
  {
    $ids = array($ids);
  }
  $ids = array_map('intval', $ids);

  // delete subscription
  $query = '
DELETE FROM '.SUBSCRIBE_TO_TABLE.'
  WHERE
    email = "'.pwg_db_real_escape_string($email).'"
    AND id IN('. implode(',', $ids) .')
;';
  pwg_query($query);

  return (pwg_db_changes() != 0);
}


/**
 * validate a subscription
 * @param: string email
 * @param: int subscription id
 * @return: bool
 */
function validate_subscriptions($email, $ids)
{
  if (!email_check_format($email))
  {
    trigger_error('validate_subscriptions: bad email', E_USER_WARNING);
    return false;
  }
  if (empty($ids))
  {
    trigger_error('validate_subscriptions: bad id', E_USER_WARNING);
    return false;
  }

  if (!is_array($ids))
  {
    $ids = array($ids);
  }
  $ids = array_map('intval', $ids);

  $query = '
UPDATE '.SUBSCRIBE_TO_TABLE.'
  SET validated = "true"
  WHERE
    email = "'.pwg_db_real_escape_string($email).'"
    AND id IN('. implode(',', $ids) .')
;';
  pwg_query($query);

  return (pwg_db_changes() != 0);
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
  if (empty($action) or empty($email))
  {
    trigger_error('make_stc_url: missing action and/or mail', E_USER_WARNING);
    return null;
  }

  global $conf;
  set_make_full_url();

  $url_params = compact('action', 'email');
  if (!empty($id))
  {
    $url_params['id'] = $id;
  }

  $url_params['key'] = crypt_value(
    $action.$email.$id,
    $conf['secret_key']
    );

  $url = add_url_params(
    make_index_url(array('section' => 'subscriptions')),
    $url_params
    );

  unset_make_full_url();
  return $url;
}


/**
 * get name, url and thumbnail of a picture
 * @param: int image_id
 * @param: bool return thumbnail
 * @return: array (id, name, url, thumbnail)
 */
function get_picture_infos($image_id, $with_thumb=true)
{
  if (empty($image_id))
  {
    return array();
  }

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
  $element['name'] = trigger_event('render_element_name', $element['name']);

  $element['url'] = make_picture_url(array(
    'image_id'=>$element['id']
    ));

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
function get_category_infos($cat_id, $with_thumb=true, $user_id=null)
{
  global $conf;

  if ($user_id===null)
  {
    $user_id = $conf['guest_id'];
  }

  $query = '
SELECT
    cat.id,
    cat.name,
    cat.permalink,
    ucc.count_images,
    cat.uppercats,
    img.id AS image_id,
    img.path
  FROM '.CATEGORIES_TABLE.' AS cat
    LEFT JOIN '.USER_CACHE_CATEGORIES_TABLE.' AS ucc
      ON ucc.cat_id = cat.id AND ucc.user_id = '.$user_id.'
    LEFT JOIN '.IMAGES_TABLE.' AS img
      ON img.id = ucc.user_representative_picture_id
  WHERE cat.id = '.$cat_id.'
;';
  $element = pwg_db_fetch_assoc(pwg_query($query));

  $element['url'] = make_index_url(array(
    'section'=>'categories',
    'category'=>$element,
    ));

  $element['name'] = trigger_event('render_category_name', $element['name']);

  if ($with_thumb)
  {
    if (empty($element['image_id']) and $conf['allow_random_representative'])
    {
      $image = get_picture_infos(get_random_image_in_category($element));
      $element['thumbnail'] = $image['thumbnail'];
    }
    else
    {
      $element['thumbnail'] = DerivativeImage::thumb_url(array(
        'id'=>$element['image_id'],
        'path'=>$element['path'],
        ));
    }
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
if (!function_exists('base64url_encode'))
{
  function base64url_encode($data)
  {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
  }
  function base64url_decode($data)
  {
    return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
  }
}
