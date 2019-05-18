<?php

/**
 * @file
 * Contains \Drupal\config_db\Controller\ConfigExportController.
 */

namespace Drupal\config_db\Controller;

use Drupal\config\Controller\ConfigController;
use Drupal\Component\Archiver\ArchiveTar;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\system\FileDownloadController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Overrides config export controller.
 */
class ConfigExportController extends ConfigController {

  /**
   * Downloads a tarball of the site configuration.
   */
  public function downloadExport() {
    $archiver = new ArchiveTar(file_directory_temp() . '/config.tar.gz', 'gz');
    $tmp_dir = file_directory_temp() . '/config_export_' . time();
    drupal_mkdir($tmp_dir);
    $file_storage = new FileStorage($tmp_dir);
    $config_files = array();
    foreach (\Drupal::service('config.storage')->listAll() as $config_name) {
      $config_files[] = $tmp_dir . '/' . $config_name . '.yml';
      $file_storage->write($config_name, \Drupal::config($config_name)->get());
    }
    $archiver->createModify($config_files, '', $tmp_dir);

    $request = new Request(array('file' => 'config.tar.gz'));
    return $this->fileDownloadController->download($request, 'temporary');
  }
}

