<?php

namespace Drupal\Tests\og_sm\Kernel;

use Drupal\Core\Url;
use Drupal\og\Entity\OgRole;
use Drupal\og\Og;
use Drupal\og_sm\OgSm;

/**
 * Tests about the node type settings.
 *
 * @group og_sm
 */
class SiteApiTest extends OgSmKernelTestBase {

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
  public function testSite() {
    $type_group = $this->createGroupNodeType(self::TYPE_IS_GROUP);
    $type_not_group = $this->createNodeType(self::TYPE_DEFAULT);
    $node = $this->createNode(['type' => $type_not_group->id()]);
    $group = $this->createGroup($type_group->id());

    $site_type_manager = OgSm::siteTypeManager();
    $site_manager = OgSm::siteManager();

    // Test og_sm_is_site.
    $this->assertFalse($site_manager->isSite($node));
    $this->assertFalse($site_manager->isSite($group));
    $site_type_manager->setIsSiteType($type_group, TRUE);
    $type_group->save();
    $this->assertTrue($site_manager->isSite($group));

    // Test og_sm_site_load.
    $site = $site_manager->load($group->id());
    $this->assertEquals($group->label(), $site->label());
    $site_type_manager->setIsSiteType($type_group, FALSE);
    $type_group->save();
    $this->assertFalse($site_manager->load($group->id()));

    // Test og_sm_site_load with non-existing node id.
    $this->assertFalse($site_manager->load(9877654321));

    // Test getting all site Nodes ID's.
    $group2 = $this->createGroup($type_group->id());
    $group3 = $this->createGroup($type_group->id());
    $this->assertEquals([], $site_manager->getAllSites());
    $site_type_manager->setIsSiteType($type_group, TRUE);
    $type_group->save();
    $expected = [
      $group->id(),
      $group2->id(),
      $group3->id(),
    ];
    $this->assertEquals($expected, array_keys($site_manager->getAllSites()));
  }

  /**
   * Test filtering an array of groups by only sites.
   */
  public function testFilterSitesFromGroups() {
    $type_is_group = $this->createGroupNodeType(self::TYPE_IS_GROUP);
    $type_not_group = $this->createNodeType(self::TYPE_DEFAULT);
    OgSm::siteTypeManager()->setIsSiteType($type_is_group, TRUE);
    $type_is_group->save();

    $group1 = $this->createGroup($type_is_group->id());
    $group2 = $this->createGroup($type_is_group->id());
    $group3 = $this->createGroup($type_not_group->id());
    $groups = ['node' => [$group3, $group2, $group1]];

    $expected = [$group2->id() => $group2, $group1->id() => $group1];
    $sites = OgSm::siteManager()->filterSitesFromGroups($groups);
    $this->assertEquals($expected, $sites);
  }

  /**
   * Test Site access callbacks.
   */
  public function testSiteAccessCallbacks() {
    $site_type = $this->createGroupNodeType(self::TYPE_IS_GROUP);
    $administer_site_permission = 'administer site';
    OgSm::siteTypeManager()->setIsSiteType($site_type, TRUE);
    $site_type->save();

    // Create the Site.
    $site = $this->createGroup($site_type->id());
    $sites = [$site];

    $og_role = OgRole::create();
    $og_role
      ->setName('role_group_manager')
      ->setLabel($this->randomString())
      ->setGroupType('node')
      ->setGroupBundle($site_type->id())
      ->grantPermission($administer_site_permission)
      ->save();

    // Create users.
    $user1 = $this->createGroupUser([], $sites);
    $user = $this->createUser();
    $site_user = $this->createGroupUser([], $sites);
    $site_manager = $this->createGroupUser([], $sites);
    $membership = Og::getMembership($site, $site_manager);
    $membership->addRole($og_role);

    /* @var \Drupal\og\OgAccessInterface $og_access */
    $og_access = $this->container->get('og.access');
    // Always give access to user 1.
    $this->assertTrue($og_access->userAccess($site, $administer_site_permission, $user1)->isAllowed());

    // No access for non site member.
    $this->assertTrue($og_access->userAccess($site, $administer_site_permission, $user)->isForbidden());

    // No access for site members who has not the proper role(s).
    $this->assertTrue($og_access->userAccess($site, $administer_site_permission, $site_user)->isForbidden());

    // Access for site members with the proper role(s).
    $this->assertTrue($og_access->userAccess($site, $administer_site_permission, $site_manager)->isAllowed());
  }

  /**
   * Test Site homepage path.
   */
  public function testGetHomepagePath() {
    $site_type = $this->createGroupNodeType(self::TYPE_IS_GROUP);
    OgSm::siteTypeManager()->setIsSiteType($site_type, TRUE);
    $site_type->save();
    $site = $this->createGroup($site_type->id());

    /* @var \Drupal\og_sm\SiteManagerInterface $site_manager */
    $site_manager = $this->container->get('og_sm.site_manager');

    // Default when no context is active.
    $this->assertFalse(
      $site_manager->getSiteHomePage(),
      'No path if no Site in current OG context.'
    );

    // Path based on the given Site.
    $expected = '/node/' . $site->id();
    $this->assertEquals(
      $expected,
      $site_manager->getSiteHomePage($site)->toString(),
      'Site homepage is the Site node.'
    );

    $this->setOgContextToGroup($site);
    $site_manager = $this->container->get('og_sm.site_manager');

    // Path from current OG context.
    $this->assertEquals(
      $expected,
      $site_manager->getSiteHomePage()->toString(),
      'Fallback to Site homepage of the active OG context.'
    );

    // Enable module that alters the path.
    $this->enableModules(['og_sm_test']);
    $site_manager = $this->container->get('og_sm.site_manager');
    $this->assertEquals(
      Url::fromUserInput('/group/node/' . $site->id() . '/admin/structure/site-edit')->toString(),
      $site_manager->getSiteHomePage($site)->toString(),
      'Modules can alter the Site path.'
    );
  }

}
