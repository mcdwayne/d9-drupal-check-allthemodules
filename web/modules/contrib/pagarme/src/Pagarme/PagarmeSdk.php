<?php

namespace Drupal\pagarme\Pagarme;

/**
 * @file Class encapsulating the business logic related to integrate Pagar.me with
 * Drupal structures.
 */
class PagarmeSdk {

  public $pagarme;
  protected $api_key;
  protected $plugin_configuration;

  public function __construct($api_key = null) {
    if ($api_key) {
      $this->setup($api_key);
    }
  }

  public function getApiKey() {
    return $this->api_key;
  }

  public function setPluginConfiguration($configuration) {
    $this->plugin_configuration = $configuration;
  }

  public function getPluginConfiguration() {
    return $this->plugin_configuration;
  }

  public function getCompanyInfo() {
    $cid = 'pagarme_company_info';
    if($cached = \Drupal::cache()->get($cid))  {
      $company_info = $cached->data;
    } else {
      $company_info = $this->pagarme->company()->info();
      $expire = time() + 60*60;
      \Drupal::cache()->set($cid, $company_info, $expire);
    }
    return $company_info;
  }

  /**
   * Setup Pagar.me PHP SDK
   */
  protected function setup($api_key) {
    $this->loadLibrary();
    $this->api_key = $api_key;
    $this->pagarme = new \PagarMe\Sdk\PagarMe($this->api_key);
  }

  /**
   * Returns TRUE if the \PagarMe\Sdk\PagarMe class is available.
   */
  protected function loadLibrary() {

    $pagarme_lib_url = 'https://github.com/pagarme/pagarme-php';
    $path = 'libraries/pagarme-php';

    if (file_exists($path)) {
      require_once $path . '/vendor/autoload.php';
    }
    else {
      $message = 'Pagar.me PHP SDK was not found on "' . $path . '". Download it in ' . $pagarme_lib_url;
      watchdog('pagarme_error', t($message), array(), WATCHDOG_ERROR);
      throw new \Exception(t($message));
    }
    return class_exists('\PagarMe\Sdk\PagarMe') ? TRUE : FALSE;
  }
}
