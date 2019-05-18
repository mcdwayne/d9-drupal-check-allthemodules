<?php

namespace Drupal\graphql_jwt\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ConfigForm.
 *
 * @package Drupal\graphql_jwt\Form
 */
class ConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'graphql_jwt.config',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'graphql_jwt_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['session_destroy'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Destroy session'),
      '#description' => $this->t('If the session should be destroy on successful authentication.'),
      '#default_value' => $this->config('graphql_jwt.config')->get('session_destroy'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $values = $form_state->getValues();
    if (isset($values['session_destroy'])) {
      $this->config('graphql_jwt.config')
        ->set('session_destroy', $values['session_destroy'])
        ->save();
    }
  }

}
