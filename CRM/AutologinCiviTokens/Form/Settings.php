<?php

use CRM_AutologinCiviTokens_ExtensionUtil as E;

/**
 * Form controller class
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/quickform/
 */
class CRM_AutologinCiviTokens_Form_Settings extends CRM_Core_Form {
  public function buildQuickForm() {

    //Webform
    $this->add(
      'advcheckbox',
      'autologin_civi_tokens_webform',
      'Webform',
      '',
      false,
    )->setChecked(Civi::settings()->get('autologin_civi_tokens_webform') == null ? true : Civi::settings()->get('autologin_civi_tokens_webform'));
    $this->add(
      'advcheckbox',
      'autologin_civi_tokens_webform_open',
      'Opened only',
      '',
      false,
    )->setChecked(Civi::settings()->get('autologin_civi_tokens_webform_open') == null ? true : Civi::settings()->get('autologin_civi_tokens_webform_open'));

    $this->add(
      'advcheckbox',
      'autologin_civi_tokens_webform_submission',
      'Submission',
      '',
      false,
    )->setChecked(Civi::settings()->get('autologin_civi_tokens_webform_submission') == null ? false : Civi::settings()->get('autologin_civi_tokens_webform_submission'));
    $this->add(
      'advcheckbox',
      'autologin_civi_tokens_webform_submission_open',
      'Opened only',
      '',
      false,
    )->setChecked(Civi::settings()->get('autologin_civi_tokens_webform_submission_open') == null ? false : Civi::settings()->get('autologin_civi_tokens_webform_submission_open'));

    // View
    $this->add(
      'advcheckbox',
      'autologin_civi_tokens_view',
      'View',
      '',
      false,
    )->setChecked(Civi::settings()->get('autologin_civi_tokens_view') == null ? false : Civi::settings()->get('autologin_civi_tokens_view'));

    // URL
    $this->add(
      'advcheckbox',
      'autologin_civi_tokens_absurl',
      'Absolute URL',
      '',
      false,
    )->setChecked(Civi::settings()->get('autologin_civi_tokens_absurl') == null ? true : Civi::settings()->get('autologin_civi_tokens_absurl'));

    // Trace
    // Log URL
    $this->add(
      'advcheckbox',
      'autologin_civi_tokens_log_url',
      'Log URL creation',
      '',
      false,
    )->setChecked(Civi::settings()->get('autologin_civi_tokens_log_url') == null ? false : Civi::settings()->get('autologin_civi_tokens_log_url'));
    // Debug
    $this->add(
      'advcheckbox',
      'autologin_civi_tokens_debug',
      'Log debug in base',
      '',
      false,
    )->setChecked(Civi::settings()->get('autologin_civi_tokens_debug') == null ? false : Civi::settings()->get('autologin_civi_tokens_debug'));

    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => E::ts('Submit'),
        'isDefault' => TRUE,
      ),
    ));

    // export form elements
    $this->assign('elementNames', $this->getRenderableElementNames());
    parent::buildQuickForm();
  }

  public function postProcess() {
    $values = $this->exportValues();

    Civi::settings()->set('autologin_civi_tokens_webform', $values['autologin_civi_tokens_webform']);
    Civi::settings()->set('autologin_civi_tokens_webform_open', $values['autologin_civi_tokens_webform_open']);
    Civi::settings()->set('autologin_civi_tokens_webform_submission', $values['autologin_civi_tokens_webform_submission']);
    Civi::settings()->set('autologin_civi_tokens_webform_submission_open', $values['autologin_civi_tokens_webform_submission_open']);

    Civi::settings()->set('autologin_civi_tokens_view', $values['autologin_civi_tokens_view']);

    Civi::settings()->set('autologin_civi_tokens_absurl', $values['autologin_civi_tokens_absurl']);

    Civi::settings()->set('autologin_civi_tokens_log_url', $values['autologin_civi_tokens_log_url']);
    Civi::settings()->set('autologin_civi_tokens_debug', $values['autologin_civi_tokens_debug']);


    parent::postProcess();
  }

  /**
   * Get the fields/elements defined in this form.
   *
   * @return array (string)
   */
  public function getRenderableElementNames() {
    // The _elements list includes some items which should not be
    // auto-rendered in the loop -- such as "qfKey" and "buttons".  These
    // items don't have labels.  We'll identify renderable by filtering on
    // the 'label'.
    $elementNames = array();
    foreach ($this->_elements as $element) {
      /** @var HTML_QuickForm_Element $element */
      $label = $element->getLabel();
      if (!empty($label)) {
        $elementNames[] = $element->getName();
      }
    }
    return $elementNames;
  }

}
