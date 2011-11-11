<?php 
if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

global $template, $conf;

$infos = $errors = array();

// check input parameters
$_GET['verif_key'] = $_GET['action'].$_GET['email'].(isset($_GET['id'])?$_GET['id']:null);
if ( 
  empty($_GET['action']) or empty($_GET['email']) or empty($_GET['key'])
  or decrypt_value($_GET['key'], $conf['secret_key']) !== $_GET['verif_key']
  )
{
  $_GET['action'] = 'hacker';
}
else
{
  // sanitize inputs
  if (isset($_GET['id'])) $_GET['id'] = pwg_db_real_escape_string($_GET['id']);
  $_GET['email'] = pwg_db_real_escape_string($_GET['email']);

  // unsubscribe
  if (isset($_POST['unsubscribe']))
  {
    if (un_subscribe_to_comments(!empty($_GET['id'])?$_GET['id']:'N/A', $_GET['email'], $_POST['unsubscribe']))
    {
      array_push($infos, l10n('Successfully unsubscribed your email address from receiving notifications.'));
    }
    else
    {
      array_push($errors, l10n('Invalid email adress.'));
    }
    
    $_GET['action'] = 'manage';
  }
  if (isset($_GET['unsubscribe']))
  {
    $query = '
  DELETE FROM '.SUBSCRIBE_TO_TABLE.'
    WHERE 
      id = '.pwg_db_real_escape_string($_GET['unsubscribe']).'
      AND email = "'.$_GET['email'].'"
  ;';
    pwg_query($query);
    
    if (pwg_db_changes(null) != 0)
    {
      array_push($infos, l10n('Successfully unsubscribed your email address from receiving notifications.'));
    }
    else
    {
      array_push($errors, l10n('Invalid email adress.'));
    }
  }
  
  $template->assign('MANAGE_LINK', make_stc_url('manage', $_GET['email']));
}

switch ($_GET['action'])
{
  /* validate */
  case 'validate-image' :
  {
    if (validate_subscriptions($_GET['id'], $_GET['email'], 'image'))
    {
      array_push($infos, l10n('Your subscribtion has been validated, thanks you.'));
    }
    else
    {
      array_push($errors, l10n('Nothing to validate.'));
    }
    
    $element = get_picture_infos($_GET['id']);
    
    $template->assign(array(
      'validate' => 'image',
      'element' => $element,
      ));
      
    break;
  }
  case 'validate-category':
  {
    if (validate_subscriptions($_GET['id'], $_GET['email'], 'category'))
    {
      array_push($infos, l10n('Your subscribtion has been validated, thanks you.'));
    }
    else
    {
      array_push($errors, l10n('Nothing to validate.'));
    }
    
    $element = get_category_infos($_GET['id']);
    
    $template->assign(array(
      'validate' => 'category',
      'element' => $element,
      ));
    break;
  }
  
  /* unsubscribe */
  case 'unsubscribe-image' :
  {  
    $element = get_picture_infos($_GET['id']);
    
    $template->assign(array(
      'unsubscribe_form' => 'image',
      'element' => $element,
      ));
    
    break;
  }
  case 'unsubscribe-category':
  {  
    $element = get_category_infos($_GET['id']);
    
    $template->assign(array(
      'unsubscribe_form' => 'category',
      'element' => $element,
      ));
    
    break;
  }
  
  /* manage */
  case 'manage' :
  {
    $query = '
SELECT *
  FROM '.SUBSCRIBE_TO_TABLE.'
  WHERE 
    email = "'.$_GET['email'].'"
    AND validated = "true"
  ORDER BY registration_date DESC
;';
    $result = pwg_query($query);
    
    if (pwg_db_num_rows($result) !== 0)
    {
      while ($subscription = pwg_db_fetch_assoc($result))
      {
        if (!empty($subscription['image_id']))
        {
          $subscription['infos'] = get_picture_infos($subscription['image_id']);
          $subscription['type'] = 'image';
        }
        else if (!empty($subscription['category_id']))
        {
          $subscription['infos'] = get_category_infos($subscription['category_id']);
          $subscription['type'] = 'category';
        }
        $subscription['registration_date'] = format_date($subscription['registration_date'], true);
        $template->append('subscriptions', $subscription);
      }
    }
    else
    {
      $template->assign('subscriptions', 'none');
    }
    break;
  }
  
  case 'hacker' :
  {
    set_status_header(403);
    array_push($errors, l10n('Bad query'));
  }
}

$template->assign(array(
  'EMAIL' => $_GET['email'],
  'SUBSCRIBE_TO_PATH' => SUBSCRIBE_TO_PATH,
  ));

$template->assign(array(
  'infos' => $infos,
  'errors' => $errors,
  ));

$template->set_filenames(array('index'=> dirname(__FILE__).'/../template/subscribtions_page.tpl'));
?>