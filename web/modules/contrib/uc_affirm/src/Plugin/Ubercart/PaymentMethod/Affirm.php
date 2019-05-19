<?php

/**
 * @file
 * Contains \Drupal\uc_affirm\Plugin\Ubercart\PaymentMethod\Affirm.
 */

namespace Drupal\uc_affirm\Plugin\Ubercart\PaymentMethod;

use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\uc_order\OrderInterface;
use Drupal\uc_payment\PaymentMethodPluginBase;
use Drupal\uc_store\Address;

/**
 * Defines the affirm payment method.
 *
 * @UbercartPaymentMethod(
 *   id = "affirm",
 *   name = @Translation("Affirm", context = "Ubercart Affirm"),
 *   redirect = "\Drupal\uc_affirm\Form\AffirmSettingsForm",
 * )
 */
class Affirm extends PaymentMethodPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getDisplayLabel($label) {
    $build['label'] = array(
      '#plain_text' => $label,
      '#suffix' => '<br />',
    );
    $build['image'] = array(
      '#theme' => 'image',
      '#uri' => drupal_get_path('module', 'uc_affirm') . '/images/affirm-icon.png',
      '#alt' => $this->t('Affirm'),
      '#attributes' => array('class' => array('')),
    );
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = \Drupal::config('uc_affirm.settings');
    return [
      'uc_affirm_fpkey' => '',
      'uc_affirm_public_key' => '',
      'uc_affirm_private_key' => '',
      'uc_affirm_server' => '',
      'uc_affirm_txt_type' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    // Form elements Affirm API.
    $form['api_security'] = array(
      '#type' => 'details',
      '#title' => $this->t('API settings'),
      '#description' => $this->t('You are responsible for the security of your website, including the protection of Affirm API details.  Please be aware that choosing some settings in this section may decrease the security of Affirm data on your website and increase your liability for damages in the case of fraud.'),
    );
    $form['uc_affirm_fpkey'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Financial product key'),
      '#description' => $this->t('This financial product key provided by the affirm dash board.'),
      '#default_value' => $this->configuration['uc_affirm_fpkey'],
      '#required' => TRUE,
    );
    $form['uc_affirm_public_key'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Public API key'),
      '#description' => $this->t('This public api key provided by the affirm dashboard.'),
      '#default_value' => $this->configuration['uc_affirm_public_key'],
      '#required' => TRUE,
    );
    $form['uc_affirm_private_key'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Private API key'),
      '#description' => $this->t('This private api key provided by the affirm dashboard.'),
      '#default_value' => $this->configuration['uc_affirm_private_key'],
      '#required' => TRUE,
    );
    $form['uc_affirm_server'] = array(
      '#type' => 'select',
      '#title' => $this->t('Affirm Server'),
      '#description' => $this->t('Available list of affirm server.'),
      '#options' => [
        'live' => $this->t('Live'),
        'sandbox' => $this->t('Sandbox'),
      ],
      '#default_value' => $this->configuration['uc_affirm_server'],
      '#required' => TRUE,
    );
    $form['uc_affirm_txt_type'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Default credit card transaction type'),
      '#description' => $this->t('The default will be used to process transactions during checkout.'),
      '#options' => [
        'Authorization and capture' => $this->t('Authorization and capture'),
        'Authorization' => $this->t('Authorization'),
      ],
      '#default_value' => $this->configuration['uc_affirm_txt_type'],
      '#required' => TRUE,
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::service('config.factory')->getEditable('uc_affirm.settings');
    $this->configuration['uc_affirm_fpkey'] = $form_state->getValue('uc_affirm_fpkey');
    $this->configuration['uc_affirm_public_key'] = $form_state->getValue('uc_affirm_public_key');
    $this->configuration['uc_affirm_private_key'] = $form_state->getValue('uc_affirm_private_key');
    $this->configuration['uc_affirm_server'] = $form_state->getValue('uc_affirm_server');
    $this->configuration['uc_affirm_txt_type'] = $form_state->getValue('uc_affirm_txt_type');
    $values = $form_state->getValues();
    $config -> set('uc_affirm_fpkey', $values['settings']['uc_affirm_fpkey'])
      ->set('uc_affirm_public_key', $values['settings']['uc_affirm_public_key'])
      ->set('uc_affirm_private_key', $values['settings']['uc_affirm_private_key'])
      ->set('uc_affirm_server', $values['settings']['uc_affirm_server'])
      ->set('uc_affirm_txt_type', $values['settings']['uc_affirm_txt_type'])
      ->save();
  }

}
