<?php

namespace Drupal\payex\Service;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Utility\Error;
use Drupal\payex\Entity\PayExSettingInterface;
use Drupal\payex\PayEx\PayExAPIException;
use Drupal\payex\PayEx\PxOrder;

/**
 * Class PayExApi
 *
 * Public API class which should be used to interact with PayEx.
 */
class PayExApi {

  /**
   * The configuration used for the API calls.
   *
   * @var PayExSettingInterface
   */
  protected $config;

  /**
   * The logger used to log exceptions.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The channel we want to log exceptions on.
   *
   * @var string
   */
  protected $loggerChannel;

  /**
   * The PxOrder api class used for api calls to PayEx.
   *
   * @var PxOrder
   */
  protected $pxo;

  /**
   * Default settings used in API calls.
   *
   * @var array
   */
  protected $settings;

  /**
   * Constructs a PayExApi class.
   *
   * @param PayExSettingInterface $config
   *   The configuration of the API.
   * @param LanguageManager $languageManager
   *   The Drupal language manager.
   */
  public function __construct(PayExSettingInterface $config, LanguageManager $languageManager) {
    $this->config = $config;
    $this->loggerChannel = 'payex';
    $this->languageManager = $languageManager;
  }

  /**
   * Complate a PayEx payment
   *
   * Wrapper for PXOrder.Complete
   *
   * @param array $params
   *   Array of date to send to PayEx
   *
   * @return array|bool
   *   Returns the result from complete or FALSE if API call failed.
   */
  public function complete(array $params) {
    // Add default settings to the params
    $params += $this->getSettings();
    $api = $this->getPxOrder();
    try {
      $result = $api->complete($params);
    } catch (PayExAPIException $e) {
      $this->logException($e);
      $result = FALSE;
    }
    if (!isset($result['orderStatus'])) {
      $this->getLogger()->error('Unable to get result from PayEx, params sent: @params', ['@params' => print_r($params, 1)]);
      return FALSE;
    }
    return $result;
  }

  /**
   * Returns the logger channel.
   *
   * @return \Drupal\Core\Logger\LoggerChannelInterface
   *   The logger channel
   */
  public function getLogger() {
    if (!$this->logger) {
      $logger_factory = \Drupal::service('logger.factory');
      $this->logger = $logger_factory->get($this->loggerChannel);
    }
    return $this->logger;
  }

  /**
   * Gets the PayEx language from a language code.
   *
   * Defaults to the current Drupal language.
   *
   * @param null|string $lang_code
   *   The language code.
   *
   * @return string
   *   The PayEx language code.
   */
  public function getPayExLanguage($lang_code = NULL) {
    if (!$lang_code) {
      $lang_code = $this->languageManager->getCurrentLanguage()->getId();
    }

    $payex_lang = [
      'cs' => 'cs-CS',
      'da' => 'da-DK',
      'de' => 'de-DE',
      'en' => 'en-US',
      'es' => 'es-ES',
      'fi' => 'fi-FI',
      'fr' => 'fr-FR',
      'hu' => 'hu-HU',
      'nb' => 'nb-NO',
      'pl' => 'pl-PL',
      'sv' => 'sv-SE',
    ];

    if (isset($payex_lang[$lang_code])) {
      return $payex_lang[$lang_code];
    }
    else {
      return $payex_lang['en'];
    }
  }

  /**
   * Get the purchase operation to use for payments.
   *
   * @return string
   *   The purchase operation.
   */
  public function getPurchaseOperation() {
    return $this->config->getPurchaseOperation();
  }

  /**
   * Get the test status for the API.
   *
   * @return boolean
   *   Boolean indication for the test status.
   */
  public function getTest() {
    return $this->config->isTest();
  }

  /**
   * Initialize a payex a order.
   *
   * PXOrder.Initialize
   *
   * @param array $params
   *   The params used for the initialize request
   *
   * @return array|bool
   *   Returns the result from initialize or FALSE if initialize failed.
   */
  public function initialize(array $params) {
    // Add default settings to the params
    $params += $this->getSettings();
    $api = $this->getPxOrder();
    try {
      $result = $api->initialize($params);
    } catch (PayExAPIException $e) {
      $this->logException($e);
      $result = FALSE;
    }
    return $result;

  }

  /** Internal methods */

  /**
   * Gets the initialized PxOrder api class.
   *
   * @return PxOrder
   */
  protected function getPxOrder() {
    if (empty($this->pxo)) {
      $this->pxo = new PxOrder($this->config->getMerchantAccount(), $this->config->getEncryptionKey(), $this->config->isLive());
    }
    return $this->pxo;
  }

  /**
   * Extract the payex settings from the PayExSettingInterface config entity.
   */
  protected function extractSettings() {
    $this->settings = [
      'accountNumber' => $this->config->getMerchantAccount(),
      'currency' => $this->config->getDefaultVat(),
      'vat' => $this->config->getDefaultVat(),
      'test' => $this->config->isTest(),
    ];
    if ($this->config->getPPG() == '2.0') {
      $this->settings['additionalValues'] = 'RESPONSIVE=1';
    }
  }

  /**
   * Get the settings used in API calls.
   *
   * @return array
   */
  protected function getSettings() {
    if (empty($this->settings)) {
      $this->extractSettings();
    }
    return $this->settings;
  }

  /**
   * Log an exception.
   *
   * @param \Exception $e
   *   The exception to log.
   */
  private function logException(\Exception $e) {
    $logger = $this->getLogger();
    $logger->error('%type: @message in %function (line %line of %file).', Error::decodeException($e));
  }
}
