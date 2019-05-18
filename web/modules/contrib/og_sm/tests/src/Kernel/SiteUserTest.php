<?php

namespace Drupal\Tests\og_sm\Kernel;

use Drupal\og_sm\OgSm;

/**
 * Tests Site User and helpers.
 *
 * @group og_sm
 */
class SiteUserTest extends OgSmKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->installEntitySchema('og_membership');
  }

  /**
   * Test Site helpers.
   */
  public function testSiteUser() {
    $type_group = $this->createGroupNodeType(self::TYPE_IS_GROUP);
    $type_group_no_site = $this->createGroupNodeType('not_a_site_type');

    $group1 = $this->createGroup($type_group->id());
    $group2 = $this->createGroup($type_group->id());
    $group3 = $this->createGroup($type_group_no_site->id());
    $groups = [$group3, $group2, $group1];

    // Create group users.
    $user_no_groups = $this->createGroupUser();
    $user_with_groups = $this->createGroupUser([], $groups);

    $siteManager = Ogsm::siteManager();
    // Default no Site groups.
    self::assertEquals([], $siteManager->getUserSites($user_no_groups));
    self::assertEquals([], $siteManager->getUserSites($user_with_groups));

    // Make the group type a Site type.
    OgSm::siteTypeManager()->setIsSiteType($type_group, TRUE);
    $type_group->save();

    // Get all sites a user belongs to.
    self::assertCount(2, $siteManager->getUserSites($user_with_groups));

    // User should be a member of both groups.
    self::assertTrue($siteManager->userIsMemberOfSite($user_with_groups, $group1));
    self::assertTrue($siteManager->userIsMemberOfSite($user_with_groups, $group2));

    // No Site group types are ignored.
    self::assertFalse($siteManager->userIsMemberOfSite($user_with_groups, $group3));
  }

}
