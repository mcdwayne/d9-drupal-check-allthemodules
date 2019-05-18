<?php

namespace Drupal\commerce_funds;

use Drupal\commerce_store\Entity\Store;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Print default currency class.
 */
final class FundsDefaultCurrency {

  use StringTranslationTrait;

  /**
   * Defines default currency code.
   *
   * @var string
   */
  protected $defaultCurrencyCode;

  /**
   * Class constructor.
   *
   * @param \Drupal\commerce_store\Entity\Store $store
   *   The store.
   */
  public function __construct(Store $store) {
    $this->defaultCurrencyCode = $store->getDefaultCurrencyCode();
  }

  /**
   * Display default currency or "all currencies".
   *
   * @return string
   *   Default currency code or "all currencies".
   */
  public function printConfigureFeesCurrency() {
    $default_currency = $this->defaultCurrencyCode;

    if (!$default_currency) {
      return t('No currency set');
    }

    $currencies = \Drupal::entityTypeManager()->getStorage('commerce_currency')->loadMultiple();
    $currency_qty = count($currencies);

    if ($currency_qty > 1) {
      return t('All currencies');
    }
    elseif ($currency_qty == 1) {
      return $default_currency;
    }
    else {
      throw new \InvalidArgumentException('FundsDefaultCurrency::printConfigureFeesCurrency() called with a malformed store object.');
    }
  }

  /**
   * Display default currency or "Selected currency".
   *
   * @return string
   *   Default currency code or "Selected currency".
   */
  public function printTransactionCurrency() {
    $currencies = \Drupal::entityTypeManager()->getStorage('commerce_currency')->loadMultiple();
    $currency_qty = count($currencies);

    if ($currency_qty > 1) {
      return t('Unit(s) (of selected currency)');
    }
    elseif ($currency_qty == 1) {
      return $this->defaultCurrencyCode;
    }
    else {
      throw new \InvalidArgumentException('FundsDefaultCurrency::printTransactionCurrency() called with a malformed store object.');
    }
  }

}
