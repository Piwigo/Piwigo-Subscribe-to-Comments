<?php
if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

defined('SUBSCRIBE_TO_ID') or define('SUBSCRIBE_TO_ID', basename(dirname(__FILE__)));
include_once(PHPWG_PLUGINS_PATH . SUBSCRIBE_TO_ID . '/include/install.inc.php');
  

function plugin_install() 
{
  stc_install();
  define('stc_installed', true);
}

function plugin_activate()
{
  if (!defined('stc_installed'))
  {
    stc_install();
  }
}

function plugin_uninstall() 
{
  global $prefixeTable;
  
  pwg_query('DROP TABLE `'. $prefixeTable . 'subscribe_to_comments`;');
  pwg_query('DELETE FROM `'. CONFIG_TABLE .'` WHERE param = "Subscribe_to_Comments" LIMIT 1;');
}
?>