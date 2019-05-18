<?php

namespace Drupal\brightcove\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;

/**
 * Builds the form for managing Brightcove cron settings.
 */
class BrightcoveCronSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['brightcove.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'brightcove_cron_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('brightcove.settings');

    $form['disable_cron'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable cron'),
      '#description' => $this->t('Enabling this option will prevent a Brightcove-to-Drupal sync running from cron.'),
      '#default_value' => $config->get('disable_cron'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('brightcove.settings')
      ->set('disable_cron', $form_state->getValue('disable_cron'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
