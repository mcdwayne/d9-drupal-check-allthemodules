<?php

namespace Drupal\xhprof\XHProfLib\Report;

use Drupal\xhprof\XHProfLib\Parser\Parser;

/**
 * Class Report
 */
class Report implements ReportInterface {

  /**
   * @var array
   */
  private $data;

  /**
   * @var \Drupal\xhprof\XHProfLib\Parser\Parser
   */
  private $parser;

  /**
   * @param \Drupal\xhprof\XHProfLib\Parser\Parser $parser
   */
  public function __construct(Parser $parser) {
    $this->parser = $parser;
    $this->data = $parser->parse();
  }

  /**
   * {@inheritdoc}
   */
  public function getSymbols($length = 100) {
    if ($length != -1) {
      $data = array_slice($this->data, 0, $length);
    }
    else {
      $data = $this->data;
    }

    $totals = $this->getTotals();
    $symbols = array();
    foreach ($data as $key => $value) {
      $symbol = array();
      $symbol[] = $key;

      $symbol[] = $this->getValue($value['ct'], 'ct');
      $symbol[] = $this->getPercentValue($value['ct'], 'ct', $totals['ct']);

      foreach ($this->getMetrics() as $metric) {
        $symbol[] = $this->getValue($value[$metric], $metric);
        $symbol[] = $this->getPercentValue($value[$metric], $metric, $totals[$metric]);

        $symbol[] = $this->getValue($value['excl_' . $metric], 'excl_' . $metric);
        $symbol[] = $this->getPercentValue($value['excl_' . $metric], 'excl_' . $metric, $totals[$metric]);
      }

      $symbols[] = $symbol;
    }

    return $symbols;
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $summary = array();
    $totals = $this->getTotals();
    foreach ($this->getMetrics() as $metric) {
      $summary[$metric] = $this->getValue($totals[$metric], $metric);
    }

    if ($this->getDisplayCalls()) {
      $summary['ct'] = $this->getValue($totals['ct'], 'ct');
    }

    return $summary;
  }

  /**
   * @return mixed
   */
  public function getTotals() {
    return $this->parser->getTotals();
  }

  /**
   * @return mixed
   */
  public function getPossibleMetrics() {
    return $this->parser->getPossibleMetrics();
  }

  /**
   * @return mixed
   */
  public function getMetrics() {
    return $this->parser->getMetrics();
  }

  /**
   * @return mixed
   */
  public function getDisplayCalls() {
    return $this->parser->getDisplayCalls();
  }

  /**
   * @param $value
   * @param $metric
   *
   * @return mixed
   */
  private function getValue($value, $metric) {
    $format_cbk = ReportConstants::getFormatCbk();
    return call_user_func($format_cbk[$metric], $value);
  }

  /**
   * @param $value
   * @param $metric
   * @param $totals
   *
   * @return mixed|string
   */
  private function getPercentValue($value, $metric, $totals) {
    if ($totals == 0) {
      $pct = "N/A%";
    }
    else {
      $format_cbk = ReportConstants::getFormatCbk();
      $pct = call_user_func($format_cbk[$metric . '_perc'], ($value / abs($totals)));
    }

    return $pct;
  }
}
