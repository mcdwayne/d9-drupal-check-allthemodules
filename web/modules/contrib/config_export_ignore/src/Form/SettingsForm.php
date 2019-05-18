<?php

namespace Drupal\config_export_ignore\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SettingsForm.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'config_export_ignore.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('config_export_ignore.settings');
    $form['configuration_names'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Configuration entity names to ignore'),
      '#description' => $this->t('One configuration name per line.<br />Examples: <ul><li>user.settings</li><li>views.settings</li><li>contact.settings</li><li>webform.webform.* (will ignore all config entities that starts with <em>webform.webform</em>)</li><li>*.contact_message.custom_contact_form.* (will ignore all config entities that starts with <em>.contact_message.custom_contact_form.</em> like fields attached to a custom contact form)</li><li>* (will ignore everything)</li><li>~webform.webform.contact (will force import for this configuration, even if ignored by a wildcard)</li></ul>'),
      '#default_value' => implode(PHP_EOL, $config->get('configuration_names')),
      '#rows' => 20,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $values = $form_state->getValues();
    $config_ignore_settings = $this->config('config_export_ignore.settings');
    $config_ignore_settings_array = preg_split("[\n|\r]", $values['configuration_names']);
    $config_ignore_settings_array = array_filter($config_ignore_settings_array);
    $config_ignore_settings->set('configuration_names', $config_ignore_settings_array);
    $config_ignore_settings->save();
  }

}
