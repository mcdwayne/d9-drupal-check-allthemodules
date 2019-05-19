<?php

namespace Drupal\single_language_url_prefix\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;

/**
 * Class SettingsForm
 *
 * @package Drupal\single_language_url_prefix\Form
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['single_language_url_prefix.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'single_language_url_prefix_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('single_language_url_prefix.settings');

    $form['excluded_paths'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Excluded Paths'),
      '#default_value' => $config->get('excluded_paths'),
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
    $config = $this->configFactory()->getEditable('single_language_url_prefix.settings');
    $config->set('excluded_paths', $form_state->getValue('excluded_paths'));
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
