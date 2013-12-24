{if isset($STC.SUBSCRIBED)}
{strip}
  {if $STC.SUBSCRIBED=='all-images'}
    {assign var=str value='all pictures of the gallery'|translate}
  {else if $STC.SUBSCRIBED=='album-images'}
    {assign var=str value='all pictures of this album'|translate}
  {else if $STC.SUBSCRIBED=='image'}
    {assign var=str value='this picture'|translate}
  {else if $STC.SUBSCRIBED=='all-albums'}
    {assign var=str value='all albums of the gallery'|translate}
  {else if $STC.SUBSCRIBED=='album'}
    {assign var=str value='this album'|translate}
  {/if}
{/strip}
<p>
  {'You are currently subscribed to comments on %s.'|translate:$str}
  <a href="{$STC.U_UNSUB}">{'Unsubscribe'|translate}</a>
</p>

{else}
<p id="stc_comment">
  {'Notify me of followup comments'|translate} :<br>
  <label><input type="radio" name="stc_mode" value="-1" {if !$STC.MODE}checked{/if}> {'No'|translate}</label><br>
  {if $STC.ON_PICTURE}
    {if $STC.ALLOW_GLOBAL}
      <label><input type="radio" name="stc_mode" value="image" {if $STC.MODE=="image"}checked{/if}> {'this picture'|translate|ucfirst}</label><br>
      {if $STC.ALLOW_ALBUM_IMAGES}<label><input type="radio" name="stc_mode" value="album-images" {if $STC.MODE=="album-images"}checked{/if}> {'all pictures of this album'|translate|ucfirst}</label><br>{/if}
      <label><input type="radio" name="stc_mode" value="all-images" {if $STC.MODE=="all-images"}checked{/if}> {'all pictures of the gallery'|translate|ucfirst}</label><br>
    {else}
      <label><input type="radio" name="stc_mode" value="image" {if $STC.MODE=="image"}checked{/if}> {'Yes'|translate}</label>
    {/if}
  {else if $STC.ON_ALBUM}
    {if $STC.ALLOW_GLOBAL}
      <label><input type="radio" name="stc_mode" value="album" {if $STC.MODE=="album"}checked{/if}> {'this album'|translate|ucfirst}</label><br>
      <label><input type="radio" name="stc_mode" value="all-albums" {if $STC.MODE=="all-albums"}checked{/if}> {'all albums of the gallery'|translate|ucfirst}</label><br>
    {else}
      <label><input type="radio" name="stc_mode" value="album" {if $STC.MODE=="album"}checked{/if}> {'Yes'|translate}</label>
    {/if}
  {/if}
</p>
<p>
  <a href="#stc_standalone" id="open_stc_standalone" title="{'Subscribe to mail notifications'|translate}">{'Subscribe without commenting'|translate}</a>
</p>

{/if}