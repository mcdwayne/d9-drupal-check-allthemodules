<?php

namespace Drupal\kashing\form\View;

use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Component\Utility\Html;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\kashing\Entity\KashingValid;

/**
 * Kashing Form Configuration Class.
 */
class KashingFormConfiguration {

  /**
   * Configuration Page content.
   */
  public function addConfigurationPage(array &$form) {

    $config = \Drupal::config('kashing.settings');

    $form['settings_mode'] = [
      '#type' => 'details',
      '#group' => 'kashing_settings',
      '#title' => t('Configuration'),
      '#description' => t('<a target="_blank" href=":uri">Retrieve your Kashing API Keys</a>', [':uri' => 'https://www.kashing.co.uk/docs/#how-do-i']),
    ];

    $form['settings_mode']['test_mode'] = [
      '#type' => 'fieldset',
      '#title' => t('Kashing Mode'),
    ];

    // Kashing Mode.
    $form['settings_mode']['test_mode']['radio_buttons'] = [
      '#type' => 'radios',
      '#options' => ['test' => t('Yes'), 'live' => t('No')],
      '#title' => t('Test Mode'),
      '#default_value' => $config->get('mode') ? Html::escape($config->get('mode')) : 'test',
      '#attributes' => [
        'id' => 'kashing-radio-buttons',
      ],
      '#required' => TRUE,
      '#description' => t('Activate or deactivate the plugin Test Mode. When Test Mode is activated, no credit card payments are processed.'),
    ];

    // Kashing Test API values.
    $form['settings_mode']['test_mode_keys'] = [
      '#type' => 'fieldset',
      '#title' => t('Test'),
    ];

    $form['settings_mode']['test_mode_keys']['test_merchant_id'] = [
      '#type' => 'textfield',
      '#title' => t('Test Merchant ID'),
      '#default_value' => Html::escape($config->get('key')['test']['merchant']),
      '#attributes' => [
        'id' => 'kashing-test-merchant',
      ],
      '#required' => TRUE,
      '#description' => t('Enter your testing Merchant ID.'),
    ];

    $form['settings_mode']['test_mode_keys']['test_secret_key'] = [
      '#type' => 'textfield',
      '#title' => t('Test Secret Key'),
      '#default_value' => Html::escape($config->get('key')['test']['secret']),
      '#attributes' => [
        'id' => 'kashing-test-secret',
      ],
      '#required' => TRUE,
      '#description' => t('Enter your testing Kashing Secret Key.'),
    ];

    // Kashing Live API values.
    $form['settings_mode']['live_mode_keys'] = [
      '#type' => 'fieldset',
      '#title' => t('Live'),
    ];

    $form['settings_mode']['live_mode_keys']['live_merchant_id'] = [
      '#type' => 'textfield',
      '#title' => t('Live Merchant ID'),
      '#default_value' => Html::escape($config->get('key')['live']['merchant']),
      '#attributes' => [
        'id' => 'kashing-live-merchant',
      ],
      '#required' => TRUE,
      '#description' => t('Enter your live Merchant ID.'),
    ];

    $form['settings_mode']['live_mode_keys']['live_secret_key'] = [
      '#type' => 'textfield',
      '#title' => t('Live Secret Key'),
      '#default_value' => Html::escape($config->get('key')['live']['secret']),
      '#attributes' => [
        'id' => 'kashing-live-secret',
      ],
      '#required' => TRUE,
      '#description' => t('Enter your live Kashing Secret Key.'),
    ];

    // Ajax submit button.
    $form['settings_mode']['actions'] = [
      '#type' => 'actions',
    ];

    $form['settings_mode']['actions']['kashing_form_save'] = [
      '#type' => 'button',
      '#name' => 'kashing_form_save_button_name',
      '#value' => t('Save settings'),
      '#ajax' => [
        'callback' => 'Drupal\kashing\form\View\KashingFormConfiguration::kashingConfigurationSave',
        'wrapper' => 'kashing-configuration-result',
        'progress' => [
          'type' => 'throbber',
          'message' => t('Saving...'),
        ],
      ],
      '#suffix' => '<div id="kashing-configuration-result"></div>',
    ];
  }

  /**
   * Configuration Page save function.
   */
  public function kashingConfigurationSave(array &$form, FormStateInterface $form_state) {

    $configuration_errors = FALSE;
    $error_info = '<strong>' . t('Missing fields:') . ' </strong><ul>';
    $ajax_response = new AjaxResponse();

    $test_mode = $form_state->getValue('radio_buttons');

    $test_merchant_id = Html::escape($form_state->getValue('test_merchant_id'));
    $test_secret_key = Html::escape($form_state->getValue('test_secret_key'));

    $live_merchant_id = Html::escape($form_state->getValue('live_merchant_id'));
    $live_secret_key = Html::escape($form_state->getValue('live_secret_key'));

    // Validate all configuration fields.
    $kashing_validate = new KashingValid();

    // Test merchant id.
    if (!$kashing_validate->validateRequiredField($test_mode)) {
      $ajax_response->addCommand(new InvokeCommand('#kashing-radio-buttons', 'addClass', ['error']));
      $configuration_errors = 'true';
      $error_info .= '<li>' . t('Test Mode') . '</li>';
    }
    else {
      $ajax_response->addCommand(new InvokeCommand('#kashing-radio-buttons', 'removeClass', ['error']));
    }

    // Test merchant id.
    if (!$kashing_validate->validateRequiredField($test_merchant_id)) {
      $ajax_response->addCommand(new InvokeCommand('#kashing-test-merchant', 'addClass', ['error']));
      $configuration_errors = 'true';
      $error_info .= '<li>' . t('Test Merchant ID') . '</li>';
    }
    else {
      $ajax_response->addCommand(new InvokeCommand('#kashing-test-merchant', 'removeClass', ['error']));
    }

    // Test secret key.
    if (!$kashing_validate->validateRequiredField($test_secret_key)) {
      $ajax_response->addCommand(new InvokeCommand('#kashing-test-secret', 'addClass', ['error']));
      $configuration_errors = 'true';
      $error_info .= '<li>' . t('Test Secret Key') . '</li>';
    }
    else {
      $ajax_response->addCommand(new InvokeCommand('#kashing-test-secret', 'removeClass', ['error']));
    }

    // Live merchant id.
    if (!$kashing_validate->validateRequiredField($live_merchant_id)) {
      $ajax_response->addCommand(new InvokeCommand('#kashing-live-merchant', 'addClass', ['error']));
      $configuration_errors = 'true';
      $error_info .= '<li>' . t('Live Merchant ID') . '</li>';
    }
    else {
      $ajax_response->addCommand(new InvokeCommand('#kashing-live-merchant', 'removeClass', ['error']));
    }

    // Live secret key.
    if (!$kashing_validate->validateRequiredField($live_secret_key)) {
      $ajax_response->addCommand(new InvokeCommand('#kashing-live-secret', 'addClass', ['error']));
      $configuration_errors = 'true';
      $error_info .= '<li>' . t('Live Secret Key') . '</li>';
    }
    else {
      $ajax_response->addCommand(new InvokeCommand('#kashing-live-secret', 'removeClass', ['error']));
    }

    // Display any errors or save configuration.
    if ($configuration_errors) {
      $ajax_response->addCommand(new InvokeCommand('#kashing-configuration-result',
        'removeClass', ['messages--status messages']));
      $ajax_response->addCommand(new InvokeCommand('#kashing-configuration-result',
        'addClass', ['messages--error messages']));
      $ajax_response->addCommand(new HtmlCommand('#kashing-configuration-result', $error_info));
    }
    else {
      $ajax_response->addCommand(new InvokeCommand('#kashing-configuration-result',
        'removeClass', ['messages--error messages']));
      $ajax_response->addCommand(new HtmlCommand('#kashing-configuration-result', t('Configuration saved!')));
      $ajax_response->addCommand(new InvokeCommand('#kashing-configuration-result',
        'addClass', ['messages--status messages']));
      KashingFormConfiguration::configurationSubmitProcess($form, $form_state);
    }

    return $ajax_response;
  }

  /**
   * Configuration Page submit function.
   */
  public static function configurationSubmitProcess(array &$form, FormStateInterface $form_state) {

    $config = \Drupal::service('config.factory')->getEditable('kashing.settings');

    $mode = $form_state->getValue('radio_buttons');
    if ($mode) {
      $config->set('mode', $mode);
    }

    $test_merchant_id = $form_state->getValue('test_merchant_id');
    if ($test_merchant_id) {
      $config->set('key.test.merchant', $test_merchant_id);
    }

    $test_secret_key = $form_state->getValue('test_secret_key');
    if ($test_secret_key) {
      $config->set('key.test.secret', $test_secret_key);
    }

    $live_merchant_id = $form_state->getValue('live_merchant_id');
    if ($live_merchant_id) {
      $config->set('key.live.merchant', $live_merchant_id);
    }

    $live_secret_key = $form_state->getValue('live_secret_key');
    if ($live_secret_key) {
      $config->set('key.live.secret', $live_secret_key);
    }

    // Url to further use.
    $base_url = Url::fromUri('internal:/')->setAbsolute()->toString();
    if ($base_url) {
      $config->set('base', $base_url);
    }

    $config->save();
  }

}
