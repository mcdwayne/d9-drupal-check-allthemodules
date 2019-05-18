<?php

namespace Drupal\demo\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Default controller for the demo module.
 */
class DefaultController extends ControllerBase
{

  /**
   * funtion to autocomplete the demo dump and save it to the dumppath declared in settings.php
   */
  public function demo_autocomplete($string = '')
  {
    $matches = [];
    if ($string && $fileconfig = demo_get_fileconfig()) {
      $string = preg_quote($string);
      $files = file_scan_directory($fileconfig['dumppath'], '/' . $string . '.*\.info$/');
      foreach ($files as $file) {
        $matches[$file->name] = check_plain($file->name);
      }
    }
    return new JsonResponse($matches, 200);

  }

  /**
   * Funtion to download the dump file.
   */
  public function demo_download($filename, $type)
  {
    $fileconfig = demo_get_fileconfig($filename);
    if (!isset($fileconfig[$type . 'file']) || !file_exists($fileconfig[$type . 'file'])) {
      return MENU_NOT_FOUND;
    }
    // Force the client to re-download and trigger a file save download.
    $headers = [
      'Cache-Control: private',
      'Content-Type: application/octet-stream',
      'Content-Length: ' . filesize($fileconfig[$type . 'file']),
      'Content-Disposition: attachment, filename=' . $fileconfig[$type],
    ];
    return new BinaryFileResponse($fileconfig[$type . 'file'], 200, $headers);
  }
}
