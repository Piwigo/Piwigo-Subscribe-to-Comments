<?php
defined('SUBSCRIBE_TO_PATH') or die('Hacking attempt!');

if (isset($_GET['delete']))
{
  $query = '
DELETE FROM '.SUBSCRIBE_TO_TABLE.'
  WHERE email = "'.$_GET['delete'].'"
;';
  pwg_query($query);
}

// get subscriptions
$query = '
SELECT
    MIN(registration_date) AS min_date,
    MAX(registration_date) AS max_date,
    email
  FROM '.SUBSCRIBE_TO_TABLE.'
  GROUP BY email
;';
$dates = hash_from_query($query, 'email');

$query = '
SELECT
    sub.email,
    sub.type,
    u.'.$conf['user_fields']['username'].' AS username
  FROM '.SUBSCRIBE_TO_TABLE.' AS sub
    LEFT JOIN '.USERS_TABLE.' AS u
    ON u.'.$conf['user_fields']['email'].' = sub.email';
if (!empty($_POST['username']))
{
  $_POST['username'] = strtolower($_POST['username']);
  $query.= '
  WHERE LOWER(username) LIKE "%'.$_POST['username'].'%" OR LOWER(email) LIKE "%'.$_POST['username'].'%"';
}
  $query.= '
  ORDER BY email ASC
;';
$result = pwg_query($query);

$users = array();
while ($row = pwg_db_fetch_assoc($result))
{
  if (empty($users[ $row['email'] ]))
  {
    $users[ $row['email'] ] = array(
      'email' => $row['email'],
      'username' => $row['username'],
      'url' => make_stc_url('manage', $row['email']),
      'min_date' => strtotime($dates[ $row['email'] ]['min_date']),
      'max_date' => strtotime($dates[ $row['email'] ]['max_date']),
      'nice_min_date' => format_date($dates[ $row['email'] ]['min_date']),
      'nice_max_date' => format_date($dates[ $row['email'] ]['max_date']),
      'u_delete' => SUBSCRIBE_TO_ADMIN . '-subscriptions&amp;delete=' . $row['email'],
      'subs' => array(
        'image' => 0,
        'album_images' => 0,
        'all_images' => 0,
        'album' => 0,
        'all_albums' => 0
        ),
      );
  }

  $row['type'] = str_replace('-', '_', $row['type']); // fields are accessed in Smarty, incompatible with keys containing a '-'
  $users[ $row['email'] ]['subs'][ $row['type'] ]++;
}


// sort results
if (count($users))
{
  if (isset($_POST['filter']))
  {
    uasort($users, 'stc_sort_'.$_POST['order_by']);

    if ($_POST['direction'] == 'DESC')
    {
      $users = array_reverse($users);
    }
  }
  else
  {
    uasort($users, 'stc_sort_user');
  }
}


// filter options
$page['order_by_items'] = array(
  'user' => l10n('User'),
  'min_date' => l10n('First subscription'),
  'max_date' => l10n('Last subscription'),
  'image' => l10n('Photos'),
  'album_images' => l10n('All album photos'),
  'album' => l10n('Albums'),
  );

$page['direction_items'] = array(
  'ASC' => l10n('ascending'),
  'DESC' => l10n('descending'),
  );

$template->assign(array(
  'order_options' => $page['order_by_items'],
  'order_selected' => isset($_POST['order_by']) ? $_POST['order_by'] : 'user',
  'direction_options' => $page['direction_items'],
  'direction_selected' => isset($_POST['direction']) ? $_POST['direction'] : 'ASC',

  'F_USERNAME' => @htmlentities($_POST['username'], ENT_COMPAT, 'UTF-8'),
  'F_FILTER_ACTION' => SUBSCRIBE_TO_ADMIN . '-subscriptions',

  'USERS' => $users,
  'COA_ACTIVATED' => defined('COA_ID'),
  ));

$template->set_filename('stc_admin', realpath(SUBSCRIBE_TO_PATH . 'admin/template/subscriptions.tpl'));



function stc_sort_image($a, $b)
{
  if ($a['subs']['all_images']) return 1;
  if ($b['subs']['all_images']) return -1;
  return $a['subs']['image'] - $b['subs']['image'];
}

function stc_sort_album_images($a, $b)
{
  if ($a['subs']['all_images']) return 1;
  if ($b['subs']['all_images']) return -1;
  return $a['subs']['album_images'] - $b['subs']['album_images'];
}

function stc_sort_album($a, $b)
{
  if ($a['subs']['all_albums']) return 1;
  if ($b['subs']['all_albums']) return -1;
  return $a['subs']['album'] - $b['subs']['album'];
}

function stc_sort_min_date($a, $b)
{
  return $a['min_date'] - $b['min_date'];
}

function stc_sort_max_date($a, $b)
{
  return $a['max_date'] - $b['max_date'];
}

function stc_sort_user($a, $b)
{
  return strcasecmp($a['username'].$a['email'], $b['username'].$b['email']);
}
