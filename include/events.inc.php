<?php
defined('SUBSCRIBE_TO_PATH') or die('Hacking attempt!');

/**
 * detect 'subscriptions' section and load the page
 */
function stc_detect_section()
{
  global $tokens, $page, $conf;

  if ($tokens[0] == 'subscriptions')
  {
    $page['section'] = 'subscriptions';
    $page['body_id'] = 'theSubscriptionsPage';
    $page['is_homepage'] = false;
    $page['is_external'] = true;

    $page['title'] = l10n('Comments notifications');
  }
}

function stc_load_section()
{
  global $page;

  if (isset($page['section']) and $page['section'] == 'subscriptions')
  {
    include(SUBSCRIBE_TO_PATH.'include/subscriptions_page.inc.php');
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

  if (!empty($_POST['stc_mode']) and $_POST['stc_mode'] != -1)
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
            if (!empty($page['category']['id']))
            {
              subscribe_to_comments(@$_POST['email'], 'album-images', $page['category']['id']);
            }
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

      unset($_POST['stc_mode']);
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
  if (!is_array($comm_ids))
  {
    $comm_ids = array($comm_ids);
  }

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
  else if ($type == 'album' && defined('COA_TABLE'))
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

  if (isset($query))
  {
    $comms = query2array($query);
    foreach ($comms as $comm)
    {
      send_comment_to_subscribers($comm);
    }
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
        if (!empty($page['category']['id']))
        {
          subscribe_to_comments(@$_POST['stc_mail'], 'album-images', $page['category']['id']);
        }
        break;
      case 'image':
        subscribe_to_comments(@$_POST['stc_mail'], 'image', $picture['current']['id']);
        break;
    }
    unset($_POST['stc_mode']);
  }
  else if (isset($_GET['stc_unsubscribe']))
  {
    if (un_subscribe_to_comments(null, $_GET['stc_unsubscribe']))
    {
      $page['infos'][] = l10n('Successfully unsubscribed your email address from receiving notifications.');
    }
  }

  $tpl_vars = array(
    'ASK_MAIL' => is_a_guest() or empty($user['email']),
    'ON_PICTURE' => true,
    'ALLOW_ALBUM_IMAGES' => !empty($page['category']['id']),
    'ALLOW_GLOBAL' => $conf['Subscribe_to_Comments']['allow_global_subscriptions'] || is_admin(),
    );

  if (!empty($_POST['stc_mode']))
  {
    $tpl_vars['MODE'] = $_POST['stc_mode'];
  }

  // if registered user with mail we check if already subscribed
  if (!is_a_guest() and !empty($user['email']))
  {
    $subscribed = false;

    $base_query = '
SELECT id
  FROM '.SUBSCRIBE_TO_TABLE.'
  WHERE
    email = "'.$user['email'].'"
    AND validated = "true"
';

    // registered to all pictures
    if (!$subscribed)
    {
      $result = pwg_query($base_query . 'AND type = "all-images";');

      if (pwg_db_num_rows($result))
      {
        list($stc_id) = pwg_db_fetch_row($result);
        $subscribed = 'all-images';
      }
    }

    // registered to pictures in this album
    if (!$subscribed and !empty($page['category']['id']))
    {
      $result = pwg_query($base_query . 'AND type = "album-images" AND element_id = '.$page['category']['id'].';');

      if (pwg_db_num_rows($result))
      {
        list($stc_id) = pwg_db_fetch_row($result);
        $subscribed = 'album-images';
      }
    }

    // registered to this picture
    if (!$subscribed)
    {
      $result = pwg_query($base_query . 'AND type = "image" AND element_id = '.$picture['current']['id'].';');

      if (pwg_db_num_rows($result))
      {
        list($stc_id) = pwg_db_fetch_row($result);
        $subscribed = 'image';
      }
    }

    if ($subscribed)
    {
      $tpl_vars['SUBSCRIBED'] = $subscribed;
      $tpl_vars['U_UNSUB'] = add_url_params($picture['current']['url'], array('stc_unsubscribe'=>$stc_id));
    }
  }

  $template->assign(array(
    'SUBSCRIBE_TO_PATH' => SUBSCRIBE_TO_PATH,
    'STC' => $tpl_vars,
    ));

  $template->set_prefilter('picture', 'stc_main_prefilter');
}


/**
 * add field and link on album page
 */
function stc_on_album()
{
  global $page, $template, $user, $conf;

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
    unset($_POST['stc_mode']);
  }
  else if (isset($_GET['stc_unsubscribe']))
  {
    if (un_subscribe_to_comments(null, $_GET['stc_unsubscribe']))
    {
      $page['infos'][] = l10n('Successfully unsubscribed your email address from receiving notifications.');
    }
  }

  $tpl_vars = array(
    'ASK_MAIL' => is_a_guest() or empty($user['email']),
    'ON_ALBUM' => true,
    'ALLOW_GLOBAL' => $conf['Subscribe_to_Comments']['allow_global_subscriptions'] || is_admin(),
    );

  if (!empty($_POST['stc_mode']))
  {
    $tpl_vars['MODE'] = $_POST['stc_mode'];
  }

  // if registered user we check if already subscribed
  if (!is_a_guest() and !empty($user['email']))
  {
    $subscribed = false;

    $base_query = '
SELECT id
  FROM '.SUBSCRIBE_TO_TABLE.'
  WHERE
    email = "'.$user['email'].'"
    AND validated = "true"
';

    // registered to all albums
    if (!$subscribed)
    {
      $result = pwg_query($base_query . 'AND type = "all-albums"');

      if (pwg_db_num_rows($result))
      {
        list($stc_id) = pwg_db_fetch_row($result);
        $subscribed = 'all-albums';
      }
    }

    // registered to this album
    if (!$subscribed)
    {
      $result = pwg_query($base_query . 'AND type = "album" AND element_id = '.$page['category']['id'].';');

      if (pwg_db_num_rows($result))
      {
        list($stc_id) = pwg_db_fetch_row($result);
        $subscribed = 'album';
      }
    }

    if ($subscribed)
    {
      $element_url = make_index_url(array(
        'section' => 'categories',
        'category' => $page['category'],
        ));

      $tpl_vars['SUBSCRIBED'] = $subscribed;
      $tpl_vars['U_UNSUB'] = add_url_params($element_url, array('stc_unsubscribe'=>$stc_id));
    }
  }

  $template->assign(array(
    'STC' => $tpl_vars,
    'SUBSCRIBE_TO_PATH' => SUBSCRIBE_TO_PATH,
    ));

  $template->set_prefilter('comments_on_albums', 'stc_main_prefilter');
}


/**
 * main prefilter
 */
function stc_main_prefilter($content)
{
  ## subscribe at any moment ##
  $search = '{if isset($comment_add)}';
  $add = file_get_contents(SUBSCRIBE_TO_PATH.'template/form_outside.tpl');
  $content = str_replace($search, $search.$add, $content);

  ## subscribe while add a comment ##
  $search = '{$comment_add.CONTENT}</textarea></p>';
  $add = file_get_contents(SUBSCRIBE_TO_PATH.'template/form_inside.tpl');
  $content = str_replace($search, $search.$add, $content);

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
