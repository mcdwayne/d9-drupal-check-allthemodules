<?php

namespace Drupal\matomo_reporting_api;

/**
 * Interface for factory classes that return Matomo query objects.
 */
interface MatomoQueryFactoryInterface {

  /**
   * Returns a Matomo query object for the given method.
   *
   * @param string $method
   *   The name of the method for which to return a query object, in the format
   *   'ModuleName.methodName'.
   *
   * @return \Matomo\ReportingApi\QueryInterface
   *   The Matomo reporting API query object.
   *
   * @see https://developer.matomo.org/api-reference/reporting-api#api-method-list
   */
  public function getQuery($method);

  /**
   * Returns the query factory from the library.
   *
   * @return \Matomo\ReportingApi\QueryFactoryInterface
   *   The query factory.
   */
  public function getQueryFactory();

}
