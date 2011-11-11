<?php 
/*
Plugin Name: Subscribe To Comments
Version: auto
Description: This plugin allows you to subscribe to comments by email.
Plugin URI: http://piwigo.org/ext/extension_view.php?eid=587
Author: Mistic
Author URI: http://www.strangeplanet.fr
*/

if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

global $prefixeTable;

define('SUBSCRIBE_TO_DIR' , basename(dirname(__FILE__)));
define('SUBSCRIBE_TO_PATH' , PHPWG_PLUGINS_PATH . SUBSCRIBE_TO_DIR . '/');
define('SUBSCRIBE_TO_TABLE', $prefixeTable . 'subscribe_to_comments');

include_once(SUBSCRIBE_TO_PATH.'include/functions.inc.php');
include_once(SUBSCRIBE_TO_PATH.'include/subscribe_to_comments.inc.php');

load_language('plugin.lang', SUBSCRIBE_TO_PATH);

// send mails
add_event_handler('user_comment_insertion', 'stc_comment_insertion');
add_event_handler('user_comment_validation', 'stc_comment_validation', EVENT_HANDLER_PRIORITY_NEUTRAL, 2);

// subscribe
add_event_handler('loc_end_picture', 'stc_on_picture');
add_event_handler('loc_begin_index', 'stc_on_album');

// management
add_event_handler('loc_end_section_init', 'stc_detect_section');
add_event_handler('loc_end_index', 'stc_load_section');

// menu link
add_event_handler('blockmanager_apply', 'stc_menubar_apply');

?>