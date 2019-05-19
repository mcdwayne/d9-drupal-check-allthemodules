<?php

namespace Drupal\Tests\nodeorder\Kernel;

/**
 * Tests the uninstallation of module.
 *
 * @group nodeorder
 */
class NodeorderUninstallTest extends NodeorderInstallTestBase {

  /**
   * Tests module uninstallation.
   */
  public function testUninstall() {
    $schema = $this->database->schema();

    // Need to ensure that filed and index exists before uninstallation.
    // Otherwise test will be successfully passed every time.
    // @see: \Drupal\Tests\nodeorder\Kernel\NodeorderInstallTest::testInstall().
    $this->assertTrue($schema->fieldExists('taxonomy_index', 'weight'));
    $this->assertTrue($schema->indexExists('taxonomy_index', 'weight'));

    $this->moduleInstaller->uninstall(['nodeorder']);

    $this->assertFalse($schema->fieldExists('taxonomy_index', 'weight'));
    $this->assertFalse($schema->indexExists('taxonomy_index', 'weight'));
  }

}
