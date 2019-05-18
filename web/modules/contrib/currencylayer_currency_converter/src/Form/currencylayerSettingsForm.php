<?php

namespace Drupal\currencylayer_currency_converter\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure example settings for this site.
 */
class currencylayerSettingsForm extends ConfigFormBase
{
  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'currencylayer_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames()
  {
    return [
    'currencylayer_currency_converter.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $config = $this->config('currencylayer_currency_converter.settings');

    $form['currencylayer_currency_api_key'] = array(
      '#type' => 'textfield',
      '#title' => $this->t("Currencylayer Currency Api Key"),
      '#default_value' => $config->get('currencylayer_currency_api_key'),
      '#required' => TRUE,
    );

    $form['#validate'][] = '::validateCurencylayerApiKey';

    return parent::buildForm($form, $form_state);
  }

   /**
   * Checks Currencylayer Currency Api Key.
   */
  public function validateCurencylayerApiKey(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('currencylayer_currency_api_key') != '') {
      $url = "http://www.apilayer.net/api/live?access_key=" . $form_state->getValue('currencylayer_currency_api_key');
      $request = curl_init();
      $timeout = 30;
      curl_setopt($request, CURLOPT_URL, $url);
      curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($request, CURLOPT_CONNECTTIMEOUT, $timeout);
      $results = curl_exec($request);
      curl_close($request);
      $currencylayer_converter_rate_array = json_decode($results);

      if ($currencylayer_converter_rate_array->success != 'true') {
        $form_state->setErrorByName('currencylayer_currency_api_key', $this->t('Please provide valid currencylayer API key.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    // Retrieve the configuration
    $this->config('currencylayer_currency_converter.settings')
    // Set the submitted configuration setting
    ->set('currencylayer_currency_api_key', $form_state->getValue('currencylayer_currency_api_key'))
    ->save();

    parent::submitForm($form, $form_state);
  }
}
