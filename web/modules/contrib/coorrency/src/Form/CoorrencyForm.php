<?php

namespace Drupal\coorrency\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * ConfigFormBase lets us use $this->config to retrieve the module's configuration.
 */
class CoorrencyForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'coorrency_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Form constructor.
    $form = parent::buildForm($form, $form_state);
    // Default settings.
    $config = $this->config('coorrency.settings');

    $form['api'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('API Settings'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];

    $form['api']['swap'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add the swap button'),
      '#default_value' => $config->get('coorrency.swap'),
    ];

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
    $config = $this->config('coorrency.settings');
    $config->set('coorrency.swap', $form_state->getValue('swap'));
    $config->save();

    return parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    // This function returns the name of the settings files we will create/use.
    return [
      'coorrency.settings',
    ];
  }

}
