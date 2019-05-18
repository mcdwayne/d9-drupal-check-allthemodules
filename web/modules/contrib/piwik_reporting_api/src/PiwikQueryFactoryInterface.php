<?php

namespace Drupal\piwik_reporting_api;

/**
 * Interface for factory classes that return Piwik query objects.
 */
interface PiwikQueryFactoryInterface {

  /**
   * Returns a Piwik query object for the given method.
   *
   * @param string $method
   *   The name of the method for which to return a query object, in the format
   *   'ModuleName.methodName'.
   *
   * @return \Piwik\ReportingApi\QueryInterface
   *   The Piwik reporting API query object.
   *
   * @see https://developer.piwik.org/api-reference/reporting-api#api-method-list
   */
  public function getQuery($method);

  /**
   * Returns the query factory from the library.
   *
   * @return \Piwik\ReportingApi\QueryFactoryInterface
   *   The query factory.
   */
  public function getQueryFactory();

}
