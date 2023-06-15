{* HEADER *}

<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="top"}
</div>

<div class="crm-section">
  <div class="entity" style="border: thin solid grey; margin: 0.5em; border-radius: 0.2em;">
    <b>Entities to present</b>
    <div class="webform" style="border: thin solid grey; margin: 0.5em; border-radius: 0.2em;">
      <b>Webform</b>
      <div class="autologin_civi_tokens_webform">
        <div class="label">{$form.autologin_civi_tokens_webform.label}</div>
        <div class="content">{$form.autologin_civi_tokens_webform.html}</div>
        <div class="autologin_civi_tokens_webform_open" style="margin-left:4em;">
          <div class="label">{$form.autologin_civi_tokens_webform_open.label}</div>
          <div class="content">{$form.autologin_civi_tokens_webform_open.html}</div>
        </div>
      </div>
      <div class="autologin_civi_tokens_webform_submission" style="margin-top: .5em;">
        <div class="label">{$form.autologin_civi_tokens_webform_submission.label}</div>
        <div class="content">{$form.autologin_civi_tokens_webform_submission.html}</div>
        <div class="autologin_civi_tokens_webform_submission_open" style="margin-left:4em;">
          <div class="label">{$form.autologin_civi_tokens_webform_submission_open.label}</div>
          <div class="content">{$form.autologin_civi_tokens_webform_submission_open.html}</div>
        </div>
      </div>
    </div>

    <div class="view" style="border: thin solid grey; margin: 0.5em; border-radius: 0.2em;">
      <b>View</b>
      <div class="autologin_civi_tokens_view">
        <div class="label">{$form.autologin_civi_tokens_view.label}</div>
        <div class="content">{$form.autologin_civi_tokens_view.html}</div>
      </div>
    </div>
  </div>

  <div class="entity" style="border: thin solid grey; margin: 0.5em; border-radius: 0.2em;">
    <b>URL Format</b>
    <div class="autologin_civi_tokens_absurl">
      <div class="label">{$form.autologin_civi_tokens_absurl.label}</div>
      <div class="content">{$form.autologin_civi_tokens_absurl.html}</div>
    </div>
  </div>

  <div class="entity" style="border: thin solid grey; margin: 0.5em; border-radius: 0.2em;">
    <b>Trace</b>
    <div class="autologin_civi_tokens_log">
      <div class="label">{$form.autologin_civi_tokens_log_url.label}</div>
      <div class="content">{$form.autologin_civi_tokens_log_url.html}</div>
    </div>
    <div class="autologin_civi_tokens_debug" style="margin-top: .5em;">
      <div class="label">{$form.autologin_civi_tokens_debug.label}</div>
      <div class="content">{$form.autologin_civi_tokens_debug.html}</div>
    </div>
  </div>
</div>

{* FOOTER *}
<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="bottom"}
</div>
