<?php

namespace Drupal\Tests\ds_chains\Kernel;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Defines a test for chained field build.
 *
 * @group ds_chains
 */
class ChainedFieldBuildTest extends EntityKernelTestBase {

  use ChainedFieldTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['ds', 'ds_chains', 'field_test', 'layout_discovery'];

  /**
   * Tests build.
   */
  public function testBuild() {
    $this->createTestField('test_field', 'Some field', 'user', 'user');
    $user = $this->createUser([
      'test_field' => 'Some value',
    ]);
    $user_with_no_value = $this->createUser();
    $entity = EntityTest::create([
      'type' => 'entity_test',
      'user_id' => $user,
      'name' => 'Some entity',
    ]);
    $entity->save();
    $display = $this->configureEntityViewDisplay('test_field');
    $display->save();
    $view_builder = $this->container->get('entity_type.manager')->getViewBuilder('entity_test');
    $build = $view_builder->view($entity);
    $rendered = $this->container->get('renderer')->renderPlain($build);
    $this->assertContains('PONIES|Some value', (string) $rendered);
    $entity->user_id = $user_with_no_value;
    $entity->save();
    $build = $view_builder->view($entity);
    $rendered = $this->container->get('renderer')->renderPlain($build);
    $this->assertNotContains('PONIES|Some value', (string) $rendered);
  }

  /**
   * Tests empty settings object.
   */
  public function testEmptySettings() {
    $this->createTestField('test_field', 'Some field', 'user', 'user');
    $user = $this->createUser([
      'test_field' => 'Some value',
    ]);
    $user_with_no_value = $this->createUser();
    $entity = EntityTest::create([
      'type' => 'entity_test',
      'user_id' => $user,
      'name' => 'Some entity',
    ]);
    $entity->save();
    $display = $this->configureEntityViewDisplay('test_field');
    $plugin_id = 'ds_chains:entity_test/entity_test/user_id/test_field';
    $fields = $display->getThirdPartySetting('ds', 'fields', []);
    $fields[$plugin_id] = [
      'plugin_id' => $plugin_id,
      'weight' => 1,
      'label' => 'hidden',
      'formatter' => 'field_test_default',
      // Deliberately omit settings.
    ];
    $display->setThirdPartySetting('ds', 'fields', $fields);
    $display->save();
    $view_builder = $this->container->get('entity_type.manager')->getViewBuilder('entity_test');
    $build = $view_builder->view($entity);
    $rendered = $this->container->get('renderer')->renderPlain($build);
    $this->assertContains('Some value', (string) $rendered);
  }

  /**
   * Test the UI limit feature.
   */
  public function testUiLimit() {
    $this->createTestField('test_field', 'Some field', 'user', 'user');

    $user_a = $this->createUser([
      'test_field' => 'Some value A',
    ]);
    $user_b = $this->createUser([
      'test_field' => 'Some value B',
    ]);

    $entity = EntityTest::create([
      'type' => 'entity_test',
      'user_id' => [
        ['entity' => $user_a],
        ['entity' => $user_b],
      ],
      'name' => 'Some entity',
    ]);
    $entity->save();

    $display = $this->configureEntityViewDisplay('test_field');
    $plugin_id = 'ds_chains:entity_test/entity_test/user_id/test_field';
    $fields = $display->getThirdPartySetting('ds', 'fields', []);
    $fields[$plugin_id] = [
      'plugin_id' => $plugin_id,
      'weight' => 1,
      'label' => 'hidden',
      'formatter' => 'field_test_default',
      // Deliberately omit settings.
    ];
    $display->setThirdPartySetting('ds', 'fields', $fields);
    $display->save();

    $view_builder = $this->container->get('entity_type.manager')->getViewBuilder('entity_test');
    $build = $view_builder->view($entity);

    $rendered = $this->container->get('renderer')->renderPlain($build);
    $this->assertContains('Some value A', (string) $rendered);
    $this->assertContains('Some value B', (string) $rendered);

    $fields[$plugin_id]['settings']['chain_settings']['ui_limit'] = 1;
    $display->setThirdPartySetting('ds', 'fields', $fields);
    $display->save();

    $build = $view_builder->view($entity);
    $rendered = $this->container->get('renderer')->renderPlain($build);
    $this->assertContains('Some value A', (string) $rendered);
    $this->assertNotContains('Some value B', (string) $rendered);
  }

}
