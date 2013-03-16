{combine_css path=$SUBSCRIBE_TO_PATH|@cat:"admin/template/style.css"}

<div class="titrePage">
	<h2>Subscribe to Comments</h2>
</div>

<form class="filter" method="post" name="filter" action="{$F_FILTER_ACTION}">
<fieldset>
  <legend>{'Filter'|@translate}</legend>

  <label>
    {'User'|@translate}
    <input type="text" name="username" value="{$F_USERNAME}" size="30">
  </label>

  <label>
    {'Sort by'|@translate}
    {html_options name=order_by options=$order_options selected=$order_selected}
  </label>

  <label>
    {'Sort order'|@translate}
    {html_options name=direction options=$direction_options selected=$direction_selected}
  </label>

  <label>
    &nbsp;
    <span><input class="submit" type="submit" name="filter" value="{'Submit'|@translate}"> <a href="{$F_FILTER_ACTION}">{'Reset'|@translate}</a></span>
  </label>

</fieldset>

</form>

<table class="table2" width="97%">
  <thead>
    <tr class="throw">
      <td class="user">{'User'|@translate}</td>
      <td class="date">{'First subscription'|@translate}</td>
      <td class="date">{'Last subscription'|@translate}</td>
      <td class="total">{'Photos'|@translate}</td>
      <td class="total" style="font-size:0.7em;">{'All album photos'|@translate}</td>
      {if $COA_ACTIVATED}<td class="total">{'Albums'|@translate}</td>{/if}
      <td class="action">&nbsp;</td>
    </tr>
  </thead>

  {foreach from=$USERS item=user name=users_loop}
  <tr class="{if $smarty.foreach.users_loop.index is odd}row1{else}row2{/if}">
    <td><a href="{$user.url}">{if $user.username}{$user.username} &#9733;{else}{$user.email}{/if}</a></td>
    <td style="text-align:center;">{$user.nice_min_date}</td>
    <td style="text-align:center;">{$user.nice_max_date}</td>
  {if $user.subs.all_images}
    <td colspan="2" class="all">{'All'|@translate}
  {else}
    <td>{$user.subs.image}</td>
    <td>{$user.subs.album_images}</td>
  {/if}
  {if $user.subs.all_albums && $COA_ACTIVATED}
    <td class="all">{'All'|@translate}
  {elseif $COA_ACTIVATED}
    <td>{$user.subs.album}</td>
  {/if}
    <td><a href="{$user.u_delete}" title="{'delete'|@translate}" onclick="return confirm('{'Are you sure?'|@translate}');"><img src="{$ROOT_URL}{$themeconf.admin_icon_dir}/delete.png" class="button" alt="{'delete'|@translate}"></a>
  </tr>
  {/foreach}
  
  {if not $USERS}
  <tr class="row2">
    <td colspan="6" style="text-align:center;font-style:italic;">{'No result'|@translate}</td>
  </tr>
  {/if}
</table>

<p>{'&#9733; : registered users'|@translate}</p>