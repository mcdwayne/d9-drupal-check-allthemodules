<?php

namespace Drupal\Tests\forena\Unit\Mock;

use Drupal\forena\AppService;
use Drupal\forena\File\ReportFileSystem;

class TestingReportFileSystem extends ReportFileSystem{

  // Replacement Constrcutor
  public function __construct() {
    $app = AppService::instance();
    $this->dir = $app->reportDirectory;
    $this->includes = $app->reportIncludes;
  }

  public function getDirectoryState() {
    return [];
  }

  public function setDirectoryState() {
  }

  public function save($filename, $data) {
  }

  public function delete($filename) {

  }

  public function localeEnabled() {
    return FALSE;
  }

}