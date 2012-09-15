<?php 
/*
Plugin Name: Subscribe To Comments
Version: auto
Description: This plugin allows to subscribe to comments by email.
Plugin URI: http://piwigo.org/ext/extension_view.php?eid=587
Author: Mistic
Author URI: http://www.strangeplanet.fr
*/

if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

global $prefixeTable;

define('SUBSCRIBE_TO_PATH' , PHPWG_PLUGINS_PATH . 'Subscribe_to_comments/');
define('SUBSCRIBE_TO_TABLE', $prefixeTable . 'subscribe_to_comments');
define('SUBSCRIBE_TO_VERSION', '2.0.3');


add_event_handler('init', 'stc_init');


function stc_init()
{
  global $conf, $user, $pwg_loaded_plugins;
  
  // no comments on luciano
  if ($user['theme'] == 'luciano') return;
  
  // apply upgrade if needed
  if (
    $pwg_loaded_plugins['Subscribe_to_comments']['version'] == 'auto' or
    version_compare($pwg_loaded_plugins['Subscribe_to_comments']['version'], SUBSCRIBE_TO_VERSION, '<')
  )
  {
    include_once(SUBSCRIBE_TO_PATH . 'include/install.inc.php');
    stc_install();
    
    if ($pwg_loaded_plugins['Subscribe_to_comments']['version'] != 'auto')
    {
      $query = '
UPDATE '. PLUGINS_TABLE .'
SET version = "'. SUBSCRIBE_TO_VERSION .'"
WHERE id = "Subscribe_to_comments"';
      pwg_query($query);
      
      $pwg_loaded_plugins['Subscribe_to_comments']['version'] = SUBSCRIBE_TO_VERSION;
      
      if (defined('IN_ADMIN'))
      {
        $_SESSION['page_infos'][] = 'Subscribe to comments updated to version '. SUBSCRIBE_TO_VERSION;
      }
    }
  }
  
  // load language and conf
  load_language('plugin.lang', SUBSCRIBE_TO_PATH);
  $conf['Subscribe_to_Comments'] = unserialize($conf['Subscribe_to_Comments']);
  
  
  include_once(SUBSCRIBE_TO_PATH.'include/functions.inc.php');
  include_once(SUBSCRIBE_TO_PATH.'include/subscribe_to_comments.inc.php');

  
  // send mails
  add_event_handler('user_comment_insertion', 'stc_comment_insertion');
  add_event_handler('user_comment_validation', 'stc_comment_validation', EVENT_HANDLER_PRIORITY_NEUTRAL, 2);

  // subscribe
  add_event_handler('loc_end_picture', 'stc_on_picture');
  add_event_handler('loc_begin_coa', 'stc_on_album');

  // management
  add_event_handler('loc_end_section_init', 'stc_detect_section');
  add_event_handler('loc_begin_page_header', 'stc_load_section');
  
  // items deletion
  add_event_handler('begin_delete_elements', 'stc_delete_elements');
  add_event_handler('delete_categories', 'stc_delete_categories');

  // profile link
  add_event_handler('loc_begin_profile', 'stc_profile_link');
  
  // config page
  add_event_handler('get_admin_plugin_menu_links', 'stc_admin_menu');
}


function stc_admin_menu($menu) 
{
  array_push($menu, array(
    'NAME' => 'Subscribe to Comments',
    'URL' => get_root_url().'admin.php?page=plugin-' . basename(dirname(__FILE__))
  ));
  return $menu;
}

?>