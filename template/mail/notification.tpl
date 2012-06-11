<div id="the_header">
{'New comment on'|@translate} <b>{$STC.GALLERY_TITLE}</b>
</div>

<div id="the_content">

<p id="the_image">
  <a href="{$STC.element.url}">{$STC.element.name}<br>
  <img src="{$STC.element.thumbnail}" alt="{$STC.element.name}"></a>
</p>

<p>
  {$STC.comment.caption}:
  <blockquote>{$STC.comment.content}</blockquote>
</p>

<p>
  <a href="{$STC.UNSUB_URL}">{'Stop receiving notifications'|@translate}</a><br>
  <a href="{$STC.MANAGE_URL}">{'Manage my subscribtions'|@translate}</a>
</p>

</div>