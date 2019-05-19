<?php

namespace Drupal\xhprof\XHProfLib\Report;

use Drupal\xhprof\XHProfLib\Parser\DiffParser;
use Drupal\xhprof\XHProfLib\Parser\Parser;
use Drupal\xhprof\XHProfLib\Run;

/**
 * Class ReportEngine
 */
class ReportEngine {

  /**
   * @param $url_params
   * @param $source
   * @param Run $run
   * @param $wts
   * @param $symbol
   * @param $sort
   * @param Run $run1
   * @param Run $run2
   *
   * @return ReportInterface
   */
  public function getReport($url_params, $source, Run $run, $wts, $symbol, $sort = 'wt', Run $run1 = NULL, Run $run2 = NULL) {
    $report = NULL;

    // specific run to display
    if ($run) {
      $parser = new Parser($run, $sort, $symbol);
      $report = new Report($parser);
    }
    // diff report for two runs
    else {
      if ($run1 && $run2) {
        $report = new DiffParser($url_params, $run1->getSymbols(), '', $run2->getSymbols(), '', $symbol, $sort, $run1, $run2);
        $report = new DiffReport($report);
      }
    }

    return $report;
  }
}
