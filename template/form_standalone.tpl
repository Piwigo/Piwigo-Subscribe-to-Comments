{if isset($comment_add)}
<div id="pictureCommentList">
<form method="post" action="{$comment_add.F_ACTION}" id="stc_standalone">
  <fieldset>
  {if $SUBSCRIBED_ALL_IMAGES}
    {'You are currently subscribed to comments on'|@translate} {'all pictures of the gallery'|@translate}.
    <a href="{$MANAGE_LINK}">{'Manage my subscribtions'|@translate}</a> 
    
  {elseif $SUBSCRIBED_ALBUM_IMAGES}
    {'You are currently subscribed to comments on'|@translate} {'all pictures of this album'|@translate}.
    <a href="{$UNSUB_LINK}">{'Unsubscribe'|@translate}</a>
    
  {elseif $SUBSCRIBED_IMAGE}
    {'You are currently subscribed to comments on'|@translate} {'this picture'|@translate}.
    <a href="{$UNSUB_LINK}">{'Unsubscribe'|@translate}</a>
    
  {elseif $SUBSCRIBED_ALL_ALBUMS}
    {'You are currently subscribed to comments on'|@translate} {'all albums of the gallery'|@translate}.
    <a href="{$MANAGE_LINK}">{'Manage my subscribtions'|@translate}</a>
    
  {elseif $SUBSCRIBED_ALBUM}
    {'You are currently subscribed to comments on'|@translate} {'this album'|@translate}.
    <a href="{$UNSUB_LINK}">{'Unsubscribe'|@translate}</a>
    
  {else}
    <legend>{'Subscribe to mail notifications'|@translate}</legend>
    {if $STC_ON_PICTURE}
      {if $STC_ALLOW_GLOBAL}
        <label><input type="radio" name="stc_mode" value="image"> {'this picture'|@translate}</label>
        {if $STC_ALLOW_ALBUM_IMAGES}<label><input type="radio" name="stc_mode" value="album-images"> {'all pictures of this album'|@translate}</label>{/if}
        <label><input type="radio" name="stc_mode" value="all-images"> {'all pictures of the gallery'|@translate}</label>
      {else}
        <input type="hidden" name="stc_mode" value="image">
      {/if}
    {elseif $STC_ON_ALBUM}
      {if $STC_ALLOW_GLOBAL}
        <label><input type="radio" name="stc_mode" value="album"> {'this album'|@translate}</label>
        <label><input type="radio" name="stc_mode" value="all-albums"> {'all albums of the gallery'|@translate}</label>
      {else}
        <input type="hidden" name="stc_mode" value="album">
      {/if}
    {/if}
    {if $STC_ALLOW_GLOBAL and $STC_ASK_MAIL}<br>{/if}
    {if $STC_ASK_MAIL}
      <label>{'Email address'|@translate} <input type="text" name="stc_mail" size="30"></label>
    {/if}
    <label><input type="submit" name="stc_submit" value="{'Subscribe'|@translate}"></label>
  {/if}
  </fieldset>
</form>
</div>
{/if}