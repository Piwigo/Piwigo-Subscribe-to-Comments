<?php
/*
Plugin Name: Subscribe To Comments
Version: auto
Description: This plugin allows to subscribe to comments by email.
Plugin URI: auto
Author: Mistic
Author URI: http://www.strangeplanet.fr
Has Settings: false
*/

defined('PHPWG_ROOT_PATH') or die('Hacking attempt!');

if (basename(dirname(__FILE__)) != 'Subscribe_to_Comments')
{
  add_event_handler('init', 'stc_error');
  function stc_error()
  {
    global $page;
    $page['errors'][] = 'Subscribe to Comments folder name is incorrect, uninstall the plugin and rename it to "Subscribe_to_Comments"';
  }
  return;
}

global $prefixeTable;

define('SUBSCRIBE_TO_PATH' ,   PHPWG_PLUGINS_PATH . 'Subscribe_to_Comments/');
define('SUBSCRIBE_TO_TABLE',   $prefixeTable . 'subscribe_to_comments');
define('SUBSCRIBE_TO_ADMIN',   get_root_url() . 'admin.php?page=plugin-Subscribe_to_Comments');


add_event_handler('init', 'stc_init');


function stc_init()
{
  global $conf, $user;

  // no comments on luciano
  if ($user['theme'] == 'luciano')
  {
    return;
  }

  load_language('plugin.lang', SUBSCRIBE_TO_PATH);
  $conf['Subscribe_to_Comments'] = safe_unserialize($conf['Subscribe_to_Comments']);

  include_once(SUBSCRIBE_TO_PATH.'include/functions.inc.php');
  include_once(SUBSCRIBE_TO_PATH.'include/events.inc.php');


  if (!defined('IN_ADMIN'))
  {
    // subscribe
    add_event_handler('loc_end_picture', 'stc_on_picture');
    add_event_handler('loc_end_coa', 'stc_on_album');

    // management
    add_event_handler('loc_end_section_init', 'stc_detect_section');
    add_event_handler('loc_begin_page_header', 'stc_load_section');

    // profile link
    add_event_handler('loc_begin_profile', 'stc_profile_link');
  }
  else
  {
    // config page
    add_event_handler('get_admin_plugin_menu_links', 'stc_admin_menu');
  }

  // send mails
  add_event_handler('user_comment_insertion', 'stc_comment_insertion');
  add_event_handler('user_comment_validation', 'stc_comment_validation', EVENT_HANDLER_PRIORITY_NEUTRAL, 2);

  // items deletion
  add_event_handler('begin_delete_elements', 'stc_delete_elements');
  add_event_handler('delete_categories', 'stc_delete_categories');
}


function stc_admin_menu($menu)
{
  $menu[] = array(
    'NAME' => 'Subscribe to Comments',
    'URL' => SUBSCRIBE_TO_ADMIN,
    );
  return $menu;
}
