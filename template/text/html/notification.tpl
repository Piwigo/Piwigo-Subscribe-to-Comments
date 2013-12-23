<p>
  <a href="{$ELEMENT.url}">{$ELEMENT.name}<br>
  <img src="{$ELEMENT.thumbnail}" alt="{$ELEMENT.name}" class="photo"></a>
</p>

<p>
  {'<b>%s</b> wrote on <i>%s</i>'|translate:$COMMENT.author:$COMMENT.date} :
  <blockquote>{$COMMENT.content}</blockquote>
</p>

<p>
  <a href="{$UNSUB_URL}">{'Stop receiving notifications'|translate}</a><br>
  <a href="{$MANAGE_URL}">{'Manage my subscriptions'|translate}</a>
</p>