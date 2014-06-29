<?php
defined('PHPWG_ROOT_PATH') or die('Hacking attempt!');

class Subscribe_to_Comments_maintain extends PluginMaintain
{
  private $default_conf = array(
    'notify_admin_on_subscribe' => false,
    'allow_global_subscriptions' => true,
    );

  private $table;

  function __construct($plugin_id)
  {
    parent::__construct($plugin_id);

    global $prefixeTable;
    $this->table = $prefixeTable . 'subscribe_to_comments';
  }

  function install($plugin_version, &$errors=array())
  {
    global $conf;

    // config parameter
    if (empty($conf['Subscribe_to_Comments']))
    {
      conf_update_param('Subscribe_to_Comments', $this->default_config, true);
    }

    // subscriptions table
    pwg_query('
CREATE TABLE IF NOT EXISTS `'. $this->table .'` (
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

    // table structure upgrade in 1.1
    $result = pwg_query('SHOW COLUMNS FROM `'. $this->table .'` LIKE "element_id";');
    if (!pwg_db_num_rows($result))
    {
      // backup subscriptions
      $query = 'SELECT * FROM `'. $this->table .'` ORDER BY registration_date;';
      $subscriptions = query2array($query, 'id');

      // upgrade the table
      pwg_query('TRUNCATE TABLE `'. $this->table .'`;');
      pwg_query('ALTER TABLE `'. $this->table .'` DROP `image_id`, DROP `category_id`;');
      pwg_query('ALTER TABLE `'. $this->table .'` AUTO_INCREMENT = 1;');

      $query = '
ALTER TABLE `'. $this->table .'`
  ADD `type` enum("image", "album-images", "album", "all-images", "all-albums") NOT NULL DEFAULT "image" AFTER `id`,
  ADD `element_id` mediumint(8) NULL AFTER `type`,
  ADD `language` varchar(64) NOT NULL AFTER `element_id`
;';
      pwg_query($query);

      pwg_query('ALTER TABLE `'. $this->table .'`  DROP INDEX `UNIQUE`, ADD UNIQUE `UNIQUE` (`email`, `type`, `element_id`);');

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
        mass_inserts($this->table, $dbfields, $inserts);
      }
    }
  }

  function update($old_version, $new_version, &$errors=array())
  {
    $this->install($new_version, $errors);
  }

  function uninstall()
  {
    conf_delete_param('Subscribe_to_Comments');

    pwg_query('DROP TABLE `'. $this->table .'`;');
  }
}
