<div class="titrePage" style="clear:right;">
  <h2>Subscribe to Comments</h2>
</div>

<form method="post" action="" class="properties">
  <fieldset>
    <legend>{'Configuration'|@translate}</legend>
    <ul>
      <li>
        <label>
          <span class="property">{'Notify administrators when a user take a new subscription'|@translate}</span>
          <input type="checkbox" name="notify_admin_on_subscribe" value="1" {if $notify_admin_on_subscribe}checked="checked"{/if}>
        </label>
      </li>
      <li>
        <label>
          <span class="property">{'Allow users to subscribe to global notifications'|@translate}</span>
          <input type="checkbox" name="allow_global_subscriptions" value="1" {if $allow_global_subscriptions}checked="checked"{/if}>
        </label>
      </li>  
    </ul>
  </fieldset>

  <p><input class="submit" type="submit" value="{'Submit'|@translate}" name="config_submit"/></p>
</form>