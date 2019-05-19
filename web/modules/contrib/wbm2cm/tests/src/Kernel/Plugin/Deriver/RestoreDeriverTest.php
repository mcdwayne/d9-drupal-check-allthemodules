<?php

namespace Drupal\Tests\wbm2cm\Plugin\Deriver;

use Drupal\KernelTests\KernelTestBase;

/**
 * @covers \Drupal\wbm2cm\Plugin\Deriver\RestoreDeriver
 * @group wbm2cm
 */
class RestoreDeriverTest extends KernelTestBase {

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
      ->getDefinition('wbm2cm_restore:node');

    $this->assertEquals('content_entity_revision:node', $migration['source']['plugin']);
    $this->assertEquals('vid', $migration['process']['vid']);
    $this->assertEquals('langcode', $migration['process']['langcode']);

    $lookup = $migration['process']['moderation_state'][0];
    $this->assertEquals(['nid', 'vid', 'langcode'], $lookup['source']);
    $this->assertEquals(['wbm2cm_save:node'], $lookup['migration']);

    $this->assertEquals('entity_revision:node', $migration['destination']['plugin']);
    $this->assertContains('wbm2cm_save:node', $migration['migration_dependencies']['required']);
  }

}
