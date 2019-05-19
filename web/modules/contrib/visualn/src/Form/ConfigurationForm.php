<?php

namespace Drupal\visualn\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ConfigurationForm.
 */
class ConfigurationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'visualn.configuration',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'visualn_configuration';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('visualn.configuration');

    $form = parent::buildForm($form, $form_state);
    // @todo: add to module default configuration
    //   use config on Available drawers preview page
/*
    $form['drawers_preview_generator_selector'] = [
      '#type' => 'radios',
      '#title' => $this->t('Drawers preview generator selector type'),
      '#options' => [
        'select' => $this->t('Select'),
        'autocomplete' => $this->t('Autocomplete'),
      ],
      '#default_value' => $config->get('drawers_preview_generator_selector') ?: 'select',
      '#description' => $this->t('For longer lists it may be convenient to use autocomplete form element'),
      '#required' => TRUE,
    ];
*/

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('visualn.configuration')
      ->save();
  }

}
