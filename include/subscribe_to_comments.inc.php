<?php
if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

/**
 * detect 'subscriptions' section and load the page
 */
function stc_detect_section()
{
  global $tokens, $page, $conf;
  
  if ($tokens[0] == 'subscriptions')
  {
    add_event_handler('loc_begin_page_header', 'stc_page_header');
    
    $page['section'] = 'subscriptions';
    $page['title'] = l10n('Comments notifications');
    $page['section_title'] = '<a href="'.get_absolute_root_url().'">'.l10n('Home').'</a>'.$conf['level_separator'].l10n('Comments notifications');
  }
}

function stc_page_header()
{
  global $page;
  $page['body_id'] = 'theSubscriptionsPage';
}

function stc_load_section()
{
  global $page;

  if (isset($page['section']) and $page['section'] == 'subscriptions')
  {
    include(SUBSCRIBE_TO_PATH.'include/subscribtions_page.inc.php');
  }
}


/**
 * send notifications and/or add subscriber on comment insertion
 * @param: array comment
 */
function stc_comment_insertion($comm)
{
  global $page, $template;
  
  if ($comm['action'] == 'validate')
  {
    send_comment_to_subscribers($comm);
  }
  
  if ( !empty($_POST['stc_mode']) and $_POST['stc_mode'] != -1 )
  {
    if ($comm['action'] != 'reject')
    {
      if (isset($comm['image_id']))
      {
        switch ($_POST['stc_mode'])
        {
          case 'all-images':
            subscribe_to_comments(@$_POST['email'], 'all-images');
            break;
          case 'album-images':
            if (empty($page['category']['id'])) break;
            subscribe_to_comments(@$_POST['email'], 'album-images', $page['category']['id']);
            break;
          case 'image':
            subscribe_to_comments(@$_POST['email'], 'image', $comm['image_id']);
            break;
        }
      }
      else if (isset($comm['category_id']))
      {
        switch ($_POST['stc_mode'])
        {
          case 'all-albums':
            subscribe_to_comments(@$_POST['email'], 'all-albums');
            break;
          case 'album':
            subscribe_to_comments(@$_POST['email'], 'album', $comm['category_id']);
            break;
        }
      }
    }
    else
    {
      $template->assign('STC_MODE', $_POST['stc_mode']);
    }
  }
}


/**
 * send notifications on comment validation
 * @param: array|int comment_id
 * @param: string type (image|category)
 */
function stc_comment_validation($comm_ids, $type='image')
{
  if (!is_array($comm_ids)) $comm_ids = array($comm_ids);
  
  if ($type == 'image')
  {
    $query = '
SELECT
    id,
    image_id,
    author,
    content
  FROM '.COMMENTS_TABLE.'
  WHERE id IN('.implode(',', $comm_ids).')
;';
  }
  else if ($type == 'category')
  {
    $query = '
SELECT
    id,
    category_id,
    author,
    content
  FROM '.COA_TABLE.'
  WHERE id IN('.implode(',', $comm_ids).')
;';
  }
  
  $comms = hash_from_query($query, 'id');
  foreach ($comms as $comm)
  {
    send_comment_to_subscribers($comm);
  }
}


/**
 * add field and link on picture page
 */
function stc_on_picture()
{
  global $template, $picture, $page, $user, $conf;
  
  // standalone subscription
  if (isset($_POST['stc_submit']))
  {
    switch ($_POST['stc_mode'])
    {
      case 'all-images':
        subscribe_to_comments(@$_POST['stc_mail'], 'all-images');
        break;
      case 'album-images':
        if (empty($page['category']['id'])) break;
        subscribe_to_comments(@$_POST['stc_mail'], 'album-images', $page['category']['id']);
        break;
      case 'image':
        subscribe_to_comments(@$_POST['stc_mail'], 'image', $picture['current']['id']);
        break;
    }
  }
  else if (isset($_GET['stc_unsubscribe']))
  {    
    if (un_subscribe_to_comments(null, $_GET['stc_unsubscribe']))
    {
      array_push($page['infos'], l10n('Successfully unsubscribed your email address from receiving notifications.'));
    }
  }
  
  // if registered user with mail we check if already subscribed
  if ( !is_a_guest() and !empty($user['email']) )
  {
    $subscribed = false;
    
    // registered to all pictures
    if (!$subscribed)
    {
      $query = '
SELECT id
  FROM '.SUBSCRIBE_TO_TABLE.'
  WHERE
    email = "'.$user['email'].'"
    AND type = "all-images"
    AND validated = "true"
;';
      $result = pwg_query($query);
      
      if (pwg_db_num_rows($result))
      {
        $template->assign(array(
          'SUBSCRIBED_ALL_IMAGES' => true,
          'MANAGE_LINK' => make_stc_url('manage', $user['email']),
          ));
        $subscribed = true;
      }
    }

    // registered to pictures in this album
    if ( !$subscribed and !empty($page['category']['id']) )
    {
      $query = '
SELECT id
  FROM '.SUBSCRIBE_TO_TABLE.'
  WHERE
    email = "'.$user['email'].'"
    AND element_id = '.$page['category']['id'].'
    AND type = "album-images"
    AND validated = "true"
;';
      $result = pwg_query($query);
      
      if (pwg_db_num_rows($result))
      {
        list($stc_id) = pwg_db_fetch_row($result);
        $template->assign(array(
          'SUBSCRIBED_ALBUM_IMAGES' => true,
          'UNSUB_LINK' => add_url_params($picture['current']['url'], array('stc_unsubscribe'=>$stc_id)),
          ));
        $subscribed = true;
      }
    }
    
    // registered to this picture
    if (!$subscribed)
    {
      $query = '
SELECT id
  FROM '.SUBSCRIBE_TO_TABLE.'
  WHERE
    email = "'.$user['email'].'"
    AND element_id = '.$picture['current']['id'].'
    AND type = "image"
    AND validated = "true"
;';
      $result = pwg_query($query);
      
      if (pwg_db_num_rows($result))
      {
        list($stc_id) = pwg_db_fetch_row($result);
        $template->assign(array(
          'SUBSCRIBED_IMAGE' => true,
          'UNSUB_LINK' => add_url_params($picture['current']['url'], array('stc_unsubscribe'=>$stc_id)),
          ));
        $subscribed = true;
      }
    }
  }
  else
  {
    $template->assign('STC_ASK_MAIL', true);
  }
  
  $template->assign(array(
    'STC_ON_PICTURE' => true,
    'STC_ALLOW_ALBUM_IMAGES' => !empty($page['category']['id']),
    'STC_ALLOW_GLOBAL' => $conf['Subscribe_to_Comments']['allow_global_subscriptions'] || is_admin(),
    'SUBSCRIBE_TO_PATH' => SUBSCRIBE_TO_PATH,
    ));
  
  $template->set_prefilter('picture', 'stc_main_prefilter');
}


/**
 * add field and link on album page
 */
function stc_on_album()
{
  global $page, $template, $pwg_loaded_plugins, $user, $conf;
  
  if (
      script_basename() != 'index' or !isset($page['section']) or
      !isset($pwg_loaded_plugins['Comments_on_Albums']) or 
      $page['section'] != 'categories' or !isset($page['category'])
    )
  {
    return;
  }
  
	// standalone subscription
  if (isset($_POST['stc_submit']))
  {
    switch ($_POST['stc_mode'])
    {
      case 'all-albums':
        subscribe_to_comments(@$_POST['stc_mail'], 'all-albums');
        break;
      case 'album':
        subscribe_to_comments(@$_POST['stc_mail'], 'album', $page['category']['id']);
        break;
    }
  }
  else if (isset($_GET['stc_unsubscribe']))
  {
    if (un_subscribe_to_comments(null, $_GET['stc_unsubscribe']))
    {
      array_push($page['infos'], l10n('Successfully unsubscribed your email address from receiving notifications.'));
    }
  }
  
  // if registered user we check if already subscribed
  if ( !is_a_guest() and !empty($user['email']) )
  {
    $subscribed = false;
    
    // registered to all albums
    if (!$subscribed)
    {
      $query = '
SELECT id
  FROM '.SUBSCRIBE_TO_TABLE.'
  WHERE
    email = "'.$user['email'].'"
    AND type = "all-albums"
    AND validated = "true"
;';
      $result = pwg_query($query);
      
      if (pwg_db_num_rows($result))
      {
        $template->assign(array(
          'SUBSCRIBED_ALL_ALBUMS' => true,
          'MANAGE_LINK' => make_stc_url('manage', $user['email']),
          ));
        $subscribed = true;
      }
    }
    
    // registered to this album
    if (!$subscribed)
    {
      $query = '
SELECT id
  FROM '.SUBSCRIBE_TO_TABLE.'
  WHERE
    email = "'.$user['email'].'"
    AND element_id = '.$page['category']['id'].'
    AND type = "album"
    AND validated = "true"
;';
      $result = pwg_query($query);
      
      if (pwg_db_num_rows($result))
      {
        list($stc_id) = pwg_db_fetch_row($result);
        $element_url = make_index_url(array(
          'section' => 'categories',
          'category' => $page['category'],
          ));
        
        $template->assign(array(
          'SUBSCRIBED_ALBUM' => true,
          'UNSUB_LINK' => add_url_params($element_url, array('stc_unsubscribe'=>$stc_id)),
          ));
        $subscribed = true;
      }
    }
  }
  else
  {
    $template->assign('STC_ASK_MAIL', true);
  }
  
  $template->assign(array(
    'STC_ON_ALBUM' => true,
    'STC_ALLOW_GLOBAL' => $conf['Subscribe_to_Comments']['allow_global_subscriptions'] || is_admin(),
    'SUBSCRIBE_TO_PATH' => SUBSCRIBE_TO_PATH,
    ));

  $template->set_prefilter('comments_on_albums', 'stc_main_prefilter');
}


/**
 * main prefilter
 */
function stc_main_prefilter($content, &$smarty)
{
  ## subscribe at any moment ##
  $search = '{if isset($comments)}';
  $replace = file_get_contents(SUBSCRIBE_TO_PATH.'template/form_standalone.tpl');
  $content = str_replace($search, $replace.$search, $content);
  
  ## subscribe while add a comment ##
  $search = '{$comment_add.CONTENT}</textarea></p>';
  $replace = file_get_contents(SUBSCRIBE_TO_PATH.'template/form_comment.tpl');
  $content = str_replace($search, $search.$replace, $content);
  
  return $content;
}


/**
 * delete subscriptions to deleted images or categories
 */
function stc_delete_elements($ids)
{
  $query = '
DELETE FROM '.SUBSCRIBE_TO_TABLE.'
  WHERE 
    element_id IN ('.implode(',', $ids).')
    AND type = "image"
';
  pwg_query($query);
}
function stc_delete_categories($ids)
{
  $query = '
DELETE FROM '.SUBSCRIBE_TO_TABLE.'
  WHERE 
    element_id IN ('.implode(',', $ids).')
    AND (type = "album" OR type = "album-images")
';
  pwg_query($query);
}


/**
 * add link to management page for registered users
 */
function stc_profile_link()
{
  global $template, $user;
  
  if (!empty($user['email']))
  {
    $template->assign('MANAGE_LINK', make_stc_url('manage', $user['email']) );
    $template->set_prefilter('profile_content', 'stc_profile_link_prefilter');
  }
}
function stc_profile_link_prefilter($content, &$smarty)
{
  $search = '<p class="bottomButtons">';
  $replace = '<p style="font-size:1.1em;text-decoration:underline;"><a href="{$MANAGE_LINK}" rel="nofollow">{\'Manage my subscriptions\'|@translate}</a></p>';
  
  return str_replace($search, $replace.$search, $content);
}

?>