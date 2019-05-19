<?php

namespace Drupal\xhprof\XHProfLib\Report;

/**
 * Interface ReportInterface
 */
interface ReportInterface {

  /**
   * @param $length
   *
   * @return mixed
   */
  public function getSymbols($length = 100);

  /**
   * @return mixed
   */
  public function getSummary();

  /**
   * @return mixed
   */
  public function getTotals();

  /**
   * @return mixed
   */
  public function getPossibleMetrics();

  /**
   * @return mixed
   */
  public function getMetrics();

  /**
   * @return mixed
   */
  public function getDisplayCalls();

}
