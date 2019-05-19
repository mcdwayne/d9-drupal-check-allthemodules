<?php

namespace Drupal\simple_a_b_reports_google\Controller;

use Drupal\simple_a_b_reports_google\SimpleABReportsGoogle;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Simple A/B Google Analytics reporting controller.
 */
class SimpleABReportsGoogleController extends ControllerBase {

  /**
   * Creates a json response returning any reports.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Returns json response of any report data.
   */
  public function getReports() {
    $output = [];

    // Load up the reports.
    $reports = SimpleABReportsGoogle::getReport();
    // Add to an array.
    $output['reports'] = $reports;

    // Clear out all the old reports.
    SimpleABReportsGoogle::removeAllReports();

    // Return the json response.
    return new JsonResponse($output);
  }

}
