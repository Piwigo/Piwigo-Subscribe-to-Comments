<?php
defined('SUBSCRIBE_TO_PATH') or die('Hacking attempt!');

if (isset($_POST['config_submit']))
{
  $conf['Subscribe_to_Comments'] = array(
    'notify_admin_on_subscribe' => isset($_POST['notify_admin_on_subscribe']),
    'allow_global_subscriptions' => isset($_POST['allow_global_subscriptions']),
    );

  conf_update_param('Subscribe_to_Comments', serialize($conf['Subscribe_to_Comments']));
  $page['infos'][] = l10n('Information data registered in database');
}


$template->assign($conf['Subscribe_to_Comments']);

$template->set_filename('stc_admin', realpath(SUBSCRIBE_TO_PATH . 'admin/template/config.tpl'));
