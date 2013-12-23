{combine_css path=$SUBSCRIBE_TO_PATH|cat:'template/style.css'}


{if $IN_VALIDATE or $IN_UNSUBSCRIBE}
<p>
  {if !empty($element)}<a href="{$element.url}" title="{$element.name}">{'Return to item page'|@translate}</a><br>{/if}
  <a href="{$MANAGE_LINK}">{'Manage my subscriptions'|@translate}</a>
</p>

{else}
<form action="{$MANAGE_LINK}" method="post">
  {if !empty($global_subscriptions)}
  <fieldset>
    <legend>{'Global subscriptions'|@translate}</legend>
    <table class="subscriptions_list">
      {foreach from=$global_subscriptions item=sub name=subs_loop}
      <tr class="{if $smarty.foreach.subs_loop.index is odd}row1{else}row2{/if}">
        <td>
          {if $sub.type == 'all-images'}
            {assign var=str value='all pictures of the gallery'|@translate}
            <img src="{$ROOT_URL}{$SUBSCRIBE_TO_PATH}template/image.png">
          {else $sub.type == 'all-albums'}
            {assign var=str value='all albums of the gallery'|@translate}
            <img src="{$ROOT_URL}{$SUBSCRIBE_TO_PATH}template/album.png">
          {/if}
          {'You are currently subscribed to comments on %s.'|@translate|sprintf:$str}
        </td>
        <td style="white-space:nowrap;">
          <div class="actions">
            <a href="{$MANAGE_LINK}&amp;unsubscribe={$sub.id}" class="unsub">{'Unsubscribe'|@translate}</a>
            {if $sub.validated == 'false'}<br> <a href="{$MANAGE_LINK}&amp;validate={$sub.id}">{'Validate'|@translate}</a>{/if}
          </div>
        </td>
        <td style="white-space:nowrap;">
          <i>{$sub.registration_date}</i>
        </td>
      </tr>
      {/foreach}
    </table>
  </fieldset>
  {/if}

  {if !empty($subscriptions)}
  <fieldset>
    <legend>{'Manage my subscriptions'|@translate}</legend>
    <table class="subscriptions_list">
      <tr class="header">
        <th class="chkb"><input type="checkbox" id="check_all"></th>
        <th colspan="2" class="info">{'Subject'|@translate}</th>
        <th class="date">{'Followed on'|@translate}</th>
      </tr>

      {foreach from=$subscriptions item=sub name=subs_loop}
      <tr class="{if $smarty.foreach.subs_loop.index is odd}row1{else}row2{/if} {if $sub.validated == 'false'}not-validated{/if}">
        <td class="chkb"><input type="checkbox" name="selected[]" value="{$sub.id}" id="sub-{$sub.id}"></td>
        <td class="thumb"><label for="sub-{$sub.id}"><img src="{$sub.infos.thumbnail}" alt="{$sub.infos.name}" class="thumbnail"></label></td>
        <td class="info">
          <img src="{$ROOT_URL}{$SUBSCRIBE_TO_PATH}template/{$sub.type}.png">
          <b><a href="{$sub.infos.url}">{$sub.infos.name}</a></b>

          <div class="actions">
            <a href="{$MANAGE_LINK}&amp;unsubscribe={$sub.id}" class="unsub">{'Unsubscribe'|@translate}</a>
            {if $sub.validated == 'false'}| <a href="{$MANAGE_LINK}&amp;validate={$sub.id}">{'Validate'|@translate}</a>{/if}
          </div>
        </td>
        <td class="date">
          <i>{$sub.registration_date}</i>
        </td>
      </tr>
      {/foreach}

      <tr class="footer {if $smarty.foreach.subs_loop.index is odd}row1{else}row2{/if}"><td colspan="4">
        <select name="action">
          <option value="-1">{'Choose an action'|@translate}</option>
          <option value="unsubscribe">{'Unsubscribe'|@translate}</option>
          <option value="validate">{'Validate'|@translate}</option>
        </select>
        <input type="submit" name="apply_bulk" value="{'Apply action'|@translate}">
      </td></tr>
    </table>

    <p>
      <img src="{$ROOT_URL}{$SUBSCRIBE_TO_PATH}template/image.png"> {'comments on a picture'|@translate}.
      <img src="{$ROOT_URL}{$SUBSCRIBE_TO_PATH}template/album-images.png"> {'comments on all pictures of an album'|@translate}.
      {if $COA_ACTIVATED}<img src="{$ROOT_URL}{$SUBSCRIBE_TO_PATH}template/album.png"> {'comments on an album'|@translate}.{/if}
    </p>
  </fieldset>
  {/if}

  {if !empty($global_subscriptions) or !empty($subscriptions)}
    <p>
      <label><input type="checkbox" name="unsubscribe_all_check" value="1"> {'Unsubscribe from all email notifications'|@translate}</label>
      <input type="submit" name="unsubscribe_all" value="{'Submit'|@translate}">
    </p>
  {/if}
</form>

{footer_script require="jquery"}
jQuery("#check_all").change(function() {
  if (jQuery(this).is(":checked")) {
    jQuery("input[name^='selected']").attr('checked', 'checked');
  }
  else {
    jQuery("input[name^='selected']").removeAttr('checked');
  }
});
{/footer_script}

{/if}