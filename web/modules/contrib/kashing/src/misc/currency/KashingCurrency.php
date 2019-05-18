<?php

namespace Drupal\kashing\misc\currency;

/**
 * Kashing currency class.
 */
class KashingCurrency {

  private $currencies;
  public $currencySymbols;
  private $currencyDataPath;

  /**
   * Constructor class.
   */
  public function __construct() {
    $module_handler = \Drupal::service('module_handler');
    $module_path = $module_handler->getModule('kashing')->getPath();

    $this->currencyDataPath = $module_path . '/src/misc/currency/';

    $this->initCurrencySymbols();
    $this->initCurrencies();
  }

  /**
   * Get all currency.
   *
   * @return array
   *   All available currencies
   */
  public function getAll() {
    return $this->currencies;
  }

  /**
   * Get currency name by ISO Code.
   *
   * @param string $iso_code
   *   Currency ISO code.
   *
   * @return string
   *   Currency name
   */
  public function getName($iso_code) {
    if (array_key_exists($iso_code, $this->currencies)) {
      return $this->currencies[$iso_code][0];
    }
    // Currency does not have a symbol.
    return NULL;
  }

  /**
   * Get the currency symbol by ISO Code.
   *
   * @param string $iso_code
   *   ISO currency code.
   *
   * @return string
   *   Currency string
   */
  public function getCurrencySymbol($iso_code) {
    if (array_key_exists($iso_code, $this->currencySymbols)) {
      return $this->currencySymbols[$iso_code];
    }
    // Currency does not have a symbol.
    return FALSE;
  }

  /**
   * Get the array of currency symbols.
   *
   * @return array
   *   Currency symbols
   */
  public function getCurrencySymbolsArray() {
    return $this->currencySymbols;
  }

  /**
   * Assign currency array to the $currency variable.
   */
  public function initCurrencies() {
    $file = $this->currencyDataPath . 'currency-list.php';
    if (is_file($file)) {
      $currency_list_array = include $file;
      $new_currency_array = [];
      if (is_array($currency_list_array)) {
        foreach ($currency_list_array as $code => $data) {
          $currency_symbol = '';
          if (($symbol = $this->getCurrencySymbol($code))) {
            $currency_symbol = ' (' . $symbol . ')';
          }
          else {
            $currency_symbol = ' (' . $code . ')';
          }
          $new_currency_array[$code] = $data[0] . $currency_symbol;
        }
        $this->currencies = $new_currency_array;
      }
    }
  }

  /**
   * Assign currency symbols array to the variable.
   */
  public function initCurrencySymbols() {
    $file = $this->currencyDataPath . 'currency-symbols.php';
    if (is_file($file)) {
      // Get the currency list array from the file.
      $currency_list_array = include $file;
      if (is_array($currency_list_array)) {
        $this->currencySymbols = $currency_list_array;
      }
    }
  }

}
