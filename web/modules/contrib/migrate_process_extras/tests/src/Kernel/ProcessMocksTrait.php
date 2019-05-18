<?php

namespace Drupal\Tests\migrate_process_extras\Kernel;

use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\Plugin\Migration;
use Drupal\migrate\Row;

/**
 * Helper functions for testing process plugins.
 */
trait ProcessMocksTrait {

  /**
   * The migrate row.
   *
   * @var \Drupal\migrate\Row
   */
  protected $row;

  /**
   * The migrate executable.
   *
   * @var \Drupal\migrate\MigrateExecutable
   */
  protected $migrateExecutable;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->row = $this->getMockBuilder(Row::class)
      ->disableOriginalConstructor()
      ->getMock();
    $this->migrateExecutable = $this->getMockBuilder(MigrateExecutable::class)
      ->disableOriginalConstructor()
      ->getMock();
  }

}
