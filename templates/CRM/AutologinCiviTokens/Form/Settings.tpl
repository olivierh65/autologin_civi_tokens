{* HEADER *}

<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="top"}
</div>

<div class="crm-section">
  <div class="entity" style="border: thin solid grey; margin: 0.5em; border-radius: 0.2em;">
    <b>Entities to present</b>
    <div class="webform" style="border: thin solid grey; margin: 0.5em; border-radius: 0.2em;">
      <b>Webform</b>
      <div class="autologin_webform">
        <div class="label">{$form.autologin_webform.label}</div>
        <div class="content">{$form.autologin_webform.html}</div>
        <div class="autologin_webform_open" style="margin-left:4em;">
          <div class="label">{$form.autologin_webform_open.label}</div>
          <div class="content">{$form.autologin_webform_open.html}</div>
        </div>
      </div>
      <div class="autologin_webform_submission" style="margin-top: .5em;">
        <div class="label">{$form.autologin_webform_submission.label}</div>
        <div class="content">{$form.autologin_webform_submission.html}</div>
        <div class="autologin_webform_submission_open" style="margin-left:4em;">
          <div class="label">{$form.autologin_webform_submission_open.label}</div>
          <div class="content">{$form.autologin_webform_submission_open.html}</div>
        </div>
      </div>
    </div>

    <div class="view" style="border: thin solid grey; margin: 0.5em; border-radius: 0.2em;">
      <b>View</b>
      <div class="autologin_view">
        <div class="label">{$form.autologin_view.label}</div>
        <div class="content">{$form.autologin_view.html}</div>
      </div>
    </div>
  </div>

  <div class="entity" style="border: thin solid grey; margin: 0.5em; border-radius: 0.2em;">
    <b>URL Format</b>
    <div class="autologin_url">
      <div class="label">{$form.autologin_url.label}</div>
      <div class="content">{$form.autologin_url.html}</div>
    </div>
  </div>

  <div class="entity" style="border: thin solid grey; margin: 0.5em; border-radius: 0.2em;">
    <b>Trace</b>
    <div class="autologin_log">
      <div class="label">{$form.autologin_log_url.label}</div>
      <div class="content">{$form.autologin_log_url.html}</div>
    </div>
    <div class="autologin_debug" style="margin-top: .5em;">
      <div class="label">{$form.autologin_debug.label}</div>
      <div class="content">{$form.autologin_debug.html}</div>
    </div>
  </div>
</div>

{* FOOTER *}
<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="bottom"}
</div>
