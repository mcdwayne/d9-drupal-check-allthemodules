<?php

/**
 * @file
 * Contains quail reporter class objects for the quail api.
 */

/**
 * An array reporter that generates a nested array of tests and report objects.
 *
 * The quail library will look for a function called "report$name_of_reporter".
 * Therefore, 'quail_api_reporter' must be used as the name of this reporter.
 * This file must also be included before calling quail(), otherwise this
 * reporter will not be found by the quail library.
 */
class reportQuail_api extends quailReporter {
  protected $quail_api_report = array();


  /**
   * Compatibility function provided for the quail library to call.
   * This is the same as calling generate_report(), followed by get_report().
   *
   * @return array
   *   An array containing the results of all tests performed.
   */
  public function getReport() {
    $this->generate_report();

    return $this->quail_api_report;
  }

  /**
   * Generates a static list of possible accessibility problems detected.
   */
  public function generate_report() {
    // reset the report variables on generation
    $this->quail_api_report = array('report' => array(), 'total' => 0);

    if (!is_object($this->guideline)) {
      return;
    }

    $results = $this->guideline->getReport();

    if (!is_array($results)) return;

    foreach ($results as $quail_test_name => $test) {
      $this->get_test_results($quail_test_name, $test);
    }
  }

  /**
   * Processes the results of a given test.
   *
   * @param $quail_test_name
   *   The severity of the test whose results are to be processed.
   * @param $test
   *   An array containing the unprocessed test results associated with the given $quail_test_name
   */
  protected function get_test_results($quail_test_name, $test) {
    $severity = $this->guideline->getSeverity($quail_test_name);

    if (!isset($this->quail_api_report['total'])){
      $this->quail_api_report['total'] = 0;
    }

    if (!isset($this->quail_api_report['report'][$severity])) {
      $this->quail_api_report['report'][$severity] = array('total' => 0);
    }

    if (!isset($this->quail_api_report['report'][$severity][$quail_test_name])) {
      $this->quail_api_report['report'][$severity][$quail_test_name] = array('total' => 0);
      $this->quail_api_report['report'][$severity][$quail_test_name]['title'] = $this->translation[$quail_test_name];
      $this->quail_api_report['report'][$severity][$quail_test_name]['body'] = $this->guideline->getTranslation($quail_test_name);
    }

    if (!property_exists($this, 'quail_api_total_problems')) {
      $this->quail_api_total_problems = 0;
    }

    if (is_array($test)) {
      foreach ($test as $problem_id => $problem) {
        if (is_object($problem)) {
          $this->quail_api_report['total']++;
          $this->quail_api_report['report'][$severity]['total']++;
          $this->quail_api_report['report'][$severity][$quail_test_name]['total']++;
          $this->get_test_results_array($severity, $quail_test_name, $problem_id, $problem);
          $this->quail_api_total_problems++;
        }
      }

      if ($this->quail_api_report['report'][$severity][$quail_test_name]['total'] == 0) {
        unset($this->quail_api_report['report'][$severity][$quail_test_name]);
      }
    }
  }

  /**
   * Processes the results of a single test problem.
   *
   * @param $quail_test_name
   *   The severity of the test whose results are to be processed.
   * @param $test
   *   An array containing the unprocessed test results associated with the given $quail_test_name.
   * @param $problem_id
   *   The id/name of a given problem as returned by Quail.
   * @param $problem
   *   An object containing the problem as returned by Quail.
   */
  protected function get_test_results_array($severity, $quail_test_name, $problem_id, $problem) {
    $this->quail_api_report['report'][$severity][$quail_test_name]['problems'][$problem_id]['element'] = htmlentities($problem->getHtml());
    $this->quail_api_report['report'][$severity][$quail_test_name]['problems'][$problem_id]['line'] = $problem->getLine();

    if ($problem->message) {
      $this->quail_api_report['report'][$severity][$quail_test_name]['problems']['message'] = $problem->message;
    }
  }
}
