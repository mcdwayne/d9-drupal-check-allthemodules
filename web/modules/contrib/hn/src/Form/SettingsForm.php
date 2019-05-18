<?php

namespace Drupal\hn\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ConfigForm.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'hn.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'hn_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('hn.settings');

    $form['cache'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable cache'),
      '#description' => t('Check this box if you want to enable caching'),
      '#default_value' => $config->get('cache'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    // Save the config.
    $this->config('hn.settings')
      ->set('cache', $values['cache'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
