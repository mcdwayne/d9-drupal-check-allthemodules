<?php

namespace Drupal\be_ixf_drupal\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Class DrupalAdminForm.
 *
 * @package Drupal\be_ixf_drupal\Form
 */
class AdminForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'be_ixf_drupal_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['be_ixf_drupal.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('be_ixf_drupal.settings');


    $form['capsule_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Capsule Mode'),
      '#description' => $this->t('Mode to use for connecting to BrightEdge. Leave as default.'),
      '#options' => [
        'REMOTE_PROD_CAPSULE_MODE' => $this->t('Production'),
        'REMOTE_PROD_GLOBAL_CAPSULE_MODE' => $this->t('Production Global'),
      ],
      '#default_value' => $config->get('capsule_mode'),
    ];

    $form['account_id'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Account Id'),
      '#description' => $this->t('Account configured in the f000000ZZZ form'),
      '#size' => 20,
      '#maxlength' => 20,
      '#default_value' => $config->get('account_id') !== null ? $config->get('account_id') : '',
    );

    $form['api_endpoint'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('API Endpoint'),
      '#description' => $this->t('API Endpoint in https://ixfN-api.bc0a.com'),
      '#default_value' => $config->get('api_endpoint') !== null ? $config->get('api_endpoint') : '',
    );

    $form['block_cache_max_age'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Block Cache Maximum Age'),
      '#size' => 4,
      '#maxlength' => 6,
      '#description' => $this->t('The maximum age cache time for the block in seconds'),
      '#default_value' => $config->get('block_cache_max_age') !== null ? $config->get('block_cache_max_age') : '3600',
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    if (!is_numeric($values['block_cache_max_age'])) {
      form_set_error('block_cache_max_age', t('You must enter an integer for block cache maximum age.'));
    }
    parent::validateForm($form, $form_state);

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $this->configFactory()->getEditable('be_ixf_drupal.settings')
      ->set('capsule_mode', $values['capsule_mode'])
      ->set('account_id', $values['account_id'])
      ->set('api_endpoint', $values['api_endpoint'])
      ->set('block_cache_max_age', $values['block_cache_max_age'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
