<div class="titrePage" style="clear:right;">
  <h2>Subscribe to Comments</h2>
</div>

<form method="post" action="" class="properties">
  <fieldset>
    <legend>{'Configuration'|translate}</legend>
    <ul>
      <li>
        <label>
          <input type="checkbox" name="notify_admin_on_subscribe" value="1" {if $notify_admin_on_subscribe}checked="checked"{/if}>
          <b>{'Notify administrators when a user take a new subscription'|translate}</b>
        </label>
      </li>
      <li>
        <label>
          <input type="checkbox" name="allow_global_subscriptions" value="1" {if $allow_global_subscriptions}checked="checked"{/if}>
          <b>{'Allow users to subscribe to global notifications'|translate}</b>
        </label>
      </li>
    </ul>
  </fieldset>

  <p><input class="submit" type="submit" value="{'Submit'|translate}" name="config_submit"/></p>
</form>