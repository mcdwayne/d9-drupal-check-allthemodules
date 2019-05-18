<?php

namespace Drupal\pfdp\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings form for private_files_download_permission.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pfdp_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['pfdp.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $settings = \Drupal::config('pfdp.settings');
    // Prepare the fields.
    $form['by_user_checks'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Enable by-user checks'),
      '#default_value' => $settings->get('by_user_checks'),
      '#description'   => $this->t('You may wish to disable this feature if there are plenty of users, as it may slow down the entire site.'),
    ];
    $form['cache_users'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Cache user list'),
      '#default_value' => $settings->get('cache_users'),
      '#description'   => $this->t('For sites with lots of users, this will save on load time when editing directory settings.'),
      '#states'        => [
        'visible' => [
          ':input[name="by_user_checks"]' => ['checked' => TRUE],
        ],
        'enabled' => [
          ':input[name="by_user_checks"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['attachment_mode'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Enable attachment mode'),
      '#default_value' => $settings->get('attachment_mode'),
      '#description'   => $this->t('Have files downloaded as attachments instead of displayed inline in the browser.'),
    ];
    $form['debug_mode'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Enable debug mode'),
      '#default_value' => $settings->get('debug_mode'),
      '#description'   => $this->t('Turn on logging to debug issues.'),
    ];
    // Prepare the submit button.
    $form['submit'] = [
      '#type'  => 'submit',
      '#value' => $this->t('Save settings'),
    ];
    // Return the form.
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $settings = \Drupal::configFactory()->getEditable('pfdp.settings');
    // Save all settings.
    $settings->set('by_user_checks', $form_state->getValue('by_user_checks'))->save();
    if (!$form_state->getValue('by_user_checks')) {
      $settings->set('cache_users', FALSE)->save();
    }
    else {
      $settings->set('cache_users', $form_state->getValue('cache_users'))->save();
    }
    $settings->set('attachment_mode', $form_state->getValue('attachment_mode'))->save();
    $settings->set('debug_mode', $form_state->getValue('debug_mode'))->save();
    // Display the status message.
    drupal_set_message($this->t('Your settings were saved successfully.'), 'status');
  }

}
