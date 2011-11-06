<?php
if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

function plugin_install() 
{
	global $prefixeTable;

  /* create table to store subscribtions */
	pwg_query('
CREATE TABLE IF NOT EXISTS `' . $prefixeTable . 'subscribe_to_comments` (
  `email` varchar(255) NOT NULL,
  `image_id` mediumint(8) NOT NULL DEFAULT 0,
  `category_id` smallint(5) NOT NULL DEFAULT 0,
  `registration_date` datetime NOT NULL,
  `validated` enum("true", "false") NOT NULL DEFAULT "false",
  UNIQUE KEY `UNIQUE` (`mail`, `image_id`, `category_id`)
) DEFAULT CHARSET=utf8
;');
      
  /* config parameter */
  // pwg_query('
// INSERT INTO `' . CONFIG_TABLE . '`
  // VALUES (
    // "Subscribe_to_Comments", 
    // "'.serialize(array()).'",
    // "Configuration for Subscribe_to_Comments plugin"
  // )
// ;');

}

function plugin_uninstall() 
{
	global $prefixeTable;
  
  /* delete table and config */
  // pwg_query('DROP TABLE `' . $prefixeTable . 'subscribe_to_comments`;');
  pwg_query('DELETE FROM `' . CONFIG_TABLE . '` WHERE param = "Subscribe_to_Comments";');
}
?>