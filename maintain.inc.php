<?php
if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

include_once(PHPWG_PLUGINS_PATH . 'Subscribe_to_Comments/include/install.inc.php');
  

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