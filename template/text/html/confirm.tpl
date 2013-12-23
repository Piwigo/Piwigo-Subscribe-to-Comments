<style>
.button {
  padding:8px 15px;
  background:#A80D24;
  color:#bbb;
  border:1px solid #CE2E5A;
  text-decoration:none;
  font-size:14px;
  font-weight:bold;
}
</style>

<p>{'You requested to subscribe by email to comments on %s'|translate:$ELEMENT.on}.</p>

{if not empty($ELEMENT.thumbnail)}
<p><img src="{$ELEMENT.thumbnail}" alt="{$ELEMENT.name}" class="photo"></p>
{/if}

<p>{'To activate, click the confirm button. If you believe this is an error, please just ignore this message.'|translate}</p>

<p><a href="{$VALIDATE_URL}" class="button">{'Confirm subscription'|translate}</a></p>

<p>{'Want to edit your notifications options?'|translate} <a href="{$MANAGE_URL}">{'Manage my subscriptions'|translate}</a>.</p>