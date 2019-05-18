<?php

namespace Drupal\messagebird;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Class MessagebirdServiceLookup.
 *
 * @package Drupal\messagebird
 */
class MessageBirdLookup implements MessageBirdLookupInterface {

  /**
   * MessageBird Lookup object.
   *
   * @var \MessageBird\Objects\Lookup
   */
  protected $lookup;

  /**
   * MessageBird Client object.
   *
   * @var \MessageBird\Client
   */
  protected $client;

  /**
   * MessageBird Exception object.
   *
   * @var \Drupal\messagebird\MessageBirdExceptionInterface
   */
  protected $exception;

  /**
   * MessageBird Configuration.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * MessageBirdLookup constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config Factory object.
   * @param \Drupal\messagebird\MessageBirdClientInterface $client
   *   MessageBird Client object.
   * @param \Drupal\messagebird\MessageBirdExceptionInterface $exception
   *   MessageBird Exception object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, MessageBirdClientInterface $client, MessageBirdExceptionInterface $exception) {
    $this->config = $config_factory->get('messagebird.settings');
    $this->client = $client->getClient();
    $this->exception = $exception;
  }

  /**
   * Look up a telephone number.
   *
   * Checks if the telephone number could be a valid number.
   * This will not check for a active number.
   *
   * @param string $number
   *   Phone number, accepts multiple formats.
   * @param string $country_code
   *   (optional) ISO 3166-2 country code.
   */
  public function lookupNumber($number, $country_code = NULL) {
    try {
      $this->lookup = NULL;
      $this->lookup = $this->client->lookup->read($number, $country_code);
      $this->debugLookup();
    }
    catch (\Exception $e) {
      $this->exception->logError($e);
    }
  }

  /**
   * Check if the lookup was a success.
   *
   * @return bool
   *   TRUE on successful look up, FALSE otherwise.
   */
  public function hasValidLookup() {
    return !is_null($this->lookup);
  }

  /**
   * Get URL of lookup info.
   *
   * @return string
   *   URL of lookup info.
   */
  public function getHref() {
    return $this->lookup->getHref();
  }

  /**
   * Get the type of the telephone number..
   *
   * @return string
   *   Type of Telephone number.
   */
  public function getType() {
    return $this->lookup->getType();
  }

  /**
   * Get the origin country code of the telephone number.
   *
   * @return string
   *   ISO 3166-2 country code
   */
  public function getCountryCode() {
    return $this->lookup->getCountryCode();
  }

  /**
   * Get the country prefix of the telephone number.
   *
   * @return string
   *   Country prefix number.
   */
  public function getCountryPrefix() {
    return $this->lookup->getCountryPrefix();
  }

  /**
   * Get the telephone number.
   *
   * Useful for numeric storage inside array's.
   *
   * @return int
   *   Telephone number itself without leading zero's
   */
  public function getFormatNumber() {
    return $this->lookup->getPhoneNumber();
  }

  /**
   * Get the international format of the telephone number.
   *
   * This is the longest format with country prefix and appropriated dashes.
   *
   * @return string
   *   International format of the telephone number.
   */
  public function getFormatInternational() {
    return $this->lookup->getFormats()->international;
  }

  /**
   * Get the national format of the telephone number.
   *
   * This format has no country prefix.
   *
   * @return string
   *   National format of the telephone number.
   */
  public function getFormatNational() {
    return $this->lookup->getFormats()->national;
  }

  /**
   * Get the e164 format of the telephone number.
   *
   * This format contains only numbers and plus sign.
   *
   * @return string
   *   e164 format of the telephone number.
   */
  public function getFormatE164() {
    return $this->lookup->getFormats()->e164;
  }

  /**
   * Get the rfc3966 of the telephone number.
   *
   * Useful for creating a telephone URL.
   *
   * @return string
   *   rfc3966 format of the telephone number.
   */
  public function getFormatRfc3966() {
    return $this->lookup->getFormats()->rfc3966;
  }

  /**
   * Display Message information.
   */
  protected function debugLookup() {
    if ($this->config->get('debug.mode') && $this->hasValidLookup()) {

      $debug_callbacks = array(
        'getHref' => t('Href'),
        'getType' => t('Type'),
        'getCountryCode' => t('Country code'),
        'getCountryPrefix' => t('Country prefix'),
        'getFormatE164' => t('e164'),
        'getFormatInternational' => t('International'),
        'getFormatNational' => t('National'),
        'getFormatNumber' => t('Number'),
        'getFormatRfc3966' => t('RFC3966'),
      );

      foreach ($debug_callbacks as $callback => $title) {
        $value = call_user_func(array($this, $callback));
        if ($value) {
          drupal_set_message(t('@messagebird_debug_key: @messagebird_debug_value', array(
            '@messagebird_debug_key' => $title,
            '@messagebird_debug_value' => var_export($value, TRUE),
          )));
        }
      }
    }
  }

}
