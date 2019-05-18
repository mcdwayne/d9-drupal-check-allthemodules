<?php

namespace Drupal\onepass\Form;

use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form that configures forms module settings.
 */
class OnepassConfigurationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'onepass_config_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'onepass.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $config = $this->config('onepass.settings');

    $form['host'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('One Pass host'),
      '#description' => t(
        'API host, Live: @live, Dev: @dev',
        array(
          '@live' => 'https://1pass.me',
          '@dev' => 'https://demo.1pass.me',
        )),
      '#default_value' => $config->get('host'),
      '#size' => 30,
    );
    $form['publishable_key'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('One Pass publishable key'),
      '#default_value' => $config->get('publishable_key'),
      '#size' => 30,
    );
    $form['secret_key'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('One Pass secret key'),
      '#default_value' => $config->get('secret_key'),
      '#size' => 30,
    );
    $form['paywall'] = array(
      '#type' => 'checkbox',
      '#title' => t('One Pass paywall'),
      '#default_value' => $config->get('paywall'),
      '#return_value' => 1,
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('onepass.settings')
      ->set('host', $form_state->getValue('host'))
      ->set('publishable_key', $form_state->getValue('publishable_key'))
      ->set('secret_key', $form_state->getValue('secret_key'))
      ->set('paywall', intval($form_state->getValue('paywall')))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
