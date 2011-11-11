<?php
if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

/**
 * Send comment to subscribers
 * @param array comm
 */
function send_comment_to_subscribers($comm)
{
  global $conf, $page, $user;
  
  if ( empty($comm) or !is_array($comm) )
  {
    trigger_error('send_comment_to_subscribers: undefinided comm', E_USER_WARNING);
    return false;
  }
  
  $type= isset($comm['category_id']) ? 'category' : 'image';
  
  // exclude current user
  $exclude = null;
  if (!empty($_POST['stc_mail'])) $exclude = pwg_db_real_escape_string($_POST['stc_mail']);
  else if (!is_a_guest()) $exclude = $user['email'];
  
  // get subscribers emails
  $query = '
SELECT 
    email
  FROM '.SUBSCRIBE_TO_TABLE.'
  WHERE
    '.$type.'_id = '.$comm[$type.'_id'].'
    AND validated = true
    AND email != "'.$exclude.'"
';
  $emails = array_from_query($query, 'email');
  
  set_make_full_url();
  if ($type == 'image')
  {
    $element = get_picture_infos($comm['image_id']);
  }
  else if ($type == 'category')
  {
    $element = get_category_infos($comm['category_id']);
  }
  
  // get author name
  if ($comm['author'] == 'guest')
  {
    $comm['author'] = l10n('guest');
  }
  
  // mail content
  $mail_args = array(
    'subject' => '['.strip_tags($conf['gallery_title']).'] Re:'.$element['name'],
    'content_format' => 'text/html',
    );
    
  $generic_content = '
<a href="'.$element['url'].'"><img src="'.$element['thumbnail'].'" alt="'.$element['name'].'"></a>
<br>
<b>.'.trigger_event('render_comment_author', $comm['author']).'</b> wrote :

<blockquote>'.trigger_event('render_comment_content', $comm['content']).'</blockquote>

<a href="'.$element['url'].'#comment-'.$comm['id'].'">Link to comment</a>
<br><br>
================================
<br><br>';

  foreach ($emails as $email)
  {
    $mail_args['content'] = $generic_content.'
<a href="'.make_stc_url('unsubscribe-'.$type, $email, $element['id']).'">Stop receiving notifications</a><br>
<a href="'.make_stc_url('manage', $email).'">Manage my subscribtions</a>';
    pwg_mail($email, $mail_args);
  }
  
  unset_make_full_url();
}


/**
 * add an email to subscribers list
 * @param int (image|category)_id
 * @param string email
 * @param string type (image|category)
 */
function subscribe_to_comments($element_id, $email, $type='image')
{
  global $page, $conf, $user, $template, $picture;
  
  if ( empty($element_id) or empty($type) )
  {
    trigger_error('subscribe_to_comment: missing element_id and/or type', E_USER_WARNING);
    return false;
  }
  
  // check email
  if ( is_a_guest() and empty($email) )
  {
    return false;
  }
  else if (!is_a_guest())
  {
    $email = $user['email'];
  }
  
  // don't care if already registered
  $query = '
INSERT INTO '.SUBSCRIBE_TO_TABLE.'(
    email,
    '.$type.'_id,
    registration_date,
    validated
  )
  VALUES(
    "'.pwg_db_real_escape_string($email).'",
    '.$element_id.',
    NOW(),
    "'.(is_a_guest() ? "false" : "true").'"
  )
  ON DUPLICATE KEY UPDATE
    registration_date = IF(validated="true", registration_date, NOW()),
    validated = IF(validated="true", validated, "'.(is_a_guest() ? "false" : "true").'")
;';
  pwg_query($query);
  
  // send validation mail
  if ( is_a_guest() and pwg_db_changes(null) != 0 )
  {
    $element_name = ($type == 'image') ? $picture['current']['name'] : $page['category']['name'];
    
    $mail_args = array(
      'subject' => '['.strip_tags($conf['gallery_title']).'] Please confirm your subscribtion to comments',
      'content_format' => 'text/html',
      );
      
    $mail_args['content'] = '
You requested to subscribe by email to comments on <b>'.$element_name.'</b>.<br>
<br>
We care about your inbox, so we want to confirm this request. Please click the confirm link to activate the subscription.<br>
<br>
<a href="'.make_stc_url('validate-'.$type, $email, $element_id).'">Confirm subscription</a><br>
<br>
If you did not request this action please disregard this message.
';

    pwg_mail($email, $mail_args);
    return 'confirm_mail';
  }
  // just display confirmation message
  else if (pwg_db_changes(null) != 0)
  {
    return true;
  }
}


/**
 * remove an email from subscribers list
 * @param int (image|category)_id
 * @param string email
 * @param string type (image|category)
 */
function un_subscribe_to_comments($element_id, $email, $type='image')
{
  global $template, $user;
  
  if ( empty($element_id) or empty($type) )
  {
    trigger_error('un_subscribe_to_comment: missing element_id and/or type', E_USER_WARNING);
    return false;
  }
  
  // check email
  if ( is_a_guest() and empty($email) )
  {
    return false;
  }
  else if (!is_a_guest())
  {
    $email = $user['email'];
  }
  
  // delete subscription
  switch ($type)
  {
    case 'image' :
    case 'category' :
      $where_clause = $type.'_id = '.pwg_db_real_escape_string($element_id);
    case 'all' :
    {
      $query = '
DELETE FROM '.SUBSCRIBE_TO_TABLE.'
  WHERE 
    email = "'.pwg_db_real_escape_string($email).'"
    '.(!empty($where_clause) ? 'AND '.$where_clause : null).'
;';
      pwg_query($query);
      
      return true;
      break;
    }
  }
  
  return false;
}


/**
 * validate a subscription
 * @param int (image|category)_id
 * @param string email
 * @param string type (image|category)
 */
function validate_subscriptions($element_id, $email, $type='image')
{
  if ( empty($element_id) or empty($email) or empty($type) )
  {
    trigger_error('validate_subscriptions: missing element_id and/or email and/or type', E_USER_WARNING);
    return false;
  }
  
  switch ($type)
  {
    case 'image' :
    case 'category':
      $where_clause = $type.'_id = '.pwg_db_real_escape_string($element_id);
    case 'all' :
    {
       $query = '
UPDATE '.SUBSCRIBE_TO_TABLE.'
  SET validated = "true"
  WHERE 
    email = "'.pwg_db_real_escape_string($email).'"
    '.(!empty($where_clause) ? 'AND '.$where_clause : null).'
;';
      pwg_query($query);
      
      if (pwg_db_changes(null) != 0) return true;
      break;
    }
  }
  
  return false;
}


/**
 * create absolute url to subscriptions section
 * @param string action
 * @param string email
 * @return string
 */
function make_stc_url($action, $email)
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
  
  if (func_num_args() > 2)
  {
    $url_params['id'] = func_get_arg(2);
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
 * get name and url of a picture
 * @param int image_id
 * @return array
 */
function get_picture_infos($image_id, $absolute=false)
{
  global $page;
  
  $query = '
SELECT
    id,
    name,
    file,
    path, 
    tn_ext
  FROM '.IMAGES_TABLE.'
  WHERE id = '.$image_id.'
;';
  $element = pwg_db_fetch_assoc(pwg_query($query));
    
  if (empty($element['name']))
  {
    $element['name'] = get_name_from_file($element['file']);
  }
  
  $url_params = array('image_id' => $element['id']);
  if ( !empty($page['category']) and !$absolute )
  {
    $url_params['section'] = 'categories';
    $url_params['category'] = $page['category'];
  }
  $element['url'] = make_picture_url($url_params);
  
  $element['thumbnail'] = get_thumbnail_url($element);
  
  return $element;
}

/**
 * get name and url of a category
 * @param int cat_id
 * @return array
 */
function get_category_infos($cat_id)
{
  global $conf;
  
  $query = '
SELECT
    cat.id,
    cat.name,
    cat.permalink,
    img.id AS image_id,
    img.path,
    img.tn_ext
  FROM '.CATEGORIES_TABLE.' AS cat
    LEFT JOIN '.USER_CACHE_CATEGORIES_TABLE.' AS ucc 
      ON ucc.cat_id = cat.id AND ucc.user_id = '.$conf['guest_id'].'
    LEFT JOIN '.IMAGES_TABLE.' AS img
      ON img.id = ucc.user_representative_picture_id
  WHERE cat.id = '.$cat_id.'
;';
  $element = pwg_db_fetch_assoc(pwg_query($query));
  // we use guest_id for user_cache beacause we don't know the status of recipient
  
  $url_params['section'] = 'categories';
  $url_params['category'] = $element;
  $element['url'] = make_index_url($url_params);
  
  $element['thumbnail'] = get_thumbnail_url(array(
    'id' => $element['image_id'],
    'path' => $element['path'],
    'tn_ext' => $element['tn_ext'],
    ));
  
  return $element;
}


/**
 * crypt a string using mcrypt extension or a binary method
 * @param string value to crypt
 * @param string key
 * @return string
 */
function crypt_value($value, $key)
{  
  if (extension_loaded('mcrypt'))
  {
    $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
    $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
    $value = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $value, MCRYPT_MODE_ECB, $iv);
  }
  else
  {
    $value = $value ^ $key; // binary XOR operation
  }
  
  $value = base64url_encode($value);
  return trim($value); 
}

/**
 * decrypt a string crypted with previous function
 * @param string value to decrypt
 * @param string key
 * @return string
 */
function decrypt_value($value, $key)
{
  $value = base64url_decode($value); 
  
  if (extension_loaded('mcrypt'))
  {
    $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
    $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
    $value = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $key, $value, MCRYPT_MODE_ECB, $iv);
  }
  else
  {
    $value = $value ^ $key; // binary XOR operation
  }
  
  return trim($value);
}


/**
 * variant of base64 functions usable into url
 * http://fr.php.net/manual/fr/function.base64-encode.php#103849
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