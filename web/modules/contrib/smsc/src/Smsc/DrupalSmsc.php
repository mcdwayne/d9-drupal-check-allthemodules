<?php

/**
 * @file
 */

namespace Drupal\smsc\Smsc;


use Drupal\Core\StringTranslation\StringTranslationTrait;
use Smsc\Services\SmscBalance;
use Smsc\Services\SmscMessage;
use Smsc\Services\SmscSenders;


/**
 * Class DrupalSmsc.
 */
class DrupalSmsc implements DrupalSmscInterface {

  use StringTranslationTrait;

  /**
   * @var null|\Smsc\Settings\Settings
   */
  protected $settings;

  /**
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $smscConfig;

  /**
   * Constructs a new DrupalSmsc object.
   */
  public function __construct() {
    $this->smscConfig = \Drupal::config('smsc.config');
    $this->settings   = DrupalSmscSettings::init();
  }

  /**
   * Get settings.
   *
   * @return null|\Smsc\Settings\Settings
   */
  public function getSettings() {
    return $this->settings;
  }

  /**
   * Get available sender ID's.
   *
   * @return array
   */
  public static function senders() {
    $settings = DrupalSmscSettings::init();

    $senders = new SmscSenders($settings);
    $senders->getSenders();
    $senders->send();

    return $senders->results();
  }

  /**
   * Send SMS.
   *
   * @param string $phones
   * @param string $message
   * @param array  $options
   *
   * @return mixed
   */
  public static function sendSms($phones, $message, $options = []) {
    $settings = DrupalSmscSettings::init();

    $sms = new SmscMessage($settings, $phones, $message, $options);
    $sms->send();

    return $sms;
  }

  /**
   * Get SMSC config.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   */
  public function getConfig() {
    return $this->smscConfig;
  }

  /**
   * Get available hosts.
   *
   * @return array
   */
  public function getHosts() {
    $hosts = $this->settings->getApiHosts();

    return array_combine($hosts, $hosts);
  }

  /**
   * Get available sender ID's.
   *
   * @return null|array
   */
  public function getSenders() {
    $senders = &drupal_static(__FUNCTION__); // Can be replaced with the `__METHOD__`
    $cid     = 'smsc:senders';

    $senders = $this->getFromCache($cid, 'getSendersFromApi');

    return $senders;
  }

  /**
   * Get senders from API.
   *
   * @return array
   */
  public function getSendersFromApi() {
    $senders = [];

    if ($this->settings->valid()) {
      $sendersAvailable = self::senders();
    }

    if (isset($sendersAvailable) && count($sendersAvailable)) {
      $senders = array_combine($sendersAvailable, $sendersAvailable);
    }

    $senders = ['' => $this->t('Default')->render()] + $senders;

    return $senders;
  }

  /**
   * Get balance amount.
   *
   * @return float
   */
  public function getBalanceAmount() {
    $balance = &drupal_static(__FUNCTION__); // Can be replaced with the `__METHOD__`
    $cid     = 'smsc:balance:amount';

    $balance = $this->getFromCache($cid, 'getBalanceAmountFromApi');

    return $balance;
  }

  /**
   * Get balance amount from API.
   *
   * @return float
   */
  public function getBalanceAmountFromApi() {
    $settings = DrupalSmscSettings::init();

    $balance = new SmscBalance($settings);

    $balance->send();

    return $balance->getAmount();
  }

  /**
   * Get balance currency.
   *
   * @return string
   */
  public function getBalanceCurrency() {
    $currency = &drupal_static(__FUNCTION__); // Can be replaced with the `__METHOD__`
    $cid      = 'smsc:balance:currency';

    $currency = $this->getFromCache($cid, 'getBalanceCurrencyFromApi');

    return $currency;
  }

  /**
   * Get balance currency from API.
   *
   * @return string
   */
  public function getBalanceCurrencyFromApi() {
    $settings = DrupalSmscSettings::init();

    $currency = new SmscBalance($settings);

    $currency->send();

    return $currency->getCurrency();
  }

  /**
   * Get cached data.
   *
   * @param $cid
   * @param $callback
   *
   * @return mixed
   * @throws \Exception
   */
  private function getFromCache($cid, $callback) {
    $data = NULL;

    if (!method_exists($this, $callback)) {
      throw new \Exception("Method \"$callback\" do not exists!");
    }

    if ($cache = \Drupal::cache()->get($cid)) {
      $data = $cache->data;
    }
    else {
      $data = $this->$callback();

      \Drupal::cache()->set($cid, $data, (time() + 60));
    }

    return $data;
  }
}
