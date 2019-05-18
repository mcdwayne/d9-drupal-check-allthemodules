<?php

namespace Drupal\Tests\crm_core_activity\Kernel;

use Drupal\crm_core_activity\Entity\Activity;
use Drupal\crm_core_activity_plugin_test\Plugin\crm_core_activity\ActivityType\ActivityTypeWithConfig;
use Drupal\crm_core_activity\Plugin\crm_core_activity\ActivityType\Generic;
use Drupal\crm_core_contact\Entity\Individual;
use Drupal\KernelTests\KernelTestBase;

/**
 * Unit test for activity type plugin.
 *
 * @group crm_core
 */
class ActivityTypePluginTest extends KernelTestBase {

  /**
   * Plugin manager for ActivityType.
   *
   * @var \Drupal\crm_core_activity\ActivityTypePluginManager
   */
  protected $pluginManager;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'crm_core_activity_plugin_test',
    'user',
    'crm_core_activity',
    'crm_core_contact',
    'text',
    'dynamic_entity_reference',
    'datetime',
    'name',
    'options',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('crm_core_activity');
    $this->installEntitySchema('crm_core_individual');
    $this->pluginManager = $this->container->get('plugin.manager.crm_core_activity.activity_type');
  }

  /**
   * Tests activity type plugin.
   */
  public function testActivityTypePlugin() {
    /** @var \Drupal\crm_core_activity\Entity\ActivityType $activity_type */
    $activity_type = $this->container->get('entity_type.manager')
      ->getStorage('crm_core_activity_type')
      ->create(
        [
          'name' => 'Test type',
          'type' => 'test_type',
          'description' => 'Test type description.',
          'plugin_id' => 'generic',
        ]
      );
    $activity_type->save();

    $individual_1 = Individual::create([
      'type' => 'customer',
      'name' => ['given' => 'John', 'family' => 'Smith'],
      'email_value' => 'test1@example.com',
      'email_type' => 'private',
    ]);
    $individual_1->save();
    $individual_2 = Individual::create([
      'type' => 'customer',
      'name' => ['given' => 'Mark', 'family' => 'Jones'],
      'email_value' => 'test2@example.com',
      'email_type' => 'corporate',
    ]);
    $individual_2->save();
    $activity = Activity::create([
      'type' => 'test_type',
      'title' => 'Activity title',
      'activity_participants' => [$individual_1, $individual_2],
    ]);
    $instance = $this->pluginManager->createInstance('generic');
    $this->assertEquals($instance->display($activity), []);
    $this->assertEquals($instance->label($activity), $activity->label());
    $this->assertEquals($activity->label(), 'Activity title');
  }

  /**
   * Tests plugin on activity type.
   */
  public function testPluginOnActivity() {
    /** @var \Drupal\crm_core_activity\Entity\ActivityType $activity_type */
    $activity_type = $this->container->get('entity_type.manager')
      ->getStorage('crm_core_activity_type')
      ->create(
        [
          'name' => 'Test type',
          'type' => 'test_type',
          'description' => 'Test type description.',
        ]
      );

    // Assign generic plugin without configuration.
    $activity_type->setPluginId('generic');
    $this->assertInstanceOf(Generic::class, $activity_type->getPlugin(), 'Correct plugin type was returned.');
    $activity_type->save();

    // Save type and check if stored config looks ok.
    $stored_type = $this->container->get('config.factory')->get('crm_core_activity.type.test_type')->get();
    $this->assertEquals('generic', $stored_type['plugin_id'], 'Plugin ID stored correctly.');
    $this->assertEquals([], $stored_type['plugin_configuration'], 'Plugin configuration stored correctly.');

    // Use plugin with configuration and check if defaults are set.
    $activity_type->setPluginId('with_config');
    $this->assertInstanceOf(ActivityTypeWithConfig::class, $activity_type->getPlugin(), 'Correct plugin instance was returned.');
    $this->assertEquals(['configuration_variable' => 'foo'], $activity_type->getPlugin()->getConfiguration(), 'Correct plugin configuration returned.');
    $activity_type->save();

    // Save type and check if stored config looks ok.
    $stored_type = $this->container->get('config.factory')->get('crm_core_activity.type.test_type')->get();
    $this->assertEquals('with_config', $stored_type['plugin_id'], 'Plugin ID stored correctly.');
    $this->assertEquals(['configuration_variable' => 'foo'], $stored_type['plugin_configuration'], 'Plugin configuration stored correctly.');

    // Change plugin configuration.
    $activity_type->setPluginConfiguration(['configuration_variable' => 'bar']);
    $this->assertInstanceOf(ActivityTypeWithConfig::class, $activity_type->getPlugin(), 'Correct plugin instance was returned.');
    $this->assertEquals(['configuration_variable' => 'bar'], $activity_type->getPlugin()->getConfiguration(), 'Correct plugin configuration returned.');
    $activity_type->save();

    // Save type and check if stored config looks ok.
    $stored_type = $this->container->get('config.factory')->get('crm_core_activity.type.test_type')->get();
    $this->assertEquals('with_config', $stored_type['plugin_id'], 'Plugin ID stored correctly.');
    $this->assertEquals(['configuration_variable' => 'bar'], $stored_type['plugin_configuration'], 'Plugin configuration stored correctly.');

    // Load type and check if saved state is restored correctly.
    $activity_type = $this->container->get('entity_type.manager')
      ->getStorage('crm_core_activity_type')
      ->loadUnchanged('test_type');
    $this->assertInstanceOf(ActivityTypeWithConfig::class, $activity_type->getPlugin(), 'Correct plugin instance was returned.');
    $this->assertEquals(['configuration_variable' => 'bar'], $activity_type->getPlugin()->getConfiguration(), 'Correct plugin configuration returned.');
  }

}
