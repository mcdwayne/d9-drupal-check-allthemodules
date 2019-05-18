<?php

namespace Drupal\groundhog_day;

use Drupal\file\Entity\File;
use Drush\Log\LogLevel;

class GroundhogDayExecutor {

  protected $path = '../groundhog_day';
  protected $structureTables = [];

  public function __construct(array $parameters) {
    $this->path = $parameters['path'];
    $this->structureTables = $parameters['structure_tables'];
  }

  public function update() {
    $basePath = DRUPAL_ROOT . '/' . $this->path;
    // Store a database snapshot.
    if (file_exists($basePath)) {
      file_unmanaged_delete_recursive($basePath);
    }
    $sqlFile = $basePath . '/snapshot.sql';
    if (!is_dir(dirname($sqlFile))) {
      mkdir(dirname($sqlFile), 0777, TRUE);
    }

    if (file_exists($sqlFile)) {
      unlink($sqlFile);
    }
    drush_set_option('structure-tables-list', implode(',', $this->structureTables));
    drush_sql_get_class()->dump($sqlFile);
    drush_log("Stored snapshot to $sqlFile.", LogLevel::OK);

    $filesBasePath = $file = $basePath . '/files';
    foreach (File::loadMultiple() as $file) {
      $uri = $file->uri->value;
      $scheme = parse_url($uri, PHP_URL_SCHEME);
      $path = parse_url($uri, PHP_URL_HOST);
      $fileName = parse_url($uri, PHP_URL_PATH);

      if (!in_array($scheme, ['private', 'public'])) {
        continue;
      }

      $filePath = implode('/', [$filesBasePath, $scheme, $path]);

      if (!is_dir(dirname($filePath))) {
        mkdir($filePath, 0777, TRUE);
      }
      file_unmanaged_copy($file->getFileUri(), $filePath . '/' . $fileName, FILE_EXISTS_REPLACE);
    }
  }

  public function reset() {
    $basePath = DRUPAL_ROOT . '/' . $this->path;
    $sqlFile = $basePath . '/snapshot.sql';
    if (!file_exists($sqlFile)) {
      drush_log("File $sqlFile does not exists. Unable to restore snapshot.", LogLevel::ERROR);
    }

    $sql = drush_sql_get_class();
    $tables = $sql->listTables();
    $sql->drop($tables);
    $sql->query(NULL, $sqlFile);
    drush_print(implode("\n", drush_shell_exec_output()));
    drush_log("Restored snapshot from $sqlFile.", LogLevel::ERROR);

    foreach (['private', 'public'] as $scheme) {
      if($path = \Drupal::service('file_system')->realpath($scheme . '://')) {
        file_prepare_directory($path);
        $schemeDir = $basePath . '/files/' . $scheme;
        foreach (file_scan_directory($path, '/.*/') as $file => $info) {
          file_unmanaged_delete($file);
        }
        foreach (file_scan_directory($schemeDir, '/.*/') as $file => $info) {
          $destination = $scheme . '://' . substr($file, strlen($schemeDir) + 1);
          file_prepare_directory(dirname($destination), FILE_CREATE_DIRECTORY);
          file_unmanaged_copy($file, $destination);
        }
      }
    }

  }

}
