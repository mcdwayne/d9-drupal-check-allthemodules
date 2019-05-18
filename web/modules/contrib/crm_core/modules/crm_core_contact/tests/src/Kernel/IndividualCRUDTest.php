<?php

namespace Drupal\Tests\crm_core_contact\Kernel;

use Drupal\crm_core_activity\Entity\Activity;
use Drupal\crm_core_activity\Entity\ActivityType;
use Drupal\crm_core_contact\Entity\Individual;
use Drupal\crm_core_contact\Entity\IndividualType;
use Drupal\crm_core_contact\Entity\Organization;
use Drupal\crm_core_contact\Entity\OrganizationType;
use Drupal\relation\Entity\RelationType;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests CRUD operations for the CRM Core Individual entity.
 *
 * @group crm_core
 */
class IndividualCRUDTest extends KernelTestBase {

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
    'relation',
    'name',
    'options',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(['field']);
    $this->installEntitySchema('crm_core_individual');
    $this->installEntitySchema('crm_core_organization');
    $this->installEntitySchema('crm_core_activity');
    $this->installEntitySchema('relation');

    $this->pluginManager = $this->container->get('plugin.manager.action');
  }

  /**
   * Tests CRUD of individual types.
   */
  public function testIndividualType() {
    $type = 'dog';

    // Create.
    $individual_type = IndividualType::create(['type' => $type]);
    $this->assertTrue(
      isset($individual_type->type) && $individual_type->type == $type,
      'New individual type exists.'
    );
    // @todo Check if this still must be the case.
    // $this->assertTrue($individual_type->locked, t('New individual type has locked set to TRUE.'));
    $individual_type->name = $this->randomMachineName();
    $individual_type->description = $this->randomString();
    $individual_type->primary_fields = [];
    $this->assertEquals(
      SAVED_NEW,
      $individual_type->save(),
      'Individual type saved.'
    );

    // Load.
    $individual_type_load = IndividualType::load($type);
    $this->assertEquals(
      $individual_type->type,
      $individual_type_load->type,
      'Loaded individual type has same type.'
    );
    $this->assertEquals(
      $individual_type->name,
      $individual_type_load->name,
      'Loaded individual type has same name.'
    );
    $this->assertEquals(
      $individual_type->description,
      $individual_type_load->description,
      'Loaded individual type has same description.'
    );
    $uuid = $individual_type_load->uuid();
    $this->assertTrue(!empty($uuid), 'Loaded individual type has uuid.');

    // Test IndividualType::getNames().
    $individual_type_labels = IndividualType::getNames();
    $this->assertTrue(
      $individual_type->name == $individual_type_labels[$individual_type->type]
    );

    // Delete.
    $individual_type_load->delete();
    $individual_type_load = IndividualType::load($type);
    $this->assertNull($individual_type_load, 'Individual type deleted.');
  }

  /**
   * Tests CRUD of individuals.
   *
   * @todo Check if working once https://drupal.org/node/2239969 got committed.
   */
  public function testIndividual() {
    $this->installEntitySchema('user');

    $type = IndividualType::create(['type' => 'test']);
    $type->primary_fields = [];
    $type->save();

    // Create.
    $individual = Individual::create(['type' => $type->type]);
    $this->assertEquals(SAVED_NEW, $individual->save(), 'Individual saved.');

    // Create second individual.
    $individual_one = Individual::create(['type' => $type->type]);
    $this->assertEquals(
      SAVED_NEW,
      $individual_one->save(),
      'Individual saved.'
    );

    // Assert default labels.
    $this->assertEquals(
      'Nameless #' . $individual_one->id(),
      $individual_one->label()
    );
    $individual_one->name->given = 'First';
    $individual_one->name->family = 'Last';
    $individual_one->save();
    $this->assertEquals('First Last', $individual_one->label());

    // Load.
    $individual_load = Individual::load($individual->id());
    $uuid = $individual_load->uuid();
    $this->assertTrue(!empty($uuid), 'Loaded individual has uuid.');

    $activity_type = ActivityType::create(['type' => 'activity_test']);
    $activity_type->save();

    // Create activity and add participants individual.
    $activity = Activity::create(['type' => $activity_type->type]);
    $activity->get('activity_participants')->appendItem($individual);
    $activity->get('activity_participants')->appendItem($individual_one);
    $this->assertEquals(SAVED_NEW, $activity->save(), 'Activity saved.');

    // Load activity.
    $activity_load = Activity::load($activity->id());
    $this->assertTrue(
      !empty($activity_load->uuid()),
      'Loaded activity has uuid.'
    );

    // Delete first individual, activity should'n be deleted
    // because it's related to second individual.
    $individual->delete();
    $individual_load = Individual::load($individual->id());
    $this->assertNull($individual_load, 'Individual deleted.');
    $activity_load = Activity::load($activity->id());
    $this->assertNotNull($activity_load, 'Activity not deleted.');

    // Delete second individual and now activity should be deleted too.
    $individual_one->delete();
    $individual_load = Individual::load($individual_one->id());
    $this->assertNull($individual_load, 'Contact deleted.');
    $activity_load = Activity::load($activity->id());
    $this->assertNull($activity_load, 'Activity deleted.');
  }

  /**
   * Tests action plugins.
   */
  public function testActionPlugins() {
    // Create individual types.
    $individual_type_1 = IndividualType::create(['type' => 'seller']);
    $individual_type_1->primary_fields = [];
    $individual_type_1->save();
    $individual_type_2 = IndividualType::create(['type' => 'customer']);
    $individual_type_2->primary_fields = [];
    $individual_type_2->save();

    $organization_type = OrganizationType::create(
      [
        'id' => 'supplier',
        'label' => $this->randomMachineName(),
        'description' => $this->randomString(),
        'primary_fields' => [],
      ]
    );
    $organization_type->save();

    // Create seller individual.
    $seller_individual = Individual::create(
      ['type' => 'seller', 'name' => ['title' => 'Will', 'family' => 'Smith']]
    );
    $seller_individual->save();

    // Create 3 individual customers.
    $individual_customer_1 = Individual::create(
      [
        'type' => 'customer',
        'name' => ['given' => 'John', 'family' => 'Smith'],
      ]
    );
    $individual_customer_1->save();
    $individual_customer_2 = Individual::create(
      [
        'type' => 'customer',
        'name' => ['given' => 'Mark', 'family' => 'Jones'],
      ]
    );
    $individual_customer_2->save();
    $individual_customer_3 = Individual::create(
      [
        'type' => 'customer',
        'name' => ['given' => 'Joan', 'family' => 'Johnson'],
      ]
    );
    $individual_customer_3->save();

    // Create one organization.
    $organization = Organization::create(
      [
        'type' => $organization_type->id(),
      ]
    );
    $organization->save();

    // Create crm_member relation type.
    $relation_type = RelationType::create(
      [
        'id' => 'crm_member',
        'source_bundles' => [
          'crm_core_individual:*',
          'crm_core_organization:*',
        ],
        'target_bundles' => ['crm_core_individual:seller'],
      ]
    );
    $relation_type->save();

    // Create meeting activity.
    $meeting_activity = Activity::create(
      [
        'type' => 'meeting',
        'title' => $this->randomString(),
        'activity_participants' => [
          $individual_customer_2,
          $individual_customer_3,
        ],
      ]
    );
    $meeting_activity->save();

    // @codingStandardsIgnoreStart
    // Test join_into_household_action.
    // @todo there is no more household bundle after we rename contact to individual.
    // $join_into_household_action_plugin = $this->pluginManager->createInstance('join_into_household_action', ['household' => $household_contact]);
    // $join_into_household_action_plugin->executeMultiple([$individual_contact_1, $individual_contact_2, $organization]);
    // $relations = Relation::loadMultiple();
    // Test that there are two new relations with correct endpoints and types.
    // $this->assertEquals(count($relations), 3, 'Three new relations were created during this test.');
    // $this->assertEquals($relations[1]->relation_type->target_id, 'crm_member');
    // $this->assertEquals($relations[2]->relation_type->target_id, 'crm_member');
    // $this->assertEquals($relations[3]->relation_type->target_id, 'crm_member');
    // $this->assertEquals($relations[1]->endpoints[0]->entity_id, $individual_contact_1->id());
    // $this->assertEquals($relations[1]->endpoints[1]->entity_id, $household_contact->id());
    // $this->assertEquals($relations[2]->endpoints[0]->entity_id, $individual_contact_2->id());
    // $this->assertEquals($relations[2]->endpoints[1]->entity_id, $household_contact->id());
    // $this->assertEquals($relations[3]->endpoints[0]->entity_id, $organization->id());
    // $this->assertEquals($relations[3]->endpoints[1]->entity_id, $household_contact->id());
    // Test merge_contacts_action.
    // @todo contacts are now individuals
    // $data = array(
    //  'data' => array(
    //    'contact_id' => $individual_contact_1->id(),
    //    'name' => array($individual_contact_3->id() => $individual_contact_3->get('name')),
    //  ),
    // );
    // Create relation between two individuals.
    // $endpoints = [
    //  [
    //    'entity_type' => 'crm_core_individual',
    //    'entity_id' => $household_contact->id(),
    //  ],
    //  [
    //    'entity_type' => 'crm_core_individual',
    //    'entity_id' => $individual_contact_3->id(),
    //  ],
    // ];
    // $relation = Relation::create(array('relation_type' => 'crm_member'));
    // $relation->endpoints = $endpoints;
    // $relation->save();
    // $merge_contacts_action_plugin = $this->pluginManager->createInstance('merge_contacts_action', $data);
    // $merge_contacts_action_plugin->executeMultiple([$individual_contact_1, $individual_contact_3]);
    // Test that there is no individual_contact_3.
    // $individual_contact_3 = Individual::load($individual_contact_3->id());
    // $this->assertNull($individual_contact_3);
    // Test that values are updated in meeting_activity.
    // $meeting_activity = Activity::load($meeting_activity->id());
    // $this->assertEquals($meeting_activity->activity_participants[0]->target_id, $individual_contact_2->id());
    // $this->assertEquals($meeting_activity->activity_participants[1]->target_id, $individual_contact_1->id());
    // Test that relation has been created with correct values.
    // $relations = Relation::loadMultiple();
    // $this->assertEquals($relations[5]->relation_type->target_id, 'crm_member');
    // $this->assertEquals($relations[5]->endpoints[0]->entity_id, $individual_contact_1->id());
    // $this->assertEquals($relations[5]->endpoints[1]->entity_id, $household_contact->id());
    // @codingStandardsIgnoreEnd
  }

}
