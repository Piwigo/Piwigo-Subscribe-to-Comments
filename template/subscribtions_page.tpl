{combine_css path=$SUBSCRIBE_TO_PATH|@cat:'template/style.css'}

{$MENUBAR}

{if !empty($PLUGIN_INDEX_CONTENT_BEFORE)}{$PLUGIN_INDEX_CONTENT_BEFORE}{/if}

<div id="content" class="content">
  <div class="titrePage">
    <ul class="categoryActions">
      {if !empty($PLUGIN_INDEX_ACTIONS)}{$PLUGIN_INDEX_ACTIONS}{/if}
    </ul>
    <h2>{'Subscriptions of'|@translate} <i>{$EMAIL}</i></h2>
  </div> <!-- titrePage -->
  
  {if !empty($errors)}
  <div class="errors">
    <ul>
      {foreach from=$errors item=error}
      <li>{$error}</li>
      {/foreach}
    </ul>
  </div>
  {/if}
  {if !empty($infos)}
  <div class="infos">
    <ul>
      {foreach from=$infos item=info}
      <li>{$info}</li>
      {/foreach}
    </ul>
  </div>
  {/if}
  
  {if !empty($unsubscribe_form)}
  <form action="" method="post">
  <fieldset>
    <legend>{'Unsubscribe from email notification'|@translate}</legend>
    
    <p>
      <label><input type="radio" name="unsubscribe" value="{$unsubscribe_form}" checked="checked"> {'Only unsubscribe notifications for comments from:'|@translate} <a href="{$element.url}" target="_blank">{$element.name}</a></label>
      <label><input type="radio" name="unsubscribe" value="all"> {'Unsubscribe from all email notifications'|@translate}</label>
      <br>
      <label><input type="submit" value="Unsubscribe notifications for {$EMAIL}"></label>
      <a href="{$MANAGE_LINK}">{'Manage my subscriptions'|@translate}</a>
    </p>
  </fieldset>
  </form>
  {/if}
  
  {if !empty($validate)}
  <p>
    {if empty($errors)}<a href="{$element.url}">{'Return to item page'|@translate}</a><br>{/if}
    <a href="{$MANAGE_LINK}">{'Manage my subscriptions'|@translate}</a>
  </p>
  {/if}
  
  {if !empty($subscriptions) and $subscriptions != 'none'}
  <form action="{$MANAGE_LINK}" method="post">
  <fieldset>
    <legend>{'Manage my subscriptions'|@translate}</legend>
    <table class="subscriptions_list">
      <tr class="throw">
        <th>{'Item'|@translate}</th>
        <th>{'Date'|@translate}</th>
        <th>{'Unsubscribe'|@translate}</th>
      </tr>
      {foreach from=$subscriptions item=sub name=subs_loop}
      <tr class="{if $smarty.foreach.subs_loop.index is odd}row1{else}row2{/if}">
        <td>
          {if $sub.type == 'image'}
          <img src="{$SUBSCRIBE_TO_PATH}template/picture.png" alt="(P)">
          {else}
          <img src="{$SUBSCRIBE_TO_PATH}template/folder_picture.png" alt="(A)">
          {/if} 
          <a href="{$sub.infos.url}">{$sub.infos.name}</a>
        </td>
        <td>{$sub.registration_date}</td>
        <td><a href="{$MANAGE_LINK}&amp;unsubscribe={$sub.id}">{'Unsubscribe'|@translate}</a></td>
      </tr>
      {/foreach}
    </table>
    
    <p>
      <input type="hidden" name="unsubscribe" value="all">
      <input type="submit" value="{'Unsubscribe from all email notifications'|@translate}">
    </p>
  </fieldset>
  {elseif !empty($subscriptions) and $subscriptions == 'none'}
  <p>
    {'You are not subscribed to any comment.'|@translate}
  </p>
  {/if}

</div> <!-- content -->

{if !empty($PLUGIN_INDEX_CONTENT_AFTER)}{$PLUGIN_INDEX_CONTENT_AFTER}{/if}