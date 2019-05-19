<?php

namespace Drupal\stocks_api\exchanges;


/**
 * Provides an interface for defining API Interfaces to stock exchange info.
 *
 * @ingroup stocks_api
 */
interface ExchangeAPIInterface {

  /**
   * Requests summaries of the enabled exchanges.
   *
   * @return array
   *   Map of exchange contents
   */
  public function stocks_api_request_exchange_summaries();

  /**
   * Parse exchange summary CSV into map.
   *
   * @param array $exchangeSummaries
   *   Map of exchange contents, in the following format:
   *      ['NYSE'] => '"Symbol","Name","LastSale","MarketCap","ADR TSO","IPOyear","Sector","Industry","Summary Quote",
   *                   "YI","111, Inc.","9.35","67086250","7175000","2018","Health Care","Medical/Nursing Services","https://www.nasdaq.com/symbol/yi",'
   *
   * @return array
   *    Map of exchange results, where each stock is parsed into a map
   */
  public function stocks_api_build_stock_map_from_exchange_summaries($exchangeSummaries);

}
