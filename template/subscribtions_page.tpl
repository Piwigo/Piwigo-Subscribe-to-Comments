{$MENUBAR}

{if !empty($PLUGIN_INDEX_CONTENT_BEFORE)}{$PLUGIN_INDEX_CONTENT_BEFORE}{/if}

<div id="content" class="content">
  <div class="titrePage">
    <ul class="categoryActions">
      {if isset($U_EDIT)}
      <li><a href="{$U_EDIT}" title="{'edit'|@translate}" class="pwg-state-default pwg-button">
        <span class="pwg-icon pwg-icon-category-edit"> </span><span class="pwg-button-text">{'edit'|@translate}</span>
      </a></li>
      {/if}
      {if !empty($PLUGIN_INDEX_ACTIONS)}{$PLUGIN_INDEX_ACTIONS}{/if}
    </ul>
    <h2>Subscriptions</h2>
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

</div> <!-- content -->

{if !empty($PLUGIN_INDEX_CONTENT_AFTER)}{$PLUGIN_INDEX_CONTENT_AFTER}{/if}