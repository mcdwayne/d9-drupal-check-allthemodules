<?php

namespace Drupal\stocks_api\exchanges;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

/**
 * API to retrieve summary stock exchange info.
 *
 * @ingroup stocks_api
 */
class NASDAQExchangeAPI implements ExchangeAPIInterface {

  const EXCHANGE_SUMMARY_CSV_REQUEST_PREFIX = 'http://www.nasdaq.com/screening/companies-by-industry.aspx?exchange=';
  const EXCHANGE_SUMMARY_CSV_REQUEST_SUFFIX = '&render=download';

  /**
   * The HTTP client to fetch the feed data with.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * folio_stock_exchange settings
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $stockAPISettings;

  /**
   * Constructs a NYSEExchangeAPI.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   A Guzzle client object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ClientInterface $http_client) {
    $this->stockAPISettings = $config_factory->get('stocks_api.settings');
    $this->httpClient = $http_client;
  }

  /**
   * Requests summaries of the enabled exchanges.
   *
   * @return array
   *   Map of exchange contents, in the following format:
   *      ['NYSE'] => '"Symbol","Name","LastSale","MarketCap","ADR TSO","IPOyear","Sector","Industry","Summary Quote",
   *                   "YI","111, Inc.","9.35","67086250","7175000","2018","Health Care","Medical/Nursing Services","https://www.nasdaq.com/symbol/yi",'
   *   The summary of the exchange in CSV format, in the following table format:
   *    Symbol,Name,LastSale,MarketCap,ADR TSO,IPOYear,Sector,Industry,Summary Quote
   *    string,string,float(or n/a),float,string(n/a),int(or n/a),string(or n/a),string(or n/a),string(url)
   */
  public function stocks_api_request_exchange_summaries()
  {
    $exchangeSummaries = [];
    foreach ($this->stockAPISettings->get('exchanges.enabled') as $exchange) {
      $fetchUrl = static::EXCHANGE_SUMMARY_CSV_REQUEST_PREFIX . $exchange . static::EXCHANGE_SUMMARY_CSV_REQUEST_SUFFIX;
      try {
        $exchangeSummaries[$exchange] = (string) $this->httpClient
          ->get($fetchUrl, [
            'headers' => [
              'Accept' => 'text/csv'
            ],
            'verify' => false,
          ])
          ->getBody();
      } catch (RequestException $exception) {
        watchdog_exception('request', $exception);
      }
    }
    return $exchangeSummaries;
  }

  /**
   * Parse exchange summary CSV into map.
   *
   * @param array $exchangeSummaries
   *   Map of exchange contents, in the following format:
   *      ['NYSE'] => '"Symbol","Name","LastSale","MarketCap","ADR TSO","IPOyear","Sector","Industry","Summary Quote",
   *                   "YI","111, Inc.","9.35","67086250","7175000","2018","Health Care","Medical/Nursing Services","https://www.nasdaq.com/symbol/yi",'
   *
   * @return array
   *    Map of exchange results, where each stock is parsed into a map in the following format:
   *      [string] => ['Symbol' => <string>,
   *                   'Name' => <string>,
   *                   'LastSale' => <float or string>,
   *                   'MarketCap' => <float>,
   *                   'Sector' => <string>,
   *                   'Industry' => <string>]
   *
   * Example return map:
   *      ['NYSE'] => ['Symbol' => 'AAPL',
   *                   'Name' => 'Apple Inc.',
   *                   'LastSale' => 225.17,
   *                   'MarketCap' => 1050283393483,
   *                   'Sector' => 'Technology',
   *                   'Industry' => 'Mobile Technology']
   */
  public function stocks_api_build_stock_map_from_exchange_summaries($exchangeSummaries){
    $stockMap = [];

    foreach ($exchangeSummaries as $exchange => $exchangeSummary) {
      $stockMap[$exchange] = str_getcsv($exchangeSummary, PHP_EOL);
      $stockMap[$exchange] = array_map('str_getcsv', $stockMap[$exchange]);
      $header = array_shift($stockMap[$exchange]); # remove column header
      array_walk($stockMap[$exchange], function(&$a) use ($header) {
        $a = array_combine($header, $a);
      });
    }
    return $stockMap;
  }
}

