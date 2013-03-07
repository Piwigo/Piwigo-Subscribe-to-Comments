{combine_css path=$SUBSCRIBE_TO_PATH|@cat:'template/style_form.css'}

{if $STC_ALLOW_GLOBAL}
  {footer_script require="jquery"}{literal}
  jQuery("#stc_submit").hide();
  jQuery("#stc_standalone input[name='stc_mode']").change(function() {
    jQuery("#stc_submit").show();
  });
  {/literal}{/footer_script}
{/if}

{if isset($comment_add)}
<div id="pictureCommentList">
<form method="post" action="{$comment_add.F_ACTION}" id="stc_standalone">
  <fieldset>{strip}
  {if $SUBSCRIBED_ALL_IMAGES}
    {'You are currently subscribed to comments on %s.'|@translate|sprintf:'all pictures of the gallery'|@translate}
    <a href="{$MANAGE_LINK}">{'Manage my subscriptions'|@translate}</a> 
    
  {elseif $SUBSCRIBED_ALBUM_IMAGES}
    {'You are currently subscribed to comments on %s.'|@translate|sprintf:'all pictures of this album'|@translate}
    <a href="{$UNSUB_LINK}">{'Unsubscribe'|@translate}</a>
    
  {elseif $SUBSCRIBED_IMAGE}
    {'You are currently subscribed to comments on %s.'|@translate|sprintf:'this picture'|@translate}
    <a href="{$UNSUB_LINK}">{'Unsubscribe'|@translate}</a>
    
  {elseif $SUBSCRIBED_ALL_ALBUMS}
    {'You are currently subscribed to comments on %s.'|@translate|sprintf:'all albums of the gallery'|@translate}
    <a href="{$MANAGE_LINK}">{'Manage my subscriptions'|@translate}</a>
    
  {elseif $SUBSCRIBED_ALBUM}
    {'You are currently subscribed to comments on %s.'|@translate|sprintf:'this album'|@translate}
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
    <span id="stc_submit">
    {if $STC_ASK_MAIL}
        <label>{'Email'|@translate} <input type="text" name="stc_mail" size="30"></label>
    {/if}
      <label><input type="submit" name="stc_submit" value="{'Subscribe'|@translate}"></label>
    </span>
  {/if}
  {/strip}</fieldset>
</form>
</div>
{/if}