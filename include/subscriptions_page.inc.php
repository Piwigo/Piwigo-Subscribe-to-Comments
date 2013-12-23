<?php
defined('SUBSCRIBE_TO_PATH') or die('Hacking attempt!');

global $template, $conf, $page, $user;

// check input parameters
if (empty($_GET['action']) or empty($_GET['email']) or empty($_GET['key']))
{
  $_GET['action'] = null;
}
else
{
  $verif_key = $_GET['action'].$_GET['email'].(isset($_GET['id'])?$_GET['id']:null);

  if (decrypt_value($_GET['key'], $conf['secret_key']) !== $verif_key)
  {
    $_GET['action'] = null;
  }
}



if (!empty($_GET['action']))
{
  // unsubscribe all
  if (isset($_POST['unsubscribe_all']) and isset($_POST['unsubscribe_all_check']))
  {
    $query = '
DELETE FROM '.SUBSCRIBE_TO_TABLE.'
  WHERE email = "'.$_GET['email'].'"
;';
    pwg_query($query);
  }

  // bulk action
  else if (isset($_POST['apply_bulk']) and !empty($_POST['selected']))
  {
    switch ($_POST['action'])
    {
      case 'unsubscribe':
        un_subscribe_to_comments($_GET['email'], $_POST['selected']);
        break;
      case 'validate':
        validate_subscriptions($_GET['email'], $_POST['selected']);
        break;
    }
  }

  // unsubscribe from manage page
  else if (isset($_GET['unsubscribe']))
  {
    if (un_subscribe_to_comments($_GET['email'], $_GET['unsubscribe']))
    {
      $page['infos'][] = l10n('Successfully unsubscribed your email address from receiving notifications.');
    }
    else
    {
      $page['errors'][] = l10n('Not found.');
    }
  }

  // validate from manage page
  else if (isset($_GET['validate']))
  {
    if (validate_subscriptions($_GET['email'], $_GET['validate']))
    {
      $page['infos'][] = l10n('Your subscribtion has been validated, thanks you.');
    }
    else
    {
      $page['infos'][] = l10n('Already validated.');
    }
  }

  $template->assign('MANAGE_LINK', make_stc_url('manage', $_GET['email']));
}


switch ($_GET['action'])
{
  /* validate */
  case 'validate':
  {
    // don't need to sanitize inputs, already checked with the unique key
    $query = '
SELECT type, element_id
  FROM '.SUBSCRIBE_TO_TABLE.'
  WHERE
    email = "'.$_GET['email'].'"
    AND id = '.$_GET['id'].'
;';
    $result = pwg_query($query);

    if (!pwg_db_num_rows($result))
    {
      $page['errors'][] = l10n('Not found.');
    }
    else
    {
      if (validate_subscriptions($_GET['email'], $_GET['id']))
      {
        $page['infos'][] = l10n('Your subscription has been validated, thanks you.');
      }
      else
      {
        $page['infos'][] = l10n('Already validated.');
      }

      list($type, $element_id) = pwg_db_fetch_row($result);

      switch ($type)
      {
        case 'image':
          $element = get_picture_infos($element_id, false);
          break;
        case 'album-images':
        case 'album':
          $element = get_category_infos($element_id, false);
          break;
        default:
          $element = null;
      }

      $template->assign(array(
        'type' => $type,
        'element' => $element,
        ));
    }

    $template->assign('IN_VALIDATE', true);
    break;
  }

  /* unsubscribe */
  case 'unsubscribe':
  {
    $query = '
SELECT
    type,
    element_id
  FROM '.SUBSCRIBE_TO_TABLE.'
  WHERE
    email = "'.$_GET['email'].'"
    AND id = '.$_GET['id'].'
;';
    $result = pwg_query($query);

    if (!pwg_db_num_rows($result))
    {
      $page['errors'][] = l10n('Not found.');
    }
    else
    {
      if (un_subscribe_to_comments($_GET['email'], $_GET['id']))
      {
        $page['infos'][] = l10n('Successfully unsubscribed your email address from receiving notifications.');
      }
      else
      {
        $page['errors'][] = l10n('Not found.');
      }

      list($type, $element_id) = pwg_db_fetch_row($result);

      switch ($type)
      {
        case 'image':
          $element = get_picture_infos($element_id);
          break;
        case 'album-images':
        case 'album':
          $element = get_category_infos($element_id);
          break;
        default:
          $element = null;
      }

      $template->assign(array(
        'type' => $type,
        'element' => $element,
        ));
    }

    $template->assign('IN_UNSUBSCRIBE', true);
    break;
  }

  /* manage */
  case 'manage':
  {
    $query = '
SELECT *
  FROM '.SUBSCRIBE_TO_TABLE.'
  WHERE email = "'.$_GET['email'].'"
  ORDER BY registration_date DESC
;';
    $result = pwg_query($query);

    if (pwg_db_num_rows($result))
    {
      while ($subscription = pwg_db_fetch_assoc($result))
      {
        $subscription['registration_date'] = format_date($subscription['registration_date'], true);

        switch ($subscription['type'])
        {
          case 'image':
            $subscription['infos'] = get_picture_infos($subscription['element_id']);
            break;
          case 'album-images':
          case 'album':
            $subscription['infos'] = get_category_infos($subscription['element_id'], true, $user['id']);
            break;
          default:
            $subscription['infos'] = null;
            $template->append('global_subscriptions', $subscription);
            continue(2);
        }

        $template->append('subscriptions', $subscription);
      }
    }
    else
    {
      $page['infos'][] = l10n('You are not subscribed to any comment.');
    }
    break;
  }

  default:
  {
    set_status_header(403);
    $page['errors'][] = l10n('Bad query');
  }
}


$template->assign(array(
  'SUBSCRIBE_TO_PATH' => SUBSCRIBE_TO_PATH,
  'SUBSCRIBE_TO_ABS_PATH' => realpath(SUBSCRIBE_TO_PATH).'/',
  'COA_ACTIVATED' => defined('COA_ID'),
  ));

if (!empty($_GET['email']))
{
  $template->concat('TITLE', $conf['level_separator'] . l10n('Subscriptions of %s', '<i>'.$_GET['email'].'</i>'));
}

$template->set_filename('subscribe_to_comments', realpath(SUBSCRIBE_TO_PATH . 'template/subscriptions_page.tpl'));
$template->assign_var_from_handle('CONTENT', 'subscribe_to_comments');
