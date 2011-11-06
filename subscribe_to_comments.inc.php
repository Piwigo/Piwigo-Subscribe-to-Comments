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
  global $page;
  
  if ($comm['action'] == 'validate')
  {
    send_comment_to_subscribers($comm);
  }
  
  if ( !empty($_POST['stc_check']) and ( $comm['action'] == 'validate' or $comm['action'] == 'moderate' ) )
  {
    if (isset($comm['image_id']))
    {
      subscribe_to_comments($comm['image_id'], @$_POST['stc_mail'], 'image');
    }
    else if (isset($comm['category_id']))
    {
      subscribe_to_comments($comm['category_id'], @$_POST['stc_mail'], 'category');
    }
  }
}

function stc_comment_validation($comm_id, $type='image')
{ 
  switch ($type)
  {
    case 'image':
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
      break;
    }
    
    case 'category':
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
      break;
    }
  }
  
  $comm = pwg_db_fetch_assoc(pwg_query($query));
  send_comment_to_subscribers($comm);
}


/**
 * adds fields on picture page
 */
function stc_on_picture()
{
  global $template;
  
  $template->set_prefilter('picture', 'stc_on_picture_prefilter');
}

function stc_on_picture_prefilter($template, &$smarty)
{
  $search = '<input type="submit" value="{\'Submit\'|@translate}">';
  
  $replace = '
<label>{\'Subscribe to new comments\'|@translate} <input type="checkbox" name="stc_check" value="1"></label>
<label id="stc_mail" style="display:none;">{\'Email address\'|@translate} <input type="text" name="stc_mail"></label>
{footer_script require="jquery"}{literal}
jQuery(document).ready(function() {
  $("input[name=stc_check]").change(function() {
    if ($(this).is(":checked")) $("#stc_mail").css("display", "");
    else $("#stc_mail").css("display", "none");
  });
});
{/literal}
{/footer_script}
'.$search;

  return str_replace($search, $replace, $template);
}

?>