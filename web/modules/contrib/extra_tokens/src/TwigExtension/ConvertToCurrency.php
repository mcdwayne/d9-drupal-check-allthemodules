<?php
namespace Drupal\extra_tokens\TwigExtension;
use Drupal\Core\Config\ConfigValueException;

class ConvertToCurrency extends \Twig_Extension {
  const BASE_CURRENCY = 'USD';
  const CURRENCIES = [
    'UAH' => 'грн',
    'GBR' => '£',
    'EUR' => '€',
    'CKZ' => 'Kč',
    'USD' => '$',
  ];

  public function getFilters() {
    return [
      new \Twig_SimpleFilter('convert_to_currency', [$this, 'convertToCurrency']),
      new \Twig_SimpleFilter('currency_verbose', [$this, 'currencyVerbose']),
    ];
  }
  public static function convertToCurrency($amount, $currency, $to_currency, $round=true) {
    if($currency == $to_currency) {
      return $amount;
    }
    $config = \Drupal::config('extra_tokens.settings');
    $CURRENCIES = $config->get('CURRENCIES');
    $BASE_CURRENCY = $config->get('BASE_CURRENCY');
    if(isset($CURRENCIES[$to_currency]) === false) {

        throw new ConfigValueException("Currency {$to_currency} is not supported");
    }
    if($currency != $BASE_CURRENCY) {
      throw new ConfigValueException("Currency {$currency} is not supported");
    }
    $exchange_rate = $config->get('EXCHANGE_RATE_'.$to_currency);
    $result = bcmul($amount, $exchange_rate, 2);
    if($round) {
      $result = number_format($result, 0, '.', '');
    }
    return $result;
  }

  public static function currencyVerbose($currency) {
    if(!$currency) {
      return '';
    }
    $config = \Drupal::config('extra_tokens.settings');
    $CURRENCIES = $config->get('CURRENCIES');
    return $CURRENCIES[$currency];
  }
}