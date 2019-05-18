<?php

namespace Drupal\phones\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements the form controller.
 */
class Settings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'phones_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['phones.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('phones.settings');
    $form['pipeline'] = [
      '#type' => 'details',
      '#title' => $this->t('Pipeline'),
      'timeput' => [
        '#title' => $this->t('Timeout'),
        '#type' => 'textfield',
        '#default_value' => $config->get('timeout'),
      ],
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('phones.settings');
    $config
      ->set('timeout', $form_state->getValue('timeout'))
      ->save();
  }

}
