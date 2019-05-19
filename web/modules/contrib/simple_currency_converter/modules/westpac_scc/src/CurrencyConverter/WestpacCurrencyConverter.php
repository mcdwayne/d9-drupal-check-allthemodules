<?php
/**
 * Contains WestpacCurrencyConverter.php.
 */

namespace Drupal\westpac_scc\CurrencyConverter;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Drupal\simple_currency_converter\CurrencyConverter\CurrencyConverterInterface;
use SimpleXMLElement;

class WestpacCurrencyConverter implements CurrencyConverterInterface {

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
      $url = 'http://www.westpac.co.nz/olcontent/olcontent.nsf/fx.xml';

      $request = $this->httpClient->get($url);

      $xml = $request->getBody();

      $data = $this->parse($xml);

      if (isset($data[$from_currency]) && isset($data[$to_currency])) {
        // Bank Buys At (1).
        // Bank Sells At (4).
        $from = $data[$from_currency]['rate'][1];
        $to   = $data[$to_currency]['rate'][1];

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
    foreach ($xml->country as $country) {

      // 0 = Indicative.
      // 1 = BankBuysTT.
      // 2 = BankBuysOD.
      // 3 = BankBuysNotes.
      // 4 = BankSellsAt.
      $code = (string) $country['code'];
      $rate = [
        (string) $country->rate[0],
        (string) $country->rate[1],
        (string) $country->rate[2],
        (string) $country->rate[3],
        (string) $country->rate[4],
      ];

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
      'NZD',
      'USD',
      'GBP',
      'AUD',
      'EUR',
      'JPY',
      'CAD',
      'CHF',
      'XPF',
      'DKK',
      'FJD',
      'HKD',
      'INR',
      'NOK',
      'PKR',
      'PGK',
      'PHP',
      'SGD',
      'SBD',
      'ZAR',
      'LKR',
      'SEK',
      'THB',
      'TOP',
      'VUV',
      'WST',
    ];
  }

}
