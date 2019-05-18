<?php

namespace Drupal\external_link_popup\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements the external_link_popup.settings route.
 *
 * @see \Drupal\Core\Form\FormBase
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['external_link_popup.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'external_link_popup_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function config($name = NULL) {
    return parent::config($name ?: 'external_link_popup.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['whitelist'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Allowed Sites"),
      '#default_value' => $this->config()->get('whitelist'),
      '#description' => $this->t(
        'Base domain without protocol or "www" prefix. Use a comma to divide multiple domains. Links to these external domain(s) will work normally.'
      ),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    if (
      $form_state->getValue('whitelist')
      && !preg_match('/^([[:alnum:]\._-]+,\s?)*([[:alnum:]\._-]+)$/', $form_state->getValue('whitelist'))
    ) {
      $form_state->setErrorByName('whitelist', $this->t('Please match the requested format.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config()
      ->set('whitelist', $form_state->getValue('whitelist'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
