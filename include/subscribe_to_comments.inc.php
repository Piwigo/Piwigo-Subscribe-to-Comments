<?php
if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

/**
 * detect 'subscriptions' section and load page
 */
function stc_detect_section()
{
  global $tokens, $page;
  
  if ($tokens[0] == 'subscriptions')
  {
    $page['section'] = 'subscriptions';
  }
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
 * send notifications and/or add subscriber
 */
function stc_comment_insertion($comm)
{
  global $page, $template;
  
  $infos = $errors = array();
  
  if ($comm['action'] == 'validate')
  {
    send_comment_to_subscribers($comm);
  }
  
  if ( !empty($_POST['stc_check']) and ( $comm['action'] == 'validate' or $comm['action'] == 'moderate' ) )
  {
    if (isset($comm['image_id']))
    {
      $return = subscribe_to_comments($comm['image_id'], @$_POST['stc_mail'], 'image');
    }
    else if (isset($comm['category_id']))
    {
      $return = subscribe_to_comments($comm['category_id'], @$_POST['stc_mail'], 'category');
      
    }
    
    if (isset($return))
    {
      if ($return === 'confirm_mail')
      {
        array_push($infos, l10n('Please check your email inbox to confirm your subscription.'));
      }
      else if ($return === true)
      {
        array_push($infos, l10n('You have been added to the list of subscribers for this '.(isset($comm['image_id'])?'picture':'album').'.'));
      }
      else
      {
        array_push($errors, l10n('Invalid email adress, your are not subscribed to comments.'));
      }
      
      // messages management
      stc_add_messages($errors, $infos, true);
    }
  }
}

function stc_comment_validation($comm_ids, $type='image')
{
  if (!is_array($comm_ids)) $comm_ids = array($comm_ids);
  
  foreach($comm_ids as $comm_id)
  {
    if ($type == 'image')
    {
      $query = '
SELECT
    id,
    image_id,
    author,
    content
  FROM '.COMMENTS_TABLE.'
  WHERE id = '.$comm_id.'
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
  WHERE id = '.$comm_id.'
;';
    }
    
    $comm = pwg_db_fetch_assoc(pwg_query($query));
    send_comment_to_subscribers($comm);
  }
}


/**
 * add field and link on picture page
 */
function stc_on_picture()
{
  global $template, $picture, $page, $user;
  
  $infos = $errors = array();
  
  if (isset($_POST['stc_submit']))
  {
    $return = subscribe_to_comments($picture['current']['id'], @$_POST['stc_mail_stdl'], 'image');
    if ($return === 'confirm_mail')
    {
      array_push($infos, l10n('Please check your email inbox to confirm your subscription.'));
    }
    else if ($return === true)
    {
      array_push($infos, l10n('You have been added to the list of subscribers for this picture.'));
    }
    else
    {
      array_push($errors, l10n('Invalid email adress, your are not subscribed to comments.'));
    }
  }
  else if (isset($_GET['stc_unsubscribe']))
  {
    if (un_subscribe_to_comments($picture['current']['id'], null, 'image'))
    {
      array_push($infos, l10n('Successfully unsubscribed your email address from receiving notifications.'));
    }
  }
  
  // messages management
  stc_add_messages($errors, $infos);
  
  // if registered user with mail we check if already subscribed
  if ( !is_a_guest() and !empty($user['email']) )
  {
    $query = '
SELECT id
  FROM '.SUBSCRIBE_TO_TABLE.'
  WHERE
    email = "'.$user['email'].'"
    AND image_id = '.$picture['current']['id'].'
    AND validated = "true"
;';
    if (pwg_db_num_rows(pwg_query($query)))
    {
      $template->assign(array(
        'SUBSCRIBED' => true,
        'UNSUB_LINK' => add_url_params($picture['current']['url'], array('stc_unsubscribe'=>'1')),
        ));
    }
  }
  else
  {
    $template->assign('ASK_MAIL', true);
  }
  
  if ( $is_simple = strstr($user['theme'], 'simple') !== false or strstr($user['theme'], 'stripped') !== false )
    $template->set_prefilter('picture', 'stc_simple_prefilter');
  else
    $template->set_prefilter('picture', 'stc_main_prefilter');
}

/**
 * add field and on album page
 */
function stc_on_album()
{
  global $page, $template, $pwg_loaded_plugins, $user;
  
  $infos = $errors = array();
  
  if (
      script_basename() != 'index' or !isset($page['section']) or
      !isset($pwg_loaded_plugins['Comments_on_Albums']) or 
      $page['section'] != 'categories' or !isset($page['category'])
    )
  {
    return;
  }
  
  if (isset($_POST['stc_submit']))
  {
    $return = subscribe_to_comments($page['category']['id'], @$_POST['stc_mail_stdl'], 'category');
    if ($return === 'confirm_mail')
    {
      array_push($infos, l10n('Please check your email inbox to confirm your subscription.'));
    }
    else if ($return === true)
    {
      array_push($infos, l10n('You have been added to the list of subscribers for this album.'));
    }
    else
    {
      array_push($errors, l10n('Invalid email adress, your are not subscribed to comments.'));
    }
  }
  else if (isset($_GET['stc_unsubscribe']))
  {
    if (un_subscribe_to_comments($page['category']['id'], null, 'category'))
    {
      array_push($infos, l10n('Successfully unsubscribed your email address from receiving notifications.'));
    }
  }
  
  // messages management
  stc_add_messages($errors, $infos, true);
  
  // if registered user we check if already subscribed
  if ( !is_a_guest() and !empty($user['email']) )
  {
    $query = '
SELECT id
  FROM '.SUBSCRIBE_TO_TABLE.'
  WHERE
    email = "'.$user['email'].'"
    AND category_id = '.$page['category']['id'].'
    AND validated = "true"
;';
    if (pwg_db_num_rows(pwg_query($query)))
    {
      $url_params['section'] = 'categories';
      $url_params['category'] = $page['category'];
      $element_url = make_index_url($url_params);
      
      $template->assign(array(
        'SUBSCRIBED' => true,
        'UNSUB_LINK' => add_url_params($element_url, array('stc_unsubscribe'=>'1')),
        ));
    }
  }
  else
  {
    $template->assign('ASK_MAIL', true);
  }
  
  if ( $is_simple = strstr($user['theme'], 'simple') !== false or strstr($user['theme'], 'stripped') !== false )
    $template->set_prefilter('comments_on_albums', 'stc_simple_prefilter');
  else
    $template->set_prefilter('comments_on_albums', 'stc_main_prefilter');
}


/**
 * prefilter for common themes
 */
function stc_main_prefilter($content, &$smarty)
{  
  ## subscribe at any moment ##
  $search = '#\<\/div\>(.{0,10})\{\/if\}(.{0,10})\{\*comments\*\}#is';
  
  $replace = '
<form method="post" action="{$comment_add.F_ACTION}" class="filter" id="stc_standalone">
  <fieldset>
  {if $SUBSCRIBED == true}
    {\'You are currently subscribed to comments of this picture.\'|@translate}
    <a href="{$UNSUB_LINK}">{\'Unsubscribe\'|@translate}
  {else}
    <legend>{\'Subscribe without commenting\'|@translate}</legend>
    {if $ASK_MAIL == true}
      <label>{\'Email address\'|@translate} <input type="text" name="stc_mail_stdl"></label>
      <label><input type="submit" name="stc_submit" value="{\'Submit\'|@translate}"></label>
    {else}
      <label><input type="submit" name="stc_submit" value="{\'Subscribe\'|@translate}"></label>
    {/if}
  {/if}
  </fieldset>
</form>
</div>$1{/if}$2{*comments*}';

  $content = preg_replace($search, $replace, $content);
  
  ## subscribe while add a comment ##
  $search = '#<input type="hidden" name="key" value="{\$comment_add\.KEY}"([ /]*)>#';
  
  $replace = '
<input type="hidden" name="key" value="{$comment_add.KEY}"$1>
{if $SUBSCRIBED != true}
  <label>{\'Notify me of followup comments\'|@translate} <input type="checkbox" name="stc_check" value="1"></label><br>

  {if $ASK_MAIL == true}
    <label id="stc_mail" style="display:none;">{\'Email address\'|@translate} <input type="text" name="stc_mail"></label><br>
    {footer_script require="jquery"}{literal}
    jQuery(document).ready(function() {
      $("input[name=stc_check]").change(function() {
        if ($(this).is(":checked")) $("#stc_mail").css("display", "");
        else $("#stc_mail").css("display", "none");
      });
    });
    {/literal}{/footer_script}
  {/if}
{/if}';
  
  $content = preg_replace($search, $replace, $content);
  
  return $content;
}

/**
 * prefilter for simple/stripped themes
 */
function stc_simple_prefilter($content, &$smarty)
{  
  ## subscribe at any moment ##
  $search = '#\<\/div\>(.{0,10})\{\/if\}(.{0,10})\{if \!empty\(\$navbar\) \}\{include file\=\'navigation_bar.tpl\'\|\@get_extent:\'navbar\'\}\{\/if\}#is';
  
  $replace = '
<form method="post" action="{$comment_add.F_ACTION}" class="filter" id="stc_standalone">
  <fieldset>
  {if $SUBSRIBED == true}
    {\'You are currently subscribed to comments of this album.\'|@translate}
    <a href="{$UNSUB_LINK}">{\'Unsubscribe\'|@translate}
  {else}
    <legend>{\'Subscribe without commenting\'|@translate}</legend>
    {if $ASK_MAIL == true}
      <label>{\'Email address\'|@translate} <input type="text" name="stc_mail_stdl"></label>
      <label><input type="submit" name="stc_submit" value="{\'Submit\'|@translate}"></label>
    {else}
      <label><input type="submit" name="stc_submit" value="{\'Subscribe\'|@translate}"></label>
    {/if}
  {/if}
  </fieldset>
</form>
</div>$1{/if}$2{if !empty($navbar) }{include file=\'navigation_bar.tpl\'|@get_extent:\'navbar\'}{/if}';

  $content = preg_replace($search, $replace, $content);
  
  ## subscribe while add a comment ##
  $search = '#<input type="hidden" name="key" value="{\$comment_add\.KEY}"([ /]*)>#';
  
  $replace = '
<input type="hidden" name="key" value="{$comment_add.KEY}"$1>
{if $SUBSCRIBED != true}
  <label>{\'Notify me of followup comments\'|@translate} <input type="checkbox" name="stc_check" value="1"></label><br>

  {if $ASK_MAIL == true}
    <label id="stc_mail" style="display:none;">{\'Email address\'|@translate} <input type="text" name="stc_mail"></label><br>
    {footer_script require="jquery"}{literal}
    jQuery(document).ready(function() {
      $("input[name=stc_check]").change(function() {
        if ($(this).is(":checked")) $("#stc_mail").css("display", "");
        else $("#stc_mail").css("display", "none");
      });
    });
    {/literal}{/footer_script}
  {/if}
{/if}';
  
  $content = preg_replace($search, $replace, $content);
  
  return $content;
}

/**
 * add link to management page for registered users
 */
function stc_profile_link()
{
  global $template, $user;
  
  if (!empty($user['email']))
  {
    $template->set_prefilter('profile_content', 'stc_profile_link_prefilter');
  }
}

function stc_profile_link_prefilter($content, &$smarty)
{
  global $user;
  
  $search = '<p class="bottomButtons">';
  $replace = '<a href="'.make_stc_url('manage', $user['email']).'" title="{\'Manage my subscriptions to comments\'|@translate}" rel="nofollow">{\'Manage my subscriptions to comments\'|@translate}</a><br>';
  
  return str_replace($search, $search.$replace, $content);
}

/**
 * must overload messages because Piwigo is weird
 */
function stc_add_messages($errors, $infos, $prefilter=false)
{
  global $template;
  
  if (!empty($errors))
  {
    $errors_bak = $template->get_template_vars('errors');
    if (empty($errors_bak)) $errors_bak = array();
    $template->assign('errors', array_merge($errors_bak, $errors));
    if ($prefilter) $template->set_prefilter('index', 'coa_messages'); // here we use a prefilter existing in COA
  }
  if (!empty($infos))
  {
    $infos_bak = $template->get_template_vars('infos');
    if (empty($infos_bak)) $infos_bak = array();
    $template->assign('infos', array_merge($infos_bak, $infos));
    if ($prefilter) $template->set_prefilter('index', 'coa_messages');
  }
}
?>