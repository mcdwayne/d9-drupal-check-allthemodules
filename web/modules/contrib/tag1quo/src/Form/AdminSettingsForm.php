<?php

namespace Drupal\tag1quo\Form;

use Drupal\tag1quo\Adapter\Core\Core;
use Drupal\tag1quo\Adapter\Form\FormState;
use Drupal\tag1quo\Heartbeat;
use Drupal\tag1quo\VersionedClass;

/**
 * Class AdminSettingsForm.
 */
class AdminSettingsForm extends VersionedClass {

  /**
   * The Core adapter.
   *
   * @var \Drupal\tag1quo\Adapter\Core\Core
   */
  protected $core;

  /**
   * The Tag1 Quo configuration.
   *
   * @var \Drupal\tag1quo\Adapter\Config\Config
   */
  protected $config;

  /**
   * AdminSettingsForm constructor.
   */
  public function __construct() {
    $this->core = Core::create();
    $this->config = $this->core->config('tag1quo.settings');
  }

  /**
   * Creates a new AdminSettingsForm instance.
   *
   * @return static
   */
  public static function create() {
    return static::createVersionedStaticInstance();
  }

  /**
   * Builds the form.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\tag1quo\Adapter\Form\FormState $formState
   *   The form state.
   *
   * @return array
   *   The render array.
   */
  public function build(array $form, FormState $formState) {
    $curl = !!$formState->getValue('curl.enabled', $this->config->get('curl.enabled'));
    $debug = !!$formState->getValue('debug.enabled', $this->config->get('debug.enabled'));
    $enabled = !!$formState->getValue('enabled', $this->config->get('enabled'));
    $token = $formState->getValue('token', $this->config->get('api.token'));

    if ($enabled && $token === '') {
      $this->core->setMessage('No information will be sent to Tag1 Quo until you configure your token.', 'warning');
    }

    $form['enabled'] = $this->core->buildElement(array(
      '#type' => 'checkbox',
      '#title' => $this->core->t('Enabled'),
      '#description' => $this->core->t('When checked, this module will send information about your website to Tag1 Quo, a paid service.'),
      '#default_value' => $enabled,
    ));

    $form['api'] = $this->core->buildElement(array(
      '#type' => 'container',
      '#tree' => TRUE,
    ));

    $form['api']['token'] = $this->core->buildElement(array(
      '#type' => 'textfield',
      '#title' => $this->core->t('Token'),
      '#description' => $this->core->t('Your secure login token for the Tag1 Quo'),
      '#default_value' => $token,
      '#maxlength' => 255,
    ));

    $open = $debug || $curl;

    $form['advanced'] = $this->core->buildElement(array(
      '#type' => 'details',
      '#title' => $this->core->t('Advanced'),
      '#open' => $open,
      '#tree' => FALSE,
    ));

    $form['advanced']['site_identifier'] = $this->core->buildElement(array(
      '#type' => 'item',
      '#title' => $this->core->t('Site identifier'),
      '#markup' => $this->core->siteIdentifier(),
    ));

    $form['advanced']['debug'] = $this->core->buildElement(array(
      '#type' => 'details',
      '#title' => $this->core->t('Debug'),
      '#tree' => TRUE,
      '#open' => $debug,
    ));

    $form['advanced']['debug']['enabled'] = $this->core->buildElement(array(
      '#type' => 'checkbox',
      '#title' => $this->core->t('Enabled'),
      '#description' => $this->core->t('When checked, debug mode is enabled for verbose output and logging.'),
      '#default_value' => $debug,
    ));

    $curl_exists = function_exists('curl_exec');
    if ($curl_exists) {
      $curl_description = $this->core->t("cURL is enabled in your PHP installation so you can enable it if needed. This allows configuration of more advanced options such as phoning home through a proxy. It is not recommended you enable this unless you explicitly need functionality not provided by Drupal's built in <code>drupal_http_request()</code> function.");
      $curl_enabled_description = $this->core->t("When checked, use cURL to phone home instead of Drupal's built in <code>drupal_http_request()</code> function.");
    }
    else {
      $curl_description = $this->core->t("cURL is not enabled in your PHP installation. To enable, review !documentation.  This would allow configuration of more advanced options such as phoning home through a proxy. It is not recommended you enable this unless you explicitly need functionality not provided by Drupal's built in <code>drupal_http_request()</code> function.", array(
        '!documentation' => static::l('the PHP cURL documentation', 'https://secure.php.net/manual/en/curl.setup.php'),
      ));
      $curl_enabled_description = $this->core->t("cURL is not enabled in your PHP installation. If enabled, you can then check this box to use cURL to phone home instead of Drupal's built in <code>drupal_http_request()</code> function.");
    }

    // @todo Add additional UI for common cURL options.
    $form['advanced']['curl'] = $this->core->buildElement(array(
      '#type' => 'details',
      '#title' => $this->core->t('cURL'),
      '#tree' => TRUE,
      '#open' => $curl,
      '#description' => $this->core->t("By default, Quo uses Drupal's <code>drupal_http_request()</code> function to phone home.") . ' ' . $curl_description,
    ));

    $form['advanced']['curl']['enabled'] = $this->core->buildElement(array(
      '#type' => 'checkbox',
      '#title' => 'Enable cURL',
      '#disabled' => !$curl_exists,
      '#default_value' => $curl,
      '#description' => $curl_enabled_description,
    ));

    return $form;
  }

  /**
   * Validates the form.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\tag1quo\Adapter\Form\FormState $formState
   *   The form state.
   */
  public function validate(array $form, FormState $formState) {
    $enabled = !!$formState->getValue('enabled');

    // If not enabled, don't bother with other validation.
    if (!$enabled) {
      return;
    }

    $apiToken = $formState->getValue('api.token');
    $debug = !!$formState->getValue('debug.enabled');
    $curl = !!$formState->getValue('curl.enabled');
    $heartbeat = Heartbeat::create($this->core)
      ->setApiToken($apiToken)
      ->setEnabled($enabled)
      ->setDebugMode($debug)
      ->setUseCurl($curl);

    // Validate the heartbeat and API token.
    $heartbeat->validate(TRUE);

    // Determine if there are any errors.
    if (($error = $heartbeat->getError()) && ($errorMessage = $heartbeat->getErrorMessage())) {
      switch ($error) {
        case $heartbeat::ERROR_TOKEN_INVALID:
        case $heartbeat::ERROR_TOKEN_MISSING:
        case $heartbeat::ERROR_SERVER_TOKEN_INVALID:
          $formState->setErrorByName('api.token', $errorMessage);
        return;

        default:
          $this->core->setMessage($errorMessage, 'error');
          return;
      }
    }

    $this->core->setMessage($this->core->t('Successfully validated token and communicated with Tag1 Quo Server.'));
  }

  /**
   * Submits the form.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\tag1quo\Adapter\Form\FormState $formState
   *   The form state.
   */
  public function submit(array $form, FormState $formState) {
    $keys = array(
      'api.token',
      'debug.enabled',
      'curl.enabled',
      'enabled',
    );
    // Remove all current config.
    $this->config->delete();
    foreach ($keys as $key) {
      $value = $formState->getValue($key);
      $this->config->set($key, $value);
    }
    $this->config->save();
  }

}
