<?php
if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

function plugin_install() 
{
	global $prefixeTable;

  /* create table to store subscribtions */
	pwg_query('
CREATE TABLE IF NOT EXISTS `' . $prefixeTable . 'subscribe_to_comments` (
  `id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
  `email` VARCHAR( 255 ) NOT NULL ,
  `image_id` MEDIUMINT( 8 ) UNSIGNED NOT NULL DEFAULT "0",
  `category_id` SMALLINT( 5 ) UNSIGNED NOT NULL DEFAULT "0",
  `registration_date` DATETIME NOT NULL,
  `validated` ENUM( "true", "false" ) NOT NULL DEFAULT "false",
  UNIQUE KEY `UNIQUE` (`email`, `image_id`, `category_id`)
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
  pwg_query('DROP TABLE `' . $prefixeTable . 'subscribe_to_comments`;');
  // pwg_query('DELETE FROM `' . CONFIG_TABLE . '` WHERE param = "Subscribe_to_Comments";');
}
?>