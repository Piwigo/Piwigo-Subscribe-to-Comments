<?php
if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

global $prefixeTable;

define('STC_TABLE', $prefixeTable . 'subscribe_to_comments');

define(
  'stc_default_config', 
  serialize(array(
    'notify_admin_on_subscribe' => false,
    'allow_global_subscriptions' => true,
    ))
  );
  

function plugin_install() 
{
  /* create table to store subscribtions */
	pwg_query('
CREATE TABLE IF NOT EXISTS '.STC_TABLE.' (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `type` enum("image","album-images","album","all-images","all-albums") NOT NULL DEFAULT "image",
  `element_id` mediumint(8) DEFAULT NULL,
  `language` varchar(64) NOT NULL,
  `email` varchar(255) NOT NULL,
  `registration_date` datetime NOT NULL,
  `validated` enum("true","false") NOT NULL DEFAULT "false",
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQUE` (`email` , `type` , `element_id`)
) DEFAULT CHARSET=utf8
;');

  /* config parameter */
  conf_update_param('Subscribe_to_Comments', stc_default_config);

}

function plugin_activate()
{
  global $conf;
  
  // new config in 1.1
  if (empty($conf['Subscribe_to_Comments']))
  {
    conf_update_param('Subscribe_to_Comments', stc_default_config);
  }
  
  // table structure upgrade in 1.1
  $result = pwg_query('SHOW COLUMNS FROM '.STC_TABLE.' LIKE "element_id";');
  if (!pwg_db_num_rows($result))
  {
    // backup subscriptions
    $query = 'SELECT * FROM '.STC_TABLE.' ORDER BY registration_date;';
    $subscriptions = hash_from_query($query, 'id');
    
    // upgrade the table
    pwg_query('TRUNCATE TABLE '.STC_TABLE.';');
    pwg_query('ALTER TABLE '.STC_TABLE.' DROP `image_id`, DROP `category_id`;');
    pwg_query('ALTER TABLE '.STC_TABLE.' AUTO_INCREMENT = 1;');
    
    $query = '
ALTER TABLE '.STC_TABLE.' 
  ADD `type` ENUM( "image", "album-images", "album", "all-images", "all-albums" ) NOT NULL DEFAULT "image" AFTER `id`,
  ADD `element_id` MEDIUMINT( 8 ) NULL AFTER `type`,
  ADD `language` VARCHAR( 64 ) NOT NULL AFTER `element_id` 
;';
    pwg_query($query);
    
    pwg_query('ALTER TABLE '.STC_TABLE.'  DROP INDEX `UNIQUE`, ADD UNIQUE `UNIQUE` ( `email` , `type` , `element_id` );');
    
    // convert datas and fill the table
    $inserts = array();
    
    foreach ($subscriptions as $row)
    {
      if (!empty($row['category_id']))
      {
        $row['type'] = 'album';
        $row['element_id'] = $row['category_id'];
      }
      else if (!empty($row['image_id']))
      {
        $row['type'] = 'image';
        $row['element_id'] = $row['image_id'];
      }
      else
      {
        continue;
      }
      
      unset($row['id'], $row['image_id'], $row['category_id']);
      $row['language'] = 'en_UK';
      
      array_push($inserts, $row);
    }
    
    if (count($inserts) > 0)
    {
      $dbfields = array('type', 'element_id', 'language', 'email', 'registration_date', 'validated');
      mass_inserts(STC_TABLE, $dbfields, $inserts);
    }
  }
}

function plugin_uninstall() 
{
  /* delete table and config */
  pwg_query('DROP TABLE '.STC_TABLE.';');
  pwg_query('DELETE FROM `'. CONFIG_TABLE .'` WHERE param = "Subscribe_to_Comments" LIMIT 1;');
}
?>