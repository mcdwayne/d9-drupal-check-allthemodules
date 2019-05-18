<?php

namespace Drupal\Tests\migrate_source_ical\Unit\Plugin\migrate\source;

use Drupal\KernelTests\KernelTestBase;
use Drupal\migrate\Plugin\MigratePluginManagerInterface;

/**
 * @coversDefaultClass \Drupal\migrate_source_ical\Plugin\migrate\source\Ical
 *
 * @group migrate_source_ical
 */
class IcalKernelTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['migrate', 'migrate_source_ical'];

  /**
   * Tests the construction of Ical.
   *
   * @covers ::__construct
   */
  public function testCreate() {
    /** @var MigratePluginManagerInterface $migrationSourceManager */
    $migrationSourceManager = $this->container->get('plugin.manager.migrate.source');
    $this->assertTrue($migrationSourceManager->hasDefinition('ical'));
  }

}
