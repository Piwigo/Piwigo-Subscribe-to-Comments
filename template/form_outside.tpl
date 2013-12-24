{combine_script id='jquery.colorbox' load='footer' require='jquery' path='themes/default/js/plugins/jquery.colorbox.min.js'}
{combine_css id='colorbox' path='themes/default/js/plugins/colorbox/style2/colorbox.css'}

{footer_script require='jquery,jquery.colorbox'}
(function($){
  $('#open_stc_standalone').colorbox({
    inline: true
  });

  var bg_color = 'white';
  $.each(['#the_page #content', 'body'], function(i, selector) {
    var color = $(selector).css('background-color');
    if (color && color!='transparent') {
      bg_color = color;
      return false;
    }
  });
  $('#stc_standalone').css('background-color', bg_color);

{if $STC.ALLOW_GLOBAL}
  $("#stc_submit").hide();
  $("#stc_standalone input[name='stc_mode']").change(function() {
    $("#stc_submit").show();
    $.colorbox.resize({ speed:0 });
  });
{/if}

{if !isset($STC_SUBSCRIBED) and $STC.ASK_MAIL}
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
{/if}
}(jQuery));
{/footer_script}

<div style="display:none">
  <form method="post" action="{$comment_add.F_ACTION}" id="stc_standalone" style="padding:10px;min-width:350px;">
  {if $STC.ON_PICTURE}
    {if $STC.ALLOW_GLOBAL}
      <label><input type="radio" name="stc_mode" value="image"> {'this picture'|translate}</label>
      {if $STC.ALLOW_ALBUM_IMAGES}<label><input type="radio" name="stc_mode" value="album-images"> {'all pictures of this album'|translate}</label>{/if}
      <label><input type="radio" name="stc_mode" value="all-images"> {'all pictures of the gallery'|translate}</label>
    {else}
      <input type="hidden" name="stc_mode" value="image">
    {/if}
  {else if $STC.ON_ALBUM}
    {if $STC.ALLOW_GLOBAL}
      <label><input type="radio" name="stc_mode" value="album"> {'this album'|translate}</label>
      <label><input type="radio" name="stc_mode" value="all-albums"> {'all albums of the gallery'|translate}</label>
    {else}
      <input type="hidden" name="stc_mode" value="album">
    {/if}
  {/if}
    {if $STC.ALLOW_GLOBAL and $STC.ASK_MAIL}<br>{/if}
    <span id="stc_submit">
    {if $STC.ASK_MAIL}
      <label>{'Email'|translate} <input type="text" name="stc_mail" size="30"></label>
    {/if}
      <label><input type="submit" name="stc_submit" value="{'Subscribe'|translate}"></label>
    </span>
  </form>
</div>