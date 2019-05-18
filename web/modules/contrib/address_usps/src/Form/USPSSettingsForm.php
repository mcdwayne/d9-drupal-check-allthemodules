<?php

namespace Drupal\address_usps\Form;

use Drupal\address_usps\AddressUSPSHelper;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Class USPSSettingsForm.
 *
 * @package Drupal\address_usps\Form
 */
class USPSSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'address_usps.uspssettings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'usps_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('address_usps.uspssettings');

    $form[AddressUSPSHelper::CONFIG_USPS_USERNAME] = [
      '#type' => 'textfield',
      '#title' => $this->t('Service username'),
      '#description' => $this->t('Enter username that you got in email after registration in USPS Web Tools API portal. If you have no Web Tools API username, you can get it here: <a href=":url" target="_blank">Registration for USPS Web Tools</a>', [
        ':url' => Url::fromUri('https://www.usps.com/business/web-tools-apis/web-tools-registration.htm')->toString(),
      ]),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get(AddressUSPSHelper::CONFIG_USPS_USERNAME),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('address_usps.uspssettings')
      ->set(AddressUSPSHelper::CONFIG_USPS_USERNAME, $form_state->getValue(AddressUSPSHelper::CONFIG_USPS_USERNAME))
      ->save();
  }

}
