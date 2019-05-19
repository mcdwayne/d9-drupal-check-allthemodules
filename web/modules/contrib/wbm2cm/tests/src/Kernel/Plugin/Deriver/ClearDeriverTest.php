<?php

namespace Drupal\Tests\wbm2cm\Plugin\Deriver;

use Drupal\KernelTests\KernelTestBase;

/**
 * @covers \Drupal\wbm2cm\Plugin\Deriver\ClearDeriver
 * @group wbm2cm
 */
class ClearDeriverTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'filter',
    'migrate',
    'node',
    'options',
    'system',
    'text',
    'user',
    'views',
    'wbm2cm',
    'workbench_moderation',
  ];

  public function testDeriver() {
    $this->container->get('state')
      ->set('moderation_entity_types', ['node']);

    $migration = $this->container->get('plugin.manager.migration')
      ->getDefinition('wbm2cm_clear:node');

    $this->assertEquals('content_entity_revision:node', $migration['source']['plugin']);
    $this->assertEquals('vid', $migration['process']['vid']);
    $this->assertEquals('langcode', $migration['process']['langcode']);
    $this->assertEquals('entity_revision:node', $migration['destination']['plugin']);
    $this->assertContains('wbm2cm_save:node', $migration['migration_dependencies']['required']);
  }

}
