<?php

namespace Drupal\bisnode\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class BisnodeConfigForm.
 */
class BisnodeConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'bisnode.bisnodeconfig',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bisnode_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('bisnode.bisnodeconfig');
    $form['bisnode_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Bisnode url'),
      '#default_value' => $config->get('bisnode_url'),
      '#required' => TRUE,
      '#description' => 'eg: https://api.bisnode.no',
    ];
    $form['bisnode_username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('bisnode_username'),
      '#required' => TRUE,
    ];
    $form['bisnode_password'] = [
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('bisnode_password'),
    ];
    $form['bisnode_debug_javascript'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Debug javascript'),
      '#default_value' => $config->get('bisnode_debug_javascript'),
    ];

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

    $config = $this->config('bisnode.bisnodeconfig');
    $config
      ->set('bisnode_url', $form_state->getValue('bisnode_url'))
      ->set('bisnode_username', $form_state->getValue('bisnode_username'))
      ->set('bisnode_debug_javascript', $form_state->getValue('bisnode_debug_javascript'));
    if ($password = $form_state->getValue('bisnode_password')) {
      $config->set('bisnode_password', $password);
    }
    $config->save();
  }

}
