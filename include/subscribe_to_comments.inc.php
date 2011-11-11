<?php
if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

/**
 * detect 'subscriptions' section and load page
 */
function stc_detect_section() {
  global $tokens, $page;
  
  if ($tokens[0] == 'subscriptions')
  {
    $page['section'] = 'subscriptions';
  }
}

function stc_load_section() {
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
      if (!empty($errors))
      {
        $errors_bak = $template->get_template_vars('errors');
        if (empty($errors_bak)) $errors_bak = array();
        $template->assign('errors', array_merge($errors_bak, $errors));
        $template->set_prefilter('index', 'coa_messages'); // here we use a prefilter existing in COA
      }
      if (!empty($infos))
      {
        $infos_bak = $template->get_template_vars('infos');
        if (empty($infos_bak)) $infos_bak = array();
        $template->assign('infos', array_merge($infos_bak, $infos));
        $template->set_prefilter('index', 'coa_messages');
      }
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
  global $template, $picture, $page;
  
  $infos = $array = array();
  
  if (isset($_POST['stc_check_stdl']))
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
  if (!empty($errors))
  {
    $errors_bak = $template->get_template_vars('errors');
    if (empty($errors_bak)) $errors_bak = array();
    $template->assign('errors', array_merge($errors_bak, $errors));
  }
  if (!empty($infos))
  {
    $infos_bak = $template->get_template_vars('infos');
    if (empty($infos_bak)) $infos_bak = array();
    $template->assign('infos', array_merge($infos_bak, $infos));
  }
  
  $template->set_prefilter('picture', 'stc_on_picture_prefilter');
}

function stc_on_picture_prefilter($template, &$smarty)
{
  global $user, $picture;  
  
  ## subscribe at any moment ##
  $search[1] = '{if isset($comment_add)}';
  
  $replace[1] = $search[1].'
<form method="post" action="{$comment_add.F_ACTION}" class="filter" id="stc_standalone">
  <fieldset>';
  
  // if registered user we check if already subscribed
  if (!is_a_guest())
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
      $replace[1].= '
    {\'You are currently subscribed to comments of this picture.\'|@translate}
    <a href="'.add_url_params($picture['current']['url'], array('stc_unsubscribe'=>'1')).'">{\'Unsubscribe\'|@translate}';
      $no_form = true;
    }
  }
  
  if (!isset($no_form))
  {
    $replace[1].= '
    <label><a href="#" id="stc_check_stdl">{\'Subscribe to new comments\'|@translate}</a> <input type="checkbox" name="stc_check_stdl" value="1" style="display:none;"></label>';
    
    // form for guests
    if (is_a_guest())
    {
      $replace[1].= ' 
      <label style="display:none;">{\'Email address\'|@translate} <input type="text" name="stc_mail_stdl"></label>
      <label style="display:none;"><input type="submit" value="{\'Submit\'|@translate}"></label>
    {footer_script require="jquery"}{literal}
    jQuery(document).ready(function() {
      $("a#stc_check_stdl").click(function() {
        $("input[name=stc_check_stdl]").prop("checked", true);
        $("#stc_standalone label").toggle();
        return false;
      });
    });
    {/literal}{/footer_script}';
    }
    // simple link for registered users
    else
    {
      $replace[1].= '
    {footer_script require="jquery"}{literal}
    jQuery(document).ready(function() {
      $("a#stc_check_stdl").click(function() {
        $("input[name=stc_check_stdl]").prop("checked", true);
        $(this).parents("form#stc_standalone").submit();
        return false;
      });
    });
    {/literal}{/footer_script}';
    }
  }
      
  $replace[1].= '
  </fieldset>
</form>';


  ## subscribe while add a comment ##
  $search[0] = '<input type="submit" value="{\'Submit\'|@translate}">';
  $replace[0] = null;
  
  if (!isset($no_form))
  {
    $replace[0].= '
<label>{\'Subscribe to new comments\'|@translate} <input type="checkbox" name="stc_check" value="1"></label>';
  }
  if (is_a_guest())
  {
    $replace[0].= ' 
<label id="stc_mail" style="display:none;">{\'Email address\'|@translate} <input type="text" name="stc_mail"></label>
{footer_script require="jquery"}{literal}
jQuery(document).ready(function() {
  $("input[name=stc_check]").change(function() {
    if ($(this).is(":checked")) $("#stc_mail").css("display", "");
    else $("#stc_mail").css("display", "none");
  });
});
{/literal}{/footer_script}';
  }
  $replace[0].= $search[0];
  
  return str_replace($search, $replace, $template);
}


/**
 * add field and on album page
 */
function stc_on_album()
{
  global $page, $template, $pwg_loaded_plugins;
  
  $infos = $errors = array();
  
  if (
      script_basename() != 'index' or !isset($page['section']) or
      !isset($pwg_loaded_plugins['Comments_on_Albums']) or 
      $page['section'] != 'categories' or !isset($page['category'])
    )
  {
    return;
  }
  
  if (isset($_POST['stc_check_stdl']))
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
  if (!empty($errors))
  {
    $errors_bak = $template->get_template_vars('errors');
    if (empty($errors_bak)) $errors_bak = array();
    $template->assign('errors', array_merge($errors_bak, $errors));
    $template->set_prefilter('index', 'coa_messages'); // here we use a prefilter existing in COA
  }
  if (!empty($infos))
  {
    $infos_bak = $template->get_template_vars('infos');
    if (empty($infos_bak)) $infos_bak = array();
    $template->assign('infos', array_merge($infos_bak, $infos));
    $template->set_prefilter('index', 'coa_messages');
  }
  
  $template->set_prefilter('comments_on_albums', 'stc_on_album_prefilter');
}

function stc_on_album_prefilter($template, &$smarty)
{
  global $user, $page;  
  
  ## subscribe at any moment ##
  $search[1] = '{if isset($comment_add)}';
  
  $replace[1] = $search[1].'
<form method="post" action="{$comment_add.F_ACTION}" class="filter" id="stc_standalone">
  <fieldset>';
  
  // if registered user we check if already subscribed
  if (!is_a_guest())
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
    
      $replace[1].= '
    {\'You are currently subscribed to comments of this album.\'|@translate}
    <a href="'.add_url_params($element_url, array('stc_unsubscribe'=>'1')).'">{\'Unsubscribe\'|@translate}';
      $no_form = true;
    }
  }
  
  if (!isset($no_form))
  {
    $replace[1].= '
    <label><a href="#" id="stc_check_stdl">{\'Subscribe to new comments\'|@translate}</a> <input type="checkbox" name="stc_check_stdl" value="1" style="display:none;"></label>';
    
    // form for guests
    if (is_a_guest())
    {
      $replace[1].= ' 
      <label style="display:none;">{\'Email address\'|@translate} <input type="text" name="stc_mail_stdl"></label>
      <label style="display:none;"><input type="submit" value="{\'Submit\'|@translate}"></label>
    {footer_script require="jquery"}{literal}
    jQuery(document).ready(function() {
      $("a#stc_check_stdl").click(function() {
        $("input[name=stc_check_stdl]").prop("checked", true);
        $("#stc_standalone label").toggle();
        return false;
      });
    });
    {/literal}{/footer_script}';
    }
    // simple link for registered users
    else
    {
      $replace[1].= '
    {footer_script require="jquery"}{literal}
    jQuery(document).ready(function() {
      $("a#stc_check_stdl").click(function() {
        $("input[name=stc_check_stdl]").prop("checked", true);
        $(this).parents("form#stc_standalone").submit();
        return false;
      });
    });
    {/literal}{/footer_script}';
    }
  }
      
  $replace[1].= '
  </fieldset>
</form>';


  ## subscribe while add a comment ##
  $search[0] = '<input type="submit" value="{\'Submit\'|@translate}">';
  $replace[0] = null;
  
  if (!isset($no_form))
  {
    $replace[0].= '
<label>{\'Subscribe to new comments\'|@translate} <input type="checkbox" name="stc_check" value="1"></label>';
  }
  if (is_a_guest())
  {
    $replace[0].= ' 
<label id="stc_mail" style="display:none;">{\'Email address\'|@translate} <input type="text" name="stc_mail"></label>
{footer_script require="jquery"}{literal}
jQuery(document).ready(function() {
  $("input[name=stc_check]").change(function() {
    if ($(this).is(":checked")) $("#stc_mail").css("display", "");
    else $("#stc_mail").css("display", "none");
  });
});
{/literal}{/footer_script}';
  }
  $replace[0].= $search[0];

  return str_replace($search, $replace, $template);
}


/**
 * add link to management page for registered users
 */
function stc_menubar_apply($menu_ref_arr)
{
  global $template;
  $menu = &$menu_ref_arr[0];
  
  if ( !is_a_guest() and ($block = $menu->get_block('mbIdentification')) != null )
  {
    $template->set_prefilter('menubar', 'stc_menubar_apply_prefilter'); 
  }
}

function stc_menubar_apply_prefilter($content, &$smarty)
{
  global $user;
  
  $search = '{if isset($U_REGISTER)}';
  $replace = '<li><a href="'.make_stc_url('manage', $user['email']).'" title="{\'Manage my subscriptions\'|@translate}" rel="nofollow">{\'Manage my subscriptions\'|@translate}</a></li>';
  return str_replace($search, $replace.$search, $content);
}
?>