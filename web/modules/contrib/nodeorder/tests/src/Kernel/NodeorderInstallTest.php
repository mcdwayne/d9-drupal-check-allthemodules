<?php

namespace Drupal\Tests\nodeorder\Kernel;

/**
 * Tests module installation.
 *
 * @group nodeorder
 */
class NodeorderInstallTest extends NodeorderInstallTestBase {

  /**
   * Tests module installation.
   */
  public function testInstall() {
    $column_exists = $this->database->schema()->fieldExists('taxonomy_index', 'weight');
    $this->assertTrue($column_exists);

    $index_exists = $this->database->schema()->indexExists('taxonomy_index', 'weight');
    $this->assertTrue($index_exists);
  }

}
