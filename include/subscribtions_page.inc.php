<?php 
if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

global $template, $conf;

$infos = $errors = array();

// check input parameters
if ( 
  empty($_GET['action']) or empty($_GET['email']) or empty($_GET['key'])
  or decrypt_value($_GET['key'], $conf['secret_key']) !== $_GET['action'].$_GET['email']  
  )
{
  set_status_header(403);
  array_push($errors, l10n('Bad query'));
}

switch ($_GET['action'])
{
  /* unsubscribe */
  case 'unsubscribe-image' :
    if (empty($where_clause)) $where_clause = 'image_id = '.pwg_db_real_escape_string($_GET['param']);
  case 'unsubscribe-category':
    if (empty($where_clause)) $where_clause = 'category_id = '.pwg_db_real_escape_string($_GET['param']);
  case 'unsubcribe-all' :
  {
    $query = '
DELETE FROM '.SUBSCRIBE_TO_TABLE.'
  WHERE 
    email = "'.pwg_db_real_escape_string($_GET['email']).'"
    '.(!empty($where_clause) ? 'AND '.$where_clause : null).'
;';
    pwg_query($query);
    
    array_push($infos, l10n('You have been successfully unsubscribed, good bye.'));
    break;
  }
  
  /* validate */
  case 'validate-image' :
    if (empty($where_clause)) $where_clause = 'image_id = '.pwg_db_real_escape_string($_GET['param']);
  case 'validate-category':
    if (empty($where_clause)) $where_clause = 'category_id = '.pwg_db_real_escape_string($_GET['param']);
  case 'validate-all' :
  {
     $query = '
UPDATE '.SUBSCRIBE_TO_TABLE.'
  SET validated = "true"
  WHERE 
    email = "'.pwg_db_real_escape_string($_GET['email']).'"
    '.(!empty($where_clause) ? 'AND '.$where_clause : null).'
;';
    pwg_query($query);
    
    array_push($infos, l10n('Your subscribtion has been validated, thanks you.'));
    break;
  }
  
  /* manage */
  case 'manage' :
  {
    break;
  }
  
  default :
  {
    set_status_header(403);
    array_push($errors, l10n('Bad query'));
  }
}

$template->assign(array(
  'infos' => $infos,
  'errors' => $errors,
  ));

$template->set_filenames(array('index'=> dirname(__FILE__).'/../template/subscribtions_page.tpl'));
?>