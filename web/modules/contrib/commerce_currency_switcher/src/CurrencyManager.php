<?php

namespace Drupal\commerce_currency_switcher;

use Drupal\commerce_price\Calculator;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class CurrencyManager.
 *
 * @package Drupal\commerce_currency_switcher
 */
class CurrencyManager {

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface;
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $configFactory, EntityTypeManagerInterface $entityTypeManager) {
    $this->configFactory = $configFactory;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Returns default commerce currency object
   * @return \Drupal\commerce_price\Entity\Currency
   */
  public function getDefaultCommerceCurrency() {

    $enabled_currencies = $active_currencies = $this->entityTypeManager->getStorage('commerce_currency')
      ->loadByProperties([
        'status' => TRUE,
      ]);

    if (isset($enabled_currencies['USD'])) {
      return $enabled_currencies['USD'];
    }
    else {
      return reset($enabled_currencies);
    }
  }

  public function getExchangeRate($current_currency_code, $target_currency_code) {
    $config = $this->configFactory
      ->getEditable('commerce_currency_switcher.settings');

    $conversion_settings = $config->get('conversion_settings');
    $cross_conversion = $config->get('use_cross_conversion');

    if ($cross_conversion) {
      $conversion_rate = $this->getCrossConversionRate($current_currency_code, $target_currency_code);
    }
    else {
      $conversion_rate = $conversion_settings[$current_currency_code]['rates'][$target_currency_code]['rate'];
    }

    return $conversion_rate;
  }

  public function getCrossConversionRate($current_currency_code, $target_currency_code) {
    $config = $this->configFactory
      ->getEditable('commerce_currency_switcher.settings');

    $conversion_settings = $config->get('conversion_settings');

    $default_currency_code = $this->getDefaultCommerceCurrency()
      ->getCurrencyCode();

    // This is straight forward lookup.
    if ($current_currency_code == $default_currency_code) {
      $conversion_rate = $conversion_settings[$current_currency_code]['rates'][$target_currency_code]['rate'];
      return $conversion_rate;
    }

    // Get reverse conversion rate from current currency to default currency.
    $convert_rate = $conversion_settings[$default_currency_code]['rates'][$current_currency_code]['rate'];

    if (empty($convert_rate)) {
      return;
    }

    $reverse_convert_rate = Calculator::divide('1', $convert_rate);

    if ($target_currency_code == $default_currency_code) {
      return $reverse_convert_rate;
    }

    // Get forward conversion rate from default currency to target currency.
    $forward_convert_rate = $conversion_settings[$default_currency_code]['rates'][$target_currency_code]['rate'];
    $conversion_rate = Calculator::multiply($reverse_convert_rate, $forward_convert_rate);

    return $conversion_rate;
  }

  public static function isCurrencyConversionEnabled() {
    $config = \Drupal::configFactory()
      ->getEditable('commerce_currency_switcher.settings');

    $is_enabled = $config->get('enable_conversion');
    return $is_enabled;
  }

}
