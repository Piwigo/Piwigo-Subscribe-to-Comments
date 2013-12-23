{if !$SUBSCRIBED}

<p>
  {'Notify me of followup comments'|translate} :<br>
  <label><input type="radio" name="stc_mode" value="-1" {if !$STC_MODE}checked{/if}> {'No'|translate}</label>
  {if $STC_ON_PICTURE}
    {if $STC_ALLOW_GLOBAL}
      <label><input type="radio" name="stc_mode" value="image" {if $STC_MODE=="image"}checked{/if}> {'this picture'|translate}</label>
      {if $STC_ALLOW_ALBUM_IMAGES}<label><input type="radio" name="stc_mode" value="album-images" {if $STC_MODE=="album-images"}checked{/if}> {'all pictures of this album'|translate}</label>{/if}
      <label><input type="radio" name="stc_mode" value="all-images" {if $STC_MODE=="all-images"}checked{/if}> {'all pictures of the gallery'|translate}</label>
    {else}
      <label><input type="radio" name="stc_mode" value="image" {if $STC_MODE=="image"}checked{/if}> {'Yes'|translate}</label>
    {/if}
  {elseif $STC_ON_ALBUM}
    {if $STC_ALLOW_GLOBAL}
      <label><input type="radio" name="stc_mode" value="album" {if $STC_MODE=="album"}checked{/if}> {'this album'|translate}</label>
      <label><input type="radio" name="stc_mode" value="all-albums" {if $STC_MODE=="all-albums"}checked{/if}> {'all albums of the gallery'|translate}</label>
    {else}
      <label><input type="radio" name="stc_mode" value="album" {if $STC_MODE=="album"}checked{/if}> {'Yes'|translate}</label>
    {/if}
  {/if}
</p>

{if $STC_ASK_MAIL}
{footer_script require='jquery'}
(function($){
	var $stc_email_input = $("#addComment input[name='email']");
  var stc_bordercolor = $stc_email_input.css('border-color');

  $("#addComment input[name='stc_mode']").change(function() {
    if ($(this).val() != "-1") {
      if ($stc_email_input.val()=="") {
        $stc_email_input.css("border-color", "red");
      }
    }
    else {
      $stc_email_input.css("border-color", stc_bordercolor);
    }
  });
  $stc_email_input.change(function() {
    $(this).css('border-color', stc_bordercolor);
  });
}(jQuery));
{/footer_script}
{/if}

{/if}