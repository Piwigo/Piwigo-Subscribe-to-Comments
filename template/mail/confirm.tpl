<div id="the_header">
{'Subscribe to comments on'|@translate} <b>{$STC.GALLERY_TITLE}</b>
</div>

<div id="the_content">

<p>{'You requested to subscribe by email to comments on'|@translate} {$STC.element.on}.</p>

{if not empty($STC.element.thumbnail)}
<p id="the_image"><img src="{$STC.element.thumbnail}" alt="{$STC.element.name}"></p>
{/if}

<p>{'To activate, click the confirm button. If you believe this is an error, please just ignore this message.'|@translate}</p>

<p><a href="{$STC.VALIDATE_URL}" class="button">{'Confirm subscription'|@translate}</a></p>

<p>{'Want to edit your notifications options?'|@translate} <a href="{$STC.MANAGE_URL}">{'Manage my subscribtions'|@translate}</a>.</p>

</div>