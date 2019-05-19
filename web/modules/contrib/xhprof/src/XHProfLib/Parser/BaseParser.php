<?php

namespace Drupal\xhprof\XHProfLib\Parser;

use Drupal\xhprof\XHProfLib\Report\ReportConstants;
use Drupal\xhprof\XHProfLib\Run;
use Drupal\xhprof\XHProfLib\Symbol\Symbol;

/**
 * Class BaseReport
 */
abstract class BaseParser implements ParserInterface {

  protected $stats;
  protected $pc_stats;
  protected $metrics;
  protected $diff_mode;
  protected $sort_col;
  protected $display_calls;
  protected $sort;
  protected $symbol;

  private $overall_totals = array(
    "ct" => 0,
    "wt" => 0,
    "ut" => 0,
    "st" => 0,
    "cpu" => 0,
    "mu" => 0,
    "pmu" => 0,
    "samples" => 0
  );

  /**
   * @var \Drupal\xhprof\XHProfLib\Symbol\Symbol
   */
  protected $mainSymbol;

  /**
   * @var \Drupal\xhprof\XHProfLib\Run
   */
  protected $run;

  /**
   * @param $run
   * @param $sort
   * @param $symbol
   */
  public function __construct(Run $run, $sort, $symbol) {
    $this->sort = $sort;
    $this->run = $run;
    $this->symbol = $symbol;
    $this->diff_mode = FALSE;
    $this->mainSymbol = $run->getMainSymbol();

    $this->initMetrics($run->getSymbols(), NULL, $sort);
  }

  /**
   * @param $symbols
   *   All profiled symbols.
   * @param $symbol
   *   Set this to show the parent-child report.
   * @param $sort
   *   Metric used to sort.
   */
  protected function initMetrics($symbols, $symbol, $sort) {
    if (!empty($sort)) {
      if (array_key_exists($sort, ReportConstants::getSortableColumns())) {
        $this->sort_col = $sort;
      }
      else {
        print("Invalid Sort Key $sort specified in URL");
      }
    }

    // For C++ profiler runs, walltime attribute isn't present.
    // In that case, use "samples" as the default sort column.
    $wt = $this->mainSymbol->getWt();
    if (!isset($wt)) {

      if ($this->sort_col == "wt") {
        $this->sort_col = "samples";
      }

      // C++ profiler data doesn't have call counts.
      // ideally we should check to see if "ct" metric
      // is present for "main()". But currently "ct"
      // metric is artificially set to 1. So, relying
      // on absence of "wt" metric instead.
      $this->display_calls = FALSE;
    }
    else {
      $this->display_calls = TRUE;
    }

    // parent/child report doesn't support exclusive times yet.
    // So, change sort hyperlinks to closest fit.
    if (!empty($symbol)) {
      $this->sort_col = str_replace("excl_", "", $this->sort_col);
    }

    if ($this->display_calls) {
      $this->stats = array("fn", "ct", "Calls%");
    }
    else {
      $this->stats = array("fn");
    }

    $this->pc_stats = $this->stats;

    $possible_metrics = $this->getPossibleMetrics($symbols);
    foreach ($possible_metrics as $metric => $desc) {
      $mainMetric = $this->mainSymbol->getMetric($metric);
      if (isset($mainMetric)) {
        $metrics[] = $metric;
        // flat (top-level reports): we can compute
        // exclusive metrics reports as well.
        $this->stats[] = $metric;
        $this->stats[] = "I" . $desc[0] . "%";
        $this->stats[] = "excl_" . $metric;
        $this->stats[] = "E" . $desc[0] . "%";

        // parent/child report for a function: we can
        // only breakdown inclusive times correctly.
        $this->pc_stats[] = $metric;
        $this->pc_stats[] = "I" . $desc[0] . "%";
      }
    }
  }

  /**
   * @return array
   */
  public function getPossibleMetrics() {
    return array(
      "wt" => array("Wall", "microsecs", "walltime"),
      "ut" => array("User", "microsecs", "user cpu time"),
      "st" => array("Sys", "microsecs", "system cpu time"),
      "cpu" => array("Cpu", "microsecs", "cpu time"),
      "mu" => array("MUse", "bytes", "memory usage"),
      "pmu" => array("PMUse", "bytes", "peak memory usage"),
      "samples" => array("Samples", "samples", "cpu time")
    );
  }

  /**
   * @return array
   */
  public function getMetrics() {
    // get list of valid metrics
    $possible_metrics = $this->getPossibleMetrics();

    // return those that are present in the raw data.
    // We'll just look at the root of the subtree for this.
    $metrics = array();
    foreach ($possible_metrics as $metric => $desc) {
      $mainMetric = $this->mainSymbol->getMetric($metric);
      if (isset($mainMetric)) {
        $metrics[] = $metric;
      }
    }

    return $metrics;
  }

  /**
   * @param Symbol[] $symbols
   *
   * @return array
   */
  protected function computeFlatInfo($symbols) {
    $metrics = $this->getMetrics();

    // Compute inclusive times for each function.
    $symbol_tab = $this->computeInclusiveTimes($symbols);

    // Total metric value is the metric value for "main()".
    foreach ($metrics as $metric) {
      $this->overall_totals[$metric] = $this->mainSymbol->getMetric($metric);
    }

    // Initialize exclusive (self) metric value to inclusive metric value to start with.
    // In the same pass, also add up the total number of function calls.
    foreach ($symbol_tab as $symbol => $info) {
      foreach ($metrics as $metric) {
        $symbol_tab[$symbol]["excl_" . $metric] = $symbol_tab[$symbol][$metric];
      }
      // Keep track of total number of calls.
      $this->overall_totals["ct"] += $info["ct"];
    }

    // Adjust exclusive times by deducting inclusive time of children.
    foreach ($symbols as $symbol) {
      $parent = $symbol->getParent();

      if ($parent) {
        foreach ($metrics as $metric) {
          // make sure the parent exists hasn't been pruned.
          if (isset($symbol_tab[$parent])) {
            $symbol_tab[$parent]["excl_" . $metric] -= $symbol->getMetric($metric);
          }
        }
      }
    }

    return $symbol_tab;
  }

  /**
   * @param Symbol[] $symbols
   *
   * @return array
   */
  protected function computeInclusiveTimes($symbols) {
    $metrics = $this->getMetrics();

    $symbol_tab = array();

    /*
     * First compute inclusive time for each function and total
     * call count for each function across all parents the
     * function is called from.
     */
    foreach ($symbols as $symbol) {
      $child = $symbol->getChild();

      if (!isset($symbol_tab[$child])) {
        $symbol_tab[$child] = array("ct" => $symbol->getCt());
        foreach ($metrics as $metric) {
          $symbol_tab[$child][$metric] = $symbol->getMetric($metric);
        }
      }
      else {
        // increment call count for this child
        $symbol_tab[$child]["ct"] += $symbol->getCt();

        // update inclusive times/metric for this child
        foreach ($metrics as $metric) {
          $symbol_tab[$child][$metric] += $symbol->getMetric($metric);
        }
      }
    }

    return $symbol_tab;
  }

  /**
   * @param Symbol[] $symbols
   * @param $functions_to_keep
   *
   * @return array
   */
  function trimRun($symbols, $functions_to_keep) {

    // convert list of functions to a hash with function as the key
    $function_map = array_fill_keys($functions_to_keep, 1);

    // always keep main() as well so that overall totals can still
    // be computed if need be.
    $function_map['main()'] = 1;

    $new_symbols = array();
    foreach ($symbols as $symbol) {
      $parent = $symbol->getParent();
      $child = $symbol->getChild();

      if (isset($function_map[$parent]) || isset($function_map[$child])) {
        $new_symbols["{$parent}==>$child"] = $symbol;
      }
    }

    return $new_symbols;
  }

  /**
   * @param $arr
   * @param $k
   * @param $v
   *
   * @return mixed
   */
  function arraySet($arr, $k, $v) {
    $arr[$k] = $v;
    return $arr;
  }

  /**
   * @param $arr
   * @param $k
   *
   * @return mixed
   */
  function arrayUnset($arr, $k) {
    unset($arr[$k]);
    return $arr;
  }

  /**
   * @return mixed
   */
  public function getTotals() {
    return $this->overall_totals;
  }

  /**
   * @return mixed
   */
  public function getDisplayCalls() {
    return $this->display_calls;
  }

}
