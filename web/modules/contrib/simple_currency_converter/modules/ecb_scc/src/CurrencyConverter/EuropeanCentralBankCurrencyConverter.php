<?php
/**
 * Contains EuropeanCentralBankCurrencyConverter.php.
 */

namespace Drupal\ecb_scc\CurrencyConverter;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Drupal\simple_currency_converter\CurrencyConverter\CurrencyConverterInterface;
use SimpleXMLElement;

class EuropeanCentralBankCurrencyConverter implements CurrencyConverterInterface {

  /**
   * Provides HTTP client service
   */
  protected $httpClient;

  /**
   * Constructs a new object.
   */
  public function __construct(ClientInterface $http_client) {
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_client')
    );
  }

  /**
   * @inheritdoc
   */
  public function convert($from_currency, $to_currency, $amount) {
    $output = NULL;

    try {
      $url = 'http://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml';

      $request = $this->httpClient->get($url);

      $xml = $request->getBody();

      $data = $this->parse($xml);

      if (isset($data[$from_currency]) && isset($data[$to_currency])) {
        $from = $data[$from_currency]['rate'];
        $to = $data[$to_currency]['rate'];

        $output = ($to / $from) * $amount;
      }
    }
    catch (RequestException $e) {

    }

    return $output;
  }

  /**
   * Parse the xml.
   */
  private function parse($raw_xml) {
    try {
      $xml = new SimpleXMLElement($raw_xml);
    }
    catch (\Exception $e) {
      return FALSE;
    }

    $data = [];
    foreach ($xml->Cube->Cube->Cube as $rate) {
      $code = (string) $rate['currency'];
      $rate = (string) $rate['rate'];

      $data[$code] = [
        'code' => $code,
        'rate' => $rate,
      ];

    }

    return $data;
  }

  /**
   * @inheritdoc
   */
  public function currencies() {
    return [
      'USD',
      'JPY',
      'BGN',
      'CZK',
      'DKK',
      'GBP',
      'HUF',
      'LTL',
      'PLN',
      'RON',
      'SEK',
      'CHF',
      'NOK',
      'HRK',
      'RUB',
      'TRY',
      'AUD',
      'BRL',
      'CAD',
      'CNY',
      'HKD',
      'IDR',
      'ILS',
      'INR',
      'KRW',
      'MXN',
      'MYR',
      'NZD',
      'PHP',
      'SGD',
      'THB',
      'ZAR',
    ];
  }

}
