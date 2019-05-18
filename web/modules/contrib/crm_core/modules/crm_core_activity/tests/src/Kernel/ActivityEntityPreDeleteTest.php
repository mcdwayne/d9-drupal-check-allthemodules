<?php

namespace Drupal\Tests\crm_core_activity\Kernel;

use Drupal\crm_core_activity\Entity\Activity;
use Drupal\crm_core_contact\Entity\Individual;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests crm_core_activity_entity_predelete().
 *
 * @group crm_core
 * @covers crm_core_activity_entity_predelete
 */
class ActivityEntityPreDeleteTest extends KernelTestBase {

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
  public function testContactPreDelete() {
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
    $activity_1 = Activity::create([
      'type' => 'test_type',
      'title' => 'Activity title',
      'activity_participants' => [$individual_1, $individual_2],
    ]);
    $activity_1->save();

    $activity_2 = Activity::create([
      'type' => 'test_type',
      'title' => 'Activity title 2',
      'activity_participants' => [$individual_2],
    ]);
    $activity_2->save();

    $individual_2->delete();

    $loaded_activity = Activity::load($activity_2->id());
    $this->assertNull($loaded_activity, 'Activity 2 was deleted.');

    $loaded_activity = Activity::load($activity_1->id());
    $this->assertTrue((bool) $loaded_activity, 'Activity 1 was loaded.');

    $individual_1->delete();
    $loaded_activity = Activity::load($activity_1->id());
    $this->assertNull($loaded_activity, 'Activity 1 was deleted.');
  }

}
