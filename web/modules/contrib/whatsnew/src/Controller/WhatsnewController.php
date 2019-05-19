<?php

namespace Drupal\whatsnew\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class WhatsnewController.
 *
 * @package Drupal\Whatsnew\Controller
 */
class WhatsnewController extends ControllerBase {

  /**
   * Return the JSON formatted report.
   */
  public function report() {

    $key = \Drupal::config('whatsnew.settings')
      ->get('key');

    // Security check.
    if (isset($_GET['key']) && ($_GET['key'] == $key)) {
      $report = $this->generateReport();
      return new JsonResponse($report, 200, ['Content-Type' => 'application/json']);
    }
    else {
      // Throw a generic 404 error if the key is incorrect.
      throw new NotFoundHttpException();
    }

  }

  /**
   * Generate the report data.
   *
   * @return array
   *   Current system version details
   */
  protected function generateReport() {
    $report = [];
    $modules = system_get_info('module');
    foreach ($modules as $module) {
      $report[] = [
        'project' => isset($module['project']) ? $module['project'] : '',
        'name' => isset($module['name']) ? $module['name'] : '',
        'package' => isset($module['package']) ? $module['package'] : '',
        'version' => isset($module['version']) ? $module['version'] : '',
        'core' => isset($module['core']) ? $module['core'] : '',
      ];
    }

    return $report;
  }

}
