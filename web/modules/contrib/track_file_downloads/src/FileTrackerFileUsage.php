<?php

namespace Drupal\track_file_downloads;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\file\FileInterface;
use Drupal\file\FileUsage\DatabaseFileUsageBackend;

/**
 * Decorate file.usage so usage isn't tracked for file_tracker entities.
 */
class FileTrackerFileUsage extends DatabaseFileUsageBackend {

  /**
   * The parent service we are decorating.
   *
   * @var \Drupal\file\FileUsage\DatabaseFileUsageBackend
   */
  protected $parentService;

  /**
   * {@inheritdoc}
   */
  public function __construct(DatabaseFileUsageBackend $parent_service, Connection $connection, $table = 'file_usage', ConfigFactoryInterface $config_factory = NULL) {
    parent::__construct($connection, $table, $config_factory);
    $this->parentService = $parent_service;
  }

  /**
   * {@inheritdoc}
   */
  public function add(FileInterface $file, $module, $type, $id, $count = 1) {
    // Don't track usage for file_tracker entities.
    if ($type !== 'file_tracker') {
      $this->parentService->add($file, $module, $type, $id, $count);
    }
  }

}
