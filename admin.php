<?php
if(!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

// +-----------------------------------------------------------------------+
//                         Save configuration                              |
// +-----------------------------------------------------------------------+
if (isset($_POST['config_submit'])) 
{
  $conf['Subscribe_to_Comments'] = array(
    'notify_admin_on_subscribe' => isset($_POST['notify_admin_on_subscribe']),
    'allow_global_subscriptions' => isset($_POST['allow_global_subscriptions']),
    );
  
  conf_update_param('Subscribe_to_Comments', serialize($conf['Subscribe_to_Comments']));
  array_push($page['infos'], l10n('Information data registered in database'));
}  


// +-----------------------------------------------------------------------+
//                               Template                                  |
// +-----------------------------------------------------------------------+  
$template->assign(array(
  'SUBSCRIBE_TO_PATH' => SUBSCRIBE_TO_PATH,
  'notify_admin_on_subscribe' => $conf['Subscribe_to_Comments']['notify_admin_on_subscribe'],
  'allow_global_subscriptions' => $conf['Subscribe_to_Comments']['allow_global_subscriptions'],
  ));

$template->set_filename('plugin_admin_content', dirname(__FILE__).'/template/admin.tpl');
$template->assign_var_from_handle('ADMIN_CONTENT', 'plugin_admin_content');

?>