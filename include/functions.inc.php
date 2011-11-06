<?php
if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

/**
 * Send comment to subscribers
 * @param array comm
 */
function send_comment_to_subscribers($comm)
{
  global $conf, $page, $user;
  
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
    // get image infos
    $query = '
SELECT
    id,
    name,
    file
  FROM '.IMAGES_TABLE.'
  WHERE id = '.$comm['image_id'].'
;';
    $element = pwg_db_fetch_assoc(pwg_query($query));
      
    if (empty($element['name']))
    {
      $element['name'] = get_name_from_file($element['file']);
    }
    
    $url_params = array('image_id' => $element['id']);
    if (!empty($page['category']))
    {
      $url_params['section'] = 'categories';
      $url_params['category'] = $page['category'];
    }

    $element['url'] = make_picture_url($url_params);
  }
  else if ($type == 'category')
  {
    // get category infos
    $query = '
SELECT
    id,
    name,
    permalink
  FROM '.CATEGORIES_TABLE.'
  WHERE id = '.$comm['category_id'].'
;';
    $element = pwg_db_fetch_assoc(pwg_query($query));
    
    $url_params['section'] = 'categories';
    $url_params['category'] = $element;
    
    $element['url'] = make_index_url($url_params);
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
<b>.'.trigger_event('render_comment_author', $comm['author']).'</b> wrote :

<blockquote>'.trigger_event('render_comment_content', $comm['content']).'</blockquote>

<a href="'.$element['url'].'#comment-'.$comm['id'].'">Link to comment</a>
<br><br>
================================
<br><br>';

  foreach ($emails as $email)
  {
    $mail_args['content'] = $generic_content.'
<a href="'.make_stc_url('unsubscribe-'.$type, $email, $element['id']).'">Stop receiving notifications for this picture</a><br>
<a href="'.make_stc_url('unsubscribe-all', $email).'">Stop receiving all notifications</a><br>
';
//<a href="'.make_stc_url('manage', $email).'">Manage my subscribtions</a>
    pwg_mail($email, $mail_args);
  }
  
  unset_make_full_url();
}


/*
 * add an email to subscribers list
 * @param int (image|category)_id
 * @param string email
 * @param string type (image|category)
 */
function subscribe_to_comments($element_id, $email, $type='image')
{
  global $page, $user, $conf, $template, $picture;
  
  $infos = $errors = array();
  if ( is_a_guest() and empty($email) )
  {
    array_push($errors, l10n('Invalid email adress, your are not subscribed to comments.'));
    
    $orig = $template->get_template_vars('errors');
    if (empty($orig)) $orig = array();
    $template->assign('errors', array_merge($orig, $errors));
    
    if ($type == 'category') $template->set_prefilter('index', 'coa_messages'); // here we use a prefilter existing in COA
    
    return;
  }
  else if (!is_a_guest())
  {
    $email = $user['email'];
  }
  
  // don't care if already registered
  $query = '
INSERT IGNORE INTO '.SUBSCRIBE_TO_TABLE.'(
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
;';
  pwg_query($query);
  
  // send validation mail
  if (is_a_guest() and pwg_db_insert_id() != 0)
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
    array_push($infos, l10n('Please check your email inbox to confirm your subscription.'));
  }
  // just displat confirmation message
  else if (pwg_db_insert_id() != 0)
  {
    array_push($infos, l10n('You have been added to the list of subscribers for this '.($type=='image' ? 'picture' : 'album').'.'));
  }
  
  
  if (!empty($infos))
  {
    $orig = $template->get_template_vars('infos');
    if (empty($orig)) $orig = array();
    $template->assign('infos', array_merge($orig, $infos));
    
    if ($type == 'category') $template->set_prefilter('index', 'coa_messages');
  }
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
    trigger_error('make_stc_url missing action and/or mail', E_USER_WARNING);
    return null;
  }
  
  global $conf;
  set_make_full_url();
  
  $url_params = array(
    'action' => $action,
    'email' => $email,
    'key' => crypt_value($action.$email, $conf['secret_key']),
    );
  
  if (func_num_args() > 2)
  {
    $url_params['param'] = func_get_arg(2);
  }
  
  $url = add_url_params(
    make_index_url( array('section' => 'subscriptions') ),
    $url_params
    );
    
  unset_make_full_url();
  return $url;
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