<?php
if (!defined('SUBSCRIBE_TO_PATH')) die('Hacking attempt!');

global $template, $page, $conf;


// tabsheet
include_once(PHPWG_ROOT_PATH.'admin/include/tabsheet.class.php');
$page['tab'] = (isset($_GET['tab'])) ? $_GET['tab'] : 'subscriptions';
  
$tabsheet = new tabsheet();
$tabsheet->add('subscriptions', l10n('Manage'), SUBSCRIBE_TO_ADMIN . '-subscriptions');
$tabsheet->add('config', l10n('Configuration'), SUBSCRIBE_TO_ADMIN . '-config');
$tabsheet->select($page['tab']);
$tabsheet->assign();


// include page
include(SUBSCRIBE_TO_PATH . 'admin/' . $page['tab'] . '.php');

// template
$template->assign(array(
  'SUBSCRIBE_TO_PATH' => SUBSCRIBE_TO_PATH,
  'SUBSCRIBE_TO_ADMIN' => SUBSCRIBE_TO_ADMIN,
  ));
  
$template->assign_var_from_handle('ADMIN_CONTENT', 'stc_admin');

?>