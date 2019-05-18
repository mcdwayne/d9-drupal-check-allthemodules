<?php

namespace Drupal\Tests\crm_core_user_sync\Kernel;

use Drupal\crm_core_contact\Entity\Individual;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\user\Entity\User;

/**
 * Test description.
 *
 * @group crm_core_user_sync
 */
class CrmCoreUserSyncRelationTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'user',
    'name',
    'crm_core_contact',
    'crm_core_user_sync',
  ];

  /**
   * Relation service.
   *
   * @var \Drupal\crm_core_user_sync\CrmCoreUserSyncRelationInterface
   */
  protected $relationService;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // User::delete() fails without this.
    $this->installSchema('user', ['users_data']);

    $this->installEntitySchema('crm_core_individual');
    $this->installEntitySchema('crm_core_user_sync_relation');

    $this->installConfig(['crm_core_user_sync']);

    $entityTypeManager = $this->container->get('entity_type.manager');
    $role_storage = $entityTypeManager->getStorage('user_role');
    $role_storage->create(['id' => 'customer'])->save();

    $config = $this->config('crm_core_user_sync.settings');
    $rules[] = [
      'role' => 'customer',
      'contact_type' => 'customer',
      'enabled' => TRUE,
      'weight' => 1,
    ];

    $rules[] = [
      'role' => 'authenticated',
      'contact_type' => 'individual',
      'enabled' => TRUE,
      'weight' => 10,
    ];

    $config
      ->set('rules', $rules)
      ->set('auto_sync_user_create', TRUE)
      ->save();

    $individual_type = $entityTypeManager
      ->getStorage('crm_core_individual_type')
      ->create([
        'type' => 'individual',
        'primary_fields' => [],
      ]);
    $individual_type->save();

    $customer_type = $entityTypeManager
      ->getStorage('crm_core_individual_type')
      ->create(['type' => 'customer', 'primary_fields' => []]);
    $customer_type->save();

    $this->relationService = $this->container->get('crm_core_user_sync.relation');
  }

  /**
   * Tests that relation and related individual was created.
   */
  public function testRelationCreated() {
    // Newly created tables for user and contact are empty so we have same IDs
    // for both - 1.
    $account_authenticated = User::create([
      'name' => 'authenticated',
      'uid' => rand(50, 100),
    ]);
    $account_authenticated->save();

    $authenticated_relation_id = $this->relationService->getRelationIdFromUserId($account_authenticated->id());
    $this->assertNotEmpty($authenticated_relation_id, 'Relation was created');

    $authenticated_individual_id = $this->relationService->getIndividualIdFromUserId($account_authenticated->id());
    $this->assertNotEmpty($authenticated_individual_id, 'Related contact was created');

    $related_account_id = $this->relationService->getUserIdFromIndividualId($authenticated_individual_id);
    $this->assertEquals($account_authenticated->id(), $related_account_id, 'Related ');
  }

  /**
   * Tests that configured rules are respected when related contacts created.
   */
  public function testRulesRespected() {
    $account_authenticated = User::create([
      'name' => 'authenticated',
      'uid' => rand(50, 100),
    ]);
    $account_authenticated->save();
    $contact_individual_id = $this->relationService->getIndividualIdFromUserId($account_authenticated->id());
    $this->assertNotEmpty($contact_individual_id, 'Individual contact was created when authenticated user account was created.');
    $contact_individual = Individual::load($contact_individual_id);
    $this->assertEquals('individual', $contact_individual->bundle(), 'Individual contact has correct bundle');

    $customer_values = [
      'name' => 'customer',
      'roles' => ['customer'],
    ];
    $account_customer = User::create($customer_values);
    $account_customer->save();

    $contact_customer_id = $this->relationService->getIndividualIdFromUserId($account_customer->id());
    $this->assertNotEmpty($contact_customer_id, 'Individual contact was created when customer user account was created.');
    $contact_customer = Individual::load($contact_customer_id);
    $this->assertEquals('customer', $contact_customer->bundle(), 'Individual contact has correct bundle');
  }

  /**
   * Tests that relation is deleted when user account id deleted.
   */
  public function testRelationDeleted() {
    $account_authenticated = User::create([
      'name' => 'authenticated',
      'uid' => rand(50, 100),
    ]);
    $account_authenticated->save();

    $authenticated_relation_id = $this->relationService->getRelationIdFromUserId($account_authenticated->id());
    $this->assertNotEmpty($authenticated_relation_id, 'Relation was created');

    $authenticated_individual_id = $this->relationService->getIndividualIdFromUserId($account_authenticated->id());
    $this->assertNotEmpty($authenticated_individual_id, 'Related contact was created');

    $account_authenticated->delete();

    $authenticated_relation_id = $this->relationService->getRelationIdFromUserId($account_authenticated->id());
    $this->assertEmpty($authenticated_relation_id, 'Relation was deleted');

    $authenticated_individual = Individual::load($authenticated_individual_id);
    $this->assertTrue(is_object($authenticated_individual), 'Related contact still exists');

    $individual_relation_id = $this->relationService->getRelationIdFromIndividualId($authenticated_individual->id());
    $this->assertEmpty($individual_relation_id, 'Relation was deleted');
  }

  /**
   * Tests that configured rules could be programmatically overridden.
   *
   * @see \Drupal\crm_core_user_sync\CrmCoreUserSyncRelation::relate()
   */
  public function testRulesOverride() {
    $account_authenticated = User::create([
      'name' => 'authenticated',
      'crm_core_no_auto_sync' => TRUE,
      'uid' => rand(50, 100),
    ]);
    $account_authenticated->save();

    $authenticated_relation_id = $this->relationService->getRelationIdFromUserId($account_authenticated->id());
    $this->assertEmpty($authenticated_relation_id, 'Relation was not created');

    $individual_customer = Individual::create(['type' => 'customer']);
    $individual_customer->save();

    $this->relationService->relate($account_authenticated, $individual_customer);
    $authenticated_relation_id = $this->relationService->getRelationIdFromUserId($account_authenticated->id());
    $this->assertEmpty($authenticated_relation_id, 'Relation was not created');

    $individual_individual = Individual::create(['type' => 'individual']);
    $individual_individual->save();

    $this->relationService->relate($account_authenticated, $individual_individual);
    $authenticated_relation_id = $this->relationService->getRelationIdFromUserId($account_authenticated->id());
    $this->assertNotEmpty($authenticated_relation_id, 'Relation was created');
  }

  /**
   * Tests that "relate" method is idempotent.
   *
   * @see \Drupal\crm_core_user_sync\CrmCoreUserSyncRelation::relate()
   */
  public function testNoDuplicatedContactsCreated() {
    $account_name = $this->randomString();
    $account_authenticated = User::create([
      'name' => $account_name,
      'uid' => rand(50, 100),
    ]);
    $account_authenticated->save();

    $authenticated_individual_id = $this->relationService->getIndividualIdFromUserId($account_authenticated->id());
    $this->assertNotEmpty($authenticated_individual_id, 'Related contact was created');

    $this->relationService->relate($account_authenticated);
    $contacts_with_name = $this->entityManager->getStorage('crm_core_individual')->getQuery()->condition('name.given', $account_name)->count()->execute();
    $this->assertEquals(1, $contacts_with_name, 'There is only one contact was created');
  }

}
