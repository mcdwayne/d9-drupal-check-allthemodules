<?php

/**
 * @file
 * Contains Drupal\prism\Form\PrismConfigForm.
 */

namespace Drupal\prism\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\prism\PrismConfig;

/**
 * Class PrismConfigForm.
 *
 * @package Drupal\prism\Form
 */
class PrismConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'prism.settings'
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'prism_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('prism.settings');

    $languages = PrismConfig::getLanguages();

    $form['languages'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('Languages'),
      '#description' => $this->t('Select the allowed languages'),
      '#options' => $languages,
      '#default_value' => $config->get('languages'),
    );

    return parent::buildForm($form, $form_state);
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
    
    $this->config('prism.settings')
      ->set('languages', $form_state->getValue('languages'))
      ->save();
  }

}
