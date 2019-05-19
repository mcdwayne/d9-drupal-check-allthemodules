<?php

/**
 * @file
 * Contains \Drupal\uc_affirm\Form\AffirmSettingsForm.
 */

namespace Drupal\uc_affirm\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Ubercart affirm settings form.
 */
class AffirmSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_affirm_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $affirm_config = $this->config('uc_affirm.settings');

    $form['uc_affirm'] = array(
      '#type' => 'vertical_tabs',
    );

    // Form elements Affirm API.
    $form['api_security'] = array(
      '#type' => 'details',
      '#title' => $this->t('API settings'),
      '#description' => $this->t('You are responsible for the security of your website, including the protection of Affirm API details.  Please be aware that choosing some settings in this section may decrease the security of Affirm data on your website and increase your liability for damages in the case of fraud.'),
      '#group' => 'uc_affirm',
    );
    $form['api_security']['uc_affirm_financial_product_key'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Financial product key'),
      '#description' => $this->t('This financial product key provided by the affirm dash board.'),
      '#default_value' => $affirm_config->get('uc_affirm_fpkey'),
    );
    $form['api_security']['uc_affirm_public_api_key'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Public API key'),
      '#description' => $this->t('This public api key provided by the affirm dashboard.'),
      '#default_value' => $affirm_config->get('uc_affirm_public_key'),
    );
    $form['api_security']['uc_affirm_private_api_key'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Private API key'),
      '#description' => $this->t('This private api key provided by the affirm dashboard.'),
      '#default_value' => $affirm_config->get('uc_affirm_private_key'),
    );
    $form['api_security']['uc_affirm_server'] = array(
      '#type' => 'select',
      '#title' => $this->t('Affirm Server'),
      '#description' => $this->t('Available list of affirm server.'),
      '#options' => [
        'live' => $this->t('Live'),
        'sandbox' => $this->t('Sandbox'),
      ],
      '#default_value' => $affirm_config->get('uc_affirm_server'),
    );
    $form['api_security']['uc_affirm_txt_type'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Default credit card transaction type'),
      '#description' => $this->t('The default will be used to process transactions during checkout.'),
      '#options' => [
        'Authorization and capture' => $this->t('Authorization and capture'),
        'Authorization' => $this->t('Authorization'),
      ],
      '#default_value' => $affirm_config->get('uc_affirm_txt_type'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('uc_affirm.settings')
      ->set('uc_affirm_fpkey', $form_state->getValue('uc_affirm_financial_product_key'))
      ->set('uc_affirm_public_key', $form_state->getValue('uc_affirm_public_api_key'))
      ->set('uc_affirm_private_key', $form_state->getValue('uc_affirm_private_api_key'))
      ->set('uc_affirm_server', $form_state->getValue('uc_affirm_server'))
      ->set('uc_affirm_txt_type', $form_state->getValue('uc_affirm_txt_type'))
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'uc_affirm.settings',
    ];
  }

}
