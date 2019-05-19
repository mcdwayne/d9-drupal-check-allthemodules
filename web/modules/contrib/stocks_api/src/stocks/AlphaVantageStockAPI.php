<?php

namespace Drupal\stocks_api\stocks;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Site\Settings;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

/**
 * API to retrieve stock quote data.
 *
 * @ingroup stocks_api
 */
class AlphaVantageStockAPI implements StockAPIInterface {

  const STOCK_QUOTE_REQUEST_PREFIX = 'https://www.alphavantage.co/query?function=';
  const GLOBAL_QUOTE_FUNCTION = 'GLOBAL_QUOTE';
  const BATCH_QUOTE_FUNCTION = 'BATCH_STOCK_QUOTES';
  const STOCK_SYMBOL_REQUEST_PREFIX = '&symbol=';
  const STOCK_SYMBOLS_REQUEST_PREFIX = '&symbols=';
  const API_KEY_PREFIX = '&apikey=';
  const DATA_REQUEST_TYPE_PREFIX = '&datatype=';


  /**
   * The Alpha Vantage API Key.
   *
   * @var string
   */
  protected $apiKey;

  /**
   * The HTTP client to fetch the feed data with.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * stocks_api settings
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $stockAPISettings;

  /**
   * Constructs a AlphaVantageStockAPI.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   A Guzzle client object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ClientInterface $http_client) {
    $this->stockAPISettings = $config_factory->get('stocks_api.settings');
    $this->httpClient = $http_client;
    $this->apiKey = Settings::get('alpha_vantage_api_key', '');
  }

  /**
   * @param string $tickerSymbol
   *    Ticker symbol of stock to get data for
   *
   * @return array
   *    Stock quote contents converted to map format.
   *
   * Example return map:
   *      ['01. symbol'] => "AAPL" (string),
   *      ['02. open'] => "110.1000" (float as string),
   *      ['03. high'] => "110.5300" (float as string),
   *      ['04. low'] => "107.8300" (float as string),
   *      ['05. price'] => "108.5000" (float as string),
   *      ['06. volume'] => "27252591" (int as string),
   *      ['07. latest trading day'] => "2018-10-18" (date string),
   *      ['08. previous close'] => "110.7100" (float as string),
   *      ['09. change'] => "-2.2100" (float as string),
   *      ['10. change percent'] => "-1.9962%" (float as string)
   */
  public function stocks_api_request_stock_quote($tickerSymbol) {
    $stockQuoteJSON = "";

    $fetchUrl = static::STOCK_QUOTE_REQUEST_PREFIX . static::GLOBAL_QUOTE_FUNCTION .
      static::DATA_REQUEST_TYPE_PREFIX . "JSON" .
      static::STOCK_SYMBOL_REQUEST_PREFIX . $tickerSymbol .
      static::API_KEY_PREFIX . $this->apiKey;
    
    try {
      $stockQuoteJSON = (string) $this->httpClient
        ->get($fetchUrl, [
          'headers' => [
            'Accept' => 'application/json'
          ],
          'verify' => false,
        ])
        ->getBody();
    } catch (RequestException $exception) {
      watchdog_exception('request', $exception);
    }
    return Json::decode($stockQuoteJSON)["Global Quote"];
  }

  /**
   * Batch requests stock quotes by HTTP request.
   *
   * @param array $tickerSymbols
   *    Ticker symbols (as strings) of stocks to request data for
   *
   * @return array
   *    Map of stock quotes
   *
   * Example return map:
   *      [0] => Array
   *        (
   *        [1. symbol] => "AAPL" (string),
   *        [2. price] => "108.5000" (float as string),
   *        [3. volume] => "27252591" (int as string),
   *        [4. timestamp] => "2018-12-04 17:00:00" (string),
   *        )
   *      [1] => Array ...
   */
  public function stocks_api_batch_request_stock_quotes($tickerSymbols) {
    $stockQuoteJSON = "";
    $stockList = implode(",", $tickerSymbols);
    $fetchUrl = static::STOCK_QUOTE_REQUEST_PREFIX . static::BATCH_QUOTE_FUNCTION .
      static::DATA_REQUEST_TYPE_PREFIX . "JSON" .
      static::STOCK_SYMBOLS_REQUEST_PREFIX . $stockList .
      static::API_KEY_PREFIX . $this->apiKey;

    try {
      $stockQuoteJSON = (string) $this->httpClient
        ->get($fetchUrl, [
          'headers' => [
            'Accept' => 'application/json'
          ],
          'verify' => false,
        ])
        ->getBody();
    } catch (RequestException $exception) {
      watchdog_exception('request', $exception);
    }

    return Json::decode($stockQuoteJSON)["Stock Quotes"];
  }
}
