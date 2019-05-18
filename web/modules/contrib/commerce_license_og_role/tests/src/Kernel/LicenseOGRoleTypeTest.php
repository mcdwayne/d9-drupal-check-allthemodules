<?php

namespace Drupal\Tests\commerce_license_og_role\Kernel\System;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\og\Entity\OgRole;
use Drupal\og\OgMembershipInterface;

/**
 * Tests the OG role license type.
 *
 * @group commerce_license_og_role
 */
class LicenseOGRoleTypeTest extends EntityKernelTestBase {

  /**
   * The modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'user',
    'state_machine',
    'entity',
    'commerce',
    'commerce_price',
    'commerce_product',
    'recurring_period',
    'commerce_license',
    'field',
    'options',
    'text' ,
    'og',
    'dynamic_entity_reference',
    'commerce_license_og_role',
    'entity_test',
  ];

  /**
   * The license storage.
   */
  protected $licenseStorage;

  /**
   * The role storage.
   */
  protected $roleStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(['og']);
    $this->installEntitySchema('og_membership');
    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('commerce_product_variation');
    $this->installEntitySchema('commerce_license');

    // Install the bundle plugins for the license entity type which this
    // module provides. This takes care of creating the fields which the bundle
    // plugins define.
    $this->container->get('entity.bundle_plugin_installer')->installBundles(
      $this->container->get('entity_type.manager')->getDefinition('commerce_license'),
      ['commerce_license_og_role']
    );

    $this->licenseStorage = $this->container->get('entity_type.manager')->getStorage('commerce_license');
    $this->licenseTypeManager = $this->container->get('plugin.manager.commerce_license_type');
    $this->ogRoleStorage = $this->container->get('entity_type.manager')->getStorage('og_role');
    $this->ogMembershipManager = $this->container->get('og.membership_manager');

    // Declare the test entity as being a group.
    $this->groupTypeManager = $this->container->get('og.group_type_manager');
    $this->groupBundle = $this->randomMachineName();
    $this->groupTypeManager->addGroup('entity_test', $this->groupBundle);

    // Create a user who owns the groups.
    $group_owner = $this->createUser();

    // Create two groups.
    $this->group_licensed = EntityTest::create([
      'type' => $this->groupBundle,
      'name' => $this->randomString(),
      'user_id' => $group_owner->id(),
    ]);
    $this->group_licensed->save();

    $this->group_unlicensed = EntityTest::create([
      'type' => $this->groupBundle,
      'name' => $this->randomString(),
      'user_id' => $group_owner->id(),
    ]);
    $this->group_unlicensed->save();
  }

  /**
   * Tests a license that grants the member role to a non-member.
   */
  public function testMemberLicenseNonMember() {
    // Create a user who is not in any group.
    $license_owner = $this->createUser();

    $member_role = OgRole::loadByGroupAndName($this->group_licensed, OgRole::AUTHENTICATED);

    // Create a license in the 'new' state, owned by the user.
    $license = $this->licenseStorage->create([
      'type' => 'commerce_license_og_role',
      'state' => 'new',
      'product' => 1,
      'uid' => $license_owner->id(),
      // Use the unlimited expiry plugin as it's simple.
      'expiration_type' => [
        'target_plugin_id' => 'unlimited',
        'target_plugin_configuration' => [],
      ],
      'license_og_group' => $this->group_licensed,
      'license_og_role' => $member_role,
    ]);

    $license->save();

    // Assert the user is not in the group.
    $membership = $this->ogMembershipManager->getMembership($this->group_licensed, $license_owner);
    $this->assertNull($membership, "The license owner is not a member of the licensed group.");

    // Change the state to 'active' and save the license. This should cause the
    // plugin to grant.
    $license->state = 'active';
    $license->save();
    $license = $this->reloadEntity($license);

    $this->ogMembershipManager->reset();

    // The license owner is now a member of the group (and only this group).
    $membership = $this->ogMembershipManager->getMembership($this->group_licensed, $license_owner);
    $this->assertNotNull($membership, "The license owner is a member of the licensed group.");

    $this->assertMembershipHasRole($member_role->id(), $membership);
    $this->assertEqual($membership, $license->license_og_membership->entity, 'The license has a reference to the membership.');

    $membership = $this->ogMembershipManager->getMembership($this->group_unlicensed, $license_owner);
    $this->assertNull($membership, "The license owner is not a member of the unlicensed group.");

    // Change the state to 'expired' and save the license. This should cause the
    // plugin to revoke.
    $license->state = 'expired';
    $license->save();

    // Revoke the license.
    $license->getTypePlugin()->revokeLicense($license);
    $this->ogMembershipManager->reset();

    // Assert the user is not in the group.
    $membership = $this->ogMembershipManager->getMembership($this->group_licensed, $license_owner);
    $this->assertNull($membership, "The license owner is not a member of the licensed group.");

    $this->assertEqual(NULL, $license->license_og_membership->entity, 'The license no longer has a reference to the membership.');
  }

  /**
   * Tests a license creates a membership of the configured type.
   */
  public function testMemberCustomMembershipType() {
    // Create a user who is not in any group.
    $license_owner = $this->createUser();

    $member_role = OgRole::loadByGroupAndName($this->group_licensed, OgRole::AUTHENTICATED);

    // Create a membership type.
    $membership_type_id = $this->randomMachineName();
    $membership_type = $this->container->get('entity_type.manager')->getStorage('og_membership_type')->create([
      'type' => $membership_type_id,
    ]);

    // Create a license in the 'new' state, owned by the user.
    $license = $this->licenseStorage->create([
      'type' => 'commerce_license_og_role',
      'state' => 'new',
      'product' => 1,
      'uid' => $license_owner->id(),
      // Use the unlimited expiry plugin as it's simple.
      'expiration_type' => [
        'target_plugin_id' => 'unlimited',
        'target_plugin_configuration' => [],
      ],
      'license_og_group' => $this->group_licensed,
      'license_og_role' => $member_role,
      'license_og_membership_type' => $membership_type_id,
    ]);

    $license->save();

    // Assert the user is not in the group.
    $membership = $this->ogMembershipManager->getMembership($this->group_licensed, $license_owner);
    $this->assertNull($membership, "The license owner is not a member of the licensed group.");

    // Don't bother pushing the license through state changes, as that is
    // by covered by LicenseStateChangeTest. Just call the plugin direct to
    // grant the license.
    $license->getTypePlugin()->grantLicense($license);
    $this->ogMembershipManager->reset();

    // The license owner now has a group membership of the custom type.
    $membership = $this->ogMembershipManager->getMembership($this->group_licensed, $license_owner);
    $this->assertEquals($membership_type_id, $membership->bundle(), "The license owner's membership entity is of the set type.");
  }

  /**
   * Tests a license that grants membership by activating a pending membership.
   */
  public function testMemberLicensePendingMember() {
    // Create a user who is not in any group.
    $license_owner = $this->createUser();

    // Make the user a member of the group, with a pending membership.
    $membership = $this->ogMembershipManager->createMembership($this->group_licensed, $license_owner);
    $membership->setState(OgMembershipInterface::STATE_PENDING);
    $membership->save();
    $membership_id = $membership->id();

    // Grant the basic 'member' role.
    $member_role = OgRole::loadByGroupAndName($this->group_licensed, OgRole::AUTHENTICATED);

    // Create a license in the 'new' state, owned by the user.
    $license = $this->licenseStorage->create([
      'type' => 'commerce_license_og_role',
      'state' => 'new',
      'product' => 1,
      'uid' => $license_owner->id(),
      // Use the unlimited expiry plugin as it's simple.
      'expiration_type' => [
        'target_plugin_id' => 'unlimited',
        'target_plugin_configuration' => [],
      ],
      'license_og_group' => $this->group_licensed,
      'license_og_role' => $member_role,
    ]);

    $license->save();

    // Assert the user does not have an active membership.
    $membership = $this->ogMembershipManager->getMembership($this->group_licensed, $license_owner);
    $this->assertNull($membership, "The license owner is not a member of the licensed group.");

    // Assert the user has a pending membership.
    $membership = $this->ogMembershipManager->getMembership($this->group_licensed, $license_owner, [OgMembershipInterface::STATE_PENDING]);
    $this->assertNotNull($membership, "The license owner is a member of the licensed group.");

    // Change the state to 'active' and save the license. This should cause the
    // plugin to grant.
    $license->state = 'active';
    $license->save();

    $license = $this->reloadEntity($license);
    $this->ogMembershipManager->reset();

    // The license owner is still a member of the group (and only this group),
    // and now has an active state.
    $membership = $this->ogMembershipManager->getMembership($this->group_licensed, $license_owner);
    $this->assertNotNull($membership, "The license owner is a member of the licensed group.");
    $this->assertEquals($membership_id, $membership->id(), "The existing membership was updated.");
    $this->assertMembershipHasRole($member_role->id(), $membership, "The license owner has the member role.");
    $this->assertEquals(OgMembershipInterface::STATE_ACTIVE, $membership->getState(), "The license owner has an active membership.");
    $this->assertEquals($membership, $license->license_og_membership->entity, 'The license has a reference to the membership.');

    // OG doesn't enforce a single membership, so check we didn't create a
    // duplicate.
    // TODO: Remove this when https://github.com/Gizra/og/issues/326 is fixed.
    $memberships = $this->ogMembershipManager->getMemberships($license_owner, []);
    $this->assertCount(1, $memberships);

    $membership = $this->ogMembershipManager->getMembership($this->group_unlicensed, $license_owner);
    $this->assertNull($membership, "The license owner is not a member of the unlicensed group.");

    // Revoke the license.
    $license->getTypePlugin()->revokeLicense($license);
    $this->ogMembershipManager->reset();

    // Assert the user is not in the group.
    $membership = $this->ogMembershipManager->getMembership($this->group_licensed, $license_owner);
    $this->assertNull($membership, "The license owner is not a member of the licensed group.");
  }

  /**
   * Tests a license attempting to grant a blocked membership.
   */
  public function testMemberLicenseBlockedMember() {
    // Create a user who is not in any group.
    $license_owner = $this->createUser();

    // Make the user a member of the group, with a pending membership.
    $membership = $this->ogMembershipManager->createMembership($this->group_licensed, $license_owner);
    $membership->setState(OgMembershipInterface::STATE_BLOCKED);
    $membership->save();

    // Grant the basic 'member' role.
    $member_role = OgRole::loadByGroupAndName($this->group_licensed, OgRole::AUTHENTICATED);

    // Create a license in the 'new' state, owned by the user.
    $license = $this->licenseStorage->create([
      'type' => 'commerce_license_og_role',
      'state' => 'new',
      'product' => 1,
      'uid' => $license_owner->id(),
      // Use the unlimited expiry plugin as it's simple.
      'expiration_type' => [
        'target_plugin_id' => 'unlimited',
        'target_plugin_configuration' => [],
      ],
      'license_og_group' => $this->group_licensed,
      'license_og_role' => $member_role,
    ]);

    $license->save();

    // Assert the user does not have an active membership.
    $membership = $this->ogMembershipManager->getMembership($this->group_licensed, $license_owner);
    $this->assertNull($membership, "The license owner is not a member of the licensed group.");

    // Assert the user has a blocked membership.
    $membership = $this->ogMembershipManager->getMembership($this->group_licensed, $license_owner, [OgMembershipInterface::STATE_BLOCKED]);
    $this->assertNotNull($membership, "The license owner is a blocked member of the licensed group.");

    // Don't bother pushing the license through state changes, as that is
    // by covered by LicenseStateChangeTest. Just call the plugin direct to
    // grant the license.
    try {
      $license->getTypePlugin()->grantLicense($license);
      $this->fail('Exception was thrown.');
    }
    catch (\Exception $e) {
      $this->pass('Exception was thrown.');
    }

    $this->ogMembershipManager->reset();

    // The user is still blocked in the group.
    $membership = $this->ogMembershipManager->getMembership($this->group_licensed, $license_owner, [OgMembershipInterface::STATE_BLOCKED]);
    $this->assertNotNull($membership, "The license owner is a blocked member of the licensed group.");
  }

  /**
   * Tests a license that grants a custom role to a group member.
   */
  public function testMemberLicenseMember() {
    // Create a user who is not in any group.
    $license_owner = $this->createUser();

    // Make the user a member of the group.
    $membership = $this->ogMembershipManager->createMembership($this->group_licensed, $license_owner);
    $membership->save();

    // Create a role.
    $licensed_role = OgRole::create();
    $licensed_role
      ->setName($this->randomMachineName())
      ->setLabel($this->randomString())
      ->setGroupType('entity_test')
      ->setGroupBundle($this->groupBundle)
      ->save();

    // Create a license in the 'new' state, owned by the user.
    $license = $this->licenseStorage->create([
      'type' => 'commerce_license_og_role',
      'state' => 'new',
      'product' => 1,
      'uid' => $license_owner->id(),
      // Use the unlimited expiry plugin as it's simple.
      'expiration_type' => [
        'target_plugin_id' => 'unlimited',
        'target_plugin_configuration' => [],
      ],
      'license_og_group' => $this->group_licensed,
      'license_og_role' => $licensed_role,
    ]);

    $license->save();

    // Assert the user is already in the group.
    $membership = $this->ogMembershipManager->getMembership($this->group_licensed, $license_owner);
    $this->assertNotNull($membership, "The license owner is a member of the licensed group.");

    // Don't bother pushing the license through state changes, as that is
    // by covered by LicenseStateChangeTest. Just call the plugin direct to
    // grant the license.
    $license->getTypePlugin()->grantLicense($license);
    $this->ogMembershipManager->reset();

    // The license owner is still a member of the group (and only this group).
    $membership = $this->ogMembershipManager->getMembership($this->group_licensed, $license_owner);
    $this->assertNotNull($membership, "The license owner is a member of the licensed group.");

    $this->assertMembershipHasRole($licensed_role->id(), $membership, "The license owner has the custom role.");

    // OG doesn't enforce a single membership, so check we didn't create a
    // duplicate.
    // TODO: Remove this when https://github.com/Gizra/og/issues/326 is fixed.
    $memberships = $this->ogMembershipManager->getMemberships($license_owner);
    $this->assertCount(1, $memberships);

    $membership = $this->ogMembershipManager->getMembership($this->group_unlicensed, $license_owner);
    $this->assertNull($membership, "The license owner is not a member of the unlicensed group.");

    // Revoke the license.
    $license->getTypePlugin()->revokeLicense($license);
    $this->ogMembershipManager->reset();

    // Assert the user is still in the group, but no longer has the role.
    $membership = $this->ogMembershipManager->getMembership($this->group_licensed, $license_owner);
    $membership = $this->reloadEntity($membership);

    $this->assertNotNull($membership, "The license owner is still a member of the licensed group.");
    $this->assertMembershipHasNotRole($licensed_role->id(), $membership, "The license owner no longer has the custom role.");
  }

  // TODO: grant a custom role to a non-member

  /**
   * Tests a license receives field values from a configured plugin.
   */
  public function testLicenseCreationFromPlugin() {
    // Create a user who is not in any group.
    $license_owner = $this->createUser();

    // Create a membership type.
    $membership_type_id = $this->randomMachineName();
    $membership_type = $this->container->get('entity_type.manager')->getStorage('og_membership_type')->create([
      'type' => $membership_type_id,
    ]);

    // Create a role.
    $licensed_role = OgRole::create();
    $licensed_role
      ->setName($this->randomMachineName())
      ->setLabel($this->randomString())
      ->setGroupType('entity_test')
      ->setGroupBundle($this->groupBundle)
      ->save();

    // Create a license which doesn't have any type-specific field values set.
    $license = $this->licenseStorage->create([
      'type' => 'commerce_license_og_role',
      'state' => 'new',
      'product' => 1,
      'uid' => $license_owner->id(),
      'expiration_type' => [
        'target_plugin_id' => 'unlimited',
        'target_plugin_configuration' => [],
      ],
    ]);
    $license->save();

    // Create a configured OG role license plugin.
    $plugin_configuration = [
      'license_og_group' => [
        'target_type' => $this->group_licensed->getEntityTypeId(),
        'target_id' => $this->group_licensed->id(),
      ],
      'license_og_role' => $licensed_role->id(),
      'license_og_membership_type' => $membership_type_id,
    ];
    $license_type_plugin = $this->licenseTypeManager->createInstance('commerce_license_og_role', $plugin_configuration);

    // Set the license's type-specific fields from the configured plugin.
    $license->setValuesFromPlugin($license_type_plugin);

    $license->save();
    $license = $this->reloadEntity($license);

    $this->assertEquals($this->group_licensed->getEntityTypeId(), $license->license_og_group->target_type, "The group type was set on the license.");
    $this->assertEquals($this->group_licensed->id(), $license->license_og_group->target_id, "The group ID was set on the license.");
    $this->assertEquals($licensed_role->id(), $license->license_og_role->target_id, "The role field was set on the license.");
    $this->assertEquals($membership_type_id, $license->license_og_membership_type->value, "The membership type field was set on the license.");
  }

  /**
   * Asserts that a membership has a role.
   *
   * @param string $role_name
   *   The ID of the role to check for.
   * @param OgMembershipInterface $membership
   *   The OG membership entity.
   * @param string $message
   *   The message to output for the assertion.
   */
  protected function assertMembershipHasRole($role_name, OgMembershipInterface $membership, $message = NULL) {
    $message = $message ?: "The membership has the $role_name role.";

    foreach ($membership->getRoles() as $role) {
      if ($role->id() === $role_name) {
        // TODO: is there a cleaner way to pass the test with a message?
        $this->assertTrue(TRUE, $message);
        // Assertion has passed: done.
        return;
      }
    }

    // Still here means no role found: fail.
    $this->fail($message);
  }

  /**
   * Asserts that a membership does not have a role.
   *
   * @param string $role_name
   *   The ID of the role to check isn't there.
   * @param OgMembershipInterface $membership
   *   The OG membership entity.
   * @param string $message
   *   The message to output for the assertion.
   */
  protected function assertMembershipHasNotRole($role_name, OgMembershipInterface $membership, $message = NULL) {
    $message = $message ?: "The membership does not have the $role_name role.";

    foreach ($membership->getRoles() as $role) {
      if ($role->id() === $role_name) {
        $this->fail($message);
      }
    }

    // Still here means no role found: pass.
    $this->assertTrue(TRUE, $message);
  }

}
