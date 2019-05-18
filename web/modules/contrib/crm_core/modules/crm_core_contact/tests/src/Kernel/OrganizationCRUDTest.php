<?php

namespace Drupal\Tests\crm_core_contact\Kernel;

use Drupal\crm_core_activity\Entity\Activity;
use Drupal\crm_core_activity\Entity\ActivityType;
use Drupal\crm_core_contact\Entity\Organization;
use Drupal\crm_core_contact\Entity\OrganizationType;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests CRUD operations for the CRM Core Organization entity.
 *
 * @group crm_core
 */
class OrganizationCRUDTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'field',
    'text',
    'user',
    'crm_core',
    'crm_core_contact',
    'crm_core_activity',
    'dynamic_entity_reference',
    'datetime',
    'options',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(['field']);
    $this->installEntitySchema('crm_core_organization');
    $this->installEntitySchema('crm_core_activity');
  }

  /**
   * Tests CRUD of organization types.
   */
  public function testOrganizationType() {
    $id = 'new_organization_type';

    // Create.
    $organization_type = OrganizationType::create(
      [
        'id' => $id,
        'label' => $this->randomMachineName(),
        'description' => $this->randomString(),
        'primary_fields' => [],
      ]
    );
    $organization_type_id = $organization_type->id();
    $this->assertTrue(isset($organization_type_id) && $organization_type_id == $id, t('New organization type @id exists.', ['@id' => $id]));
    $this->assertEquals(SAVED_NEW, $organization_type->save(), 'Organization type saved.');

    // Load.
    $organization_type_load = OrganizationType::load($id);
    $this->assertEquals($organization_type->id(), $organization_type_load->id(), 'Loaded organization type has same id.');
    $this->assertEquals($organization_type->label(), $organization_type_load->label(), 'Loaded organization type has same label.');
    $this->assertEquals($organization_type->getDescription(), $organization_type_load->getDescription(), 'Loaded organization type has same description.');
    $uuid = $organization_type_load->uuid();
    $this->assertTrue(!empty($uuid), 'Loaded organization type has uuid.');

    // Test OrganizationType::getNames().
    $organization_type_labels = OrganizationType::getNames();
    $this->assertTrue($organization_type->label() == $organization_type_labels[$organization_type->id()]);

    // Delete.
    $organization_type_load->delete();
    $organization_type_load = OrganizationType::load($id);
    $this->assertNull($organization_type_load, 'Organization type deleted.');
  }

  /**
   * Tests CRUD of organizations.
   */
  public function testOrganization() {
    $this->installEntitySchema('user');

    $type = OrganizationType::create(['id' => 'test', 'primary_fields' => []]);
    $type->save();

    // Create.
    $organization = Organization::create(['type' => $type->id()]);
    $this->assertEquals(SAVED_NEW, $organization->save(), 'Organization saved.');

    // Create second organization.
    $organization_one = Organization::create(['type' => $type->id()]);
    $this->assertEquals(SAVED_NEW, $organization_one->save(), 'Organization saved.');

    // Load.
    $organization_load = Organization::load($organization->id());
    $uuid = $organization_load->uuid();
    $this->assertTrue(!empty($uuid), 'Loaded organization has uuid.');

    $activity_type = ActivityType::create(['type' => 'activity_test']);
    $activity_type->save();

    // Create activity and add participants organization.
    $activity = Activity::create(['type' => $activity_type->type]);
    $activity->get('activity_participants')->appendItem($organization);
    $activity->get('activity_participants')->appendItem($organization_one);
    $this->assertEquals(SAVED_NEW, $activity->save(), 'Activity saved.');

    // Load activity.
    $activity_load = Activity::load($activity->id());
    $this->assertTrue(!empty($activity_load->uuid()), 'Loaded activity has uuid.');

    // Delete first organization, activity should'n be deleted because it's
    // related to second organization.
    $organization->delete();
    $organization_load = Organization::load($organization->id());
    $this->assertNull($organization_load, 'Organization deleted.');
    $activity_load = Activity::load($activity->id());
    $this->assertNotNull($activity_load, 'Activity not deleted.');

    // Delete second organization and now activity should be deleted too.
    $organization_one->delete();
    $organization_load = Organization::load($organization_one->id());
    $this->assertNull($organization_load, 'Organization deleted.');
    $activity_load = Activity::load($activity->id());
    $this->assertNull($activity_load, 'Activity deleted.');
  }

}
