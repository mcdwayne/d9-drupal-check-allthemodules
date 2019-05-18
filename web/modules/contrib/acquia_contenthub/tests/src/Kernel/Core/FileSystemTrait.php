<?php

namespace Drupal\Tests\acquia_contenthub\Kernel\Core;

/**
 * Trait FileSystemTrait.
 *
 * @group acquia_contenthub
 *
 * @package Drupal\Tests\acquia_contenthub\Kernel\Core
 */
trait FileSystemTrait {

  /**
   * File system component.
   *
   * @var \Drupal\Core\File\FileSystem
   */
  protected $fileSystem;

  /**
   * Adjust file system for test.
   */
  protected function fileSystemSetUp() {
    $this->installEntitySchema('file');
    $this->installSchema('file', ['file_usage']);

    $this->fileSystem = \Drupal::service('file_system');
    $this->fileSystem->mkdir('public://2018-12');
  }

}
