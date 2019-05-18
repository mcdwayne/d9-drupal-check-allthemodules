<?php

/**
 * @file
 * Contains \Drupal\config_single_export\Controller\ConfigSingleExportController.
 */

namespace Drupal\config_single_export\Controller;

use Symfony\Component\HttpFoundation\Request;
use Drupal\config\Controller\ConfigController;

/**
 * Returns responses for config module routes.
 */
class ConfigSingleExportController extends ConfigController {

  /**
   * Downloads a tarball of the site configuration.
   */
  public function downloadSingleExport($filename) {
    if (!empty($filename)) {
      $request = new Request(array('file' => $filename));
      $result = $this->fileDownloadController->download($request, 'temporary');

      return $result;
    }
  }

}
