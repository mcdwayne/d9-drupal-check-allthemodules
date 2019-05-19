<?php

namespace Drupal\spamicide\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Spamicide settings for this site.
 */
class SpamicideSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'spamicide_spamicide_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['spamicide.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['spamicide_admin_mode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add Spamicide administration links to forms'),
      '#default_value' => $this->config('spamicide.settings')->get('spamicide_admin_mode'),
      '#description' => $this->t('This option makes it easy to manage Spamicide settings on forms. When enabled, users with the administer Spamicide settings permission will see a fieldset with Spamicide administration links on all forms, except on administrative pages.'),
    ];

    $form['spamicide_log_attempts'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Log wrong responses'),
      '#default_value' => $this->config('spamicide.settings')->get('spamicide_log_attempts'),
      '#description' => $this->t('Report information about wrong responses to the log.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('spamicide.settings')
      ->set('spamicide_admin_mode', $form_state->getValue('spamicide_admin_mode'))
      ->set('spamicide_log_attempts', $form_state->getValue('spamicide_log_attempts'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
