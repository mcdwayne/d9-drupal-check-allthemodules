<?php

namespace Drupal\stocks_api\stocks;


/**
 * Provides an interface for defining API Interfaces to individual stock info.
 *
 * @ingroup stocks_api
 */
interface StockAPIInterface {

  /**
   * Requests the stock quote by HTTP request.
   *
   * @param string $tickerSymbol
   *    Ticker symbol of stock to get data for
   *
   * @return array
   *   Map of stock quote contents
   */
  public function stocks_api_request_stock_quote($tickerSymbol);

}
