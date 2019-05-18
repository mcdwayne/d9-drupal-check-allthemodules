<?php

namespace Drupal\Tests\ds_chains\Kernel;

use Drupal\ds\Ds;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Defines a class for testing derivative discovery.
 *
 * @group ds_chains
 */
class DiscoveryTest extends EntityKernelTestBase {

  use ChainedFieldTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['ds', 'ds_chains', 'field_test'];

  /**
   * Tests author chaining.
   */
  public function testUserIdChainedField() {
    $this->createTestField('test_field', 'Some field', 'user', 'user');
    $display = $this->getEntityViewDisplay();
    $display->setThirdPartySetting('ds_chains', 'fields', ['user_id']);
    $display->save();
    $manager = $this->container->get('plugin.manager.ds');
    $teaser = $this->getEntityViewDisplay('teaser');
    $plugin_id = 'ds_chains:entity_test/entity_test/user_id/test_field';
    $this->assertTrue($manager->hasDefinition($plugin_id));
    $definition = $manager->getDefinition($plugin_id);
    $this->assertEquals('User ID: Some field', $definition['title']);
    $this->assertEquals('entity_test', $definition['entity_type']);
    $this->assertEquals('user', $definition['target_entity_type']);
    $this->assertEquals('entity_test', $definition['bundle']);
    $this->assertEquals('user', $definition['target_bundle']);
    $this->assertEquals('test_field', $definition['chained_field_name']);
    $this->assertEquals('test_field', $definition['chained_field_type']);
    $this->assertEquals('Some field', $definition['chained_field_title']);
    $this->assertEquals('user_id', $definition['field_name']);
    $this->assertEquals(['default'], $definition['view_modes']);
    $fields = Ds::getFields('entity_test');
    $entity = EntityTest::create([
      'type' => 'entity_test',
    ]);
    $instance = Ds::getFieldInstance($plugin_id, $fields[$plugin_id], $entity, 'default', $display, []);
    $this->assertTrue($instance->isAllowed());
    $instance = Ds::getFieldInstance($plugin_id, $fields[$plugin_id], $entity, 'teaser', $teaser, []);
    $this->assertFalse($instance->isAllowed());
    $this->assertEquals('Applicable', (string) $instance->formatters()['field_test_applicable']);
    entity_test_create_bundle('another_bundle');
    $instance = Ds::getFieldInstance($plugin_id, $fields[$plugin_id], EntityTest::create([
      'type' => 'another_bundle',
    ]), 'default', $display, []);
    $this->assertFalse($instance->isAllowed());
  }

}
