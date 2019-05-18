<?php

namespace Drupal\Tests\og_sm_admin_menu\Functional;

use Drupal\og_sm\OgSm;
use Drupal\Tests\og_sm\Functional\OgSmWebTestBase;

/**
 * Tests the Site Administration Menu functionality.
 *
 * @group og_sm
 */
class AdminMenuTest extends OgSmWebTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'og_sm_admin_menu',
    'views',
  ];

  /**
   * Test the site administration menu.
   */
  public function testSiteAdminMenu() {
    $site_type_manager = OgSm::siteTypeManager();
    $site_manager = OgSm::siteManager();

    $site_type = $this->createGroupNodeType('site');
    $site_type_manager->setIsSiteType($site_type, TRUE);
    $site_type->save();
    $site = $this->createGroup($site_type->id());

    // Login as global admin.
    $admin = $this->createUser([], NULL, TRUE);
    $this->drupalLogin($admin);

    // Verify that the structure link is not available outside the site context.
    $this->drupalGet('<front>');
    $this->assertSession()->linkByHrefNotExists("/group/node/{$site->id()}/admin/structure");

    // Verify that a global admin can see the site's structure page and the
    // members page.
    $this->drupalGet($site_manager->getSiteHomePage($site));
    $this->assertSession()->linkByHrefExists("/group/node/{$site->id()}/admin/structure");
    $this->assertSession()->linkByHrefExists("/group/node/{$site->id()}/admin/members");

    // Login as a site member without any special site permissions.
    $site_member = $this->createGroupUser(['access toolbar'], [$site]);
    $this->drupalLogin($site_member);

    // Verify that he doesn't have enough permissions to see the structure page.
    $this->drupalGet($site_manager->getSiteHomePage($site));
    $this->assertSession()->linkByHrefNotExists("/group/node/{$site->id()}/admin/structure");

    // Login as a site admin.
    $site_admin = $this->createGroupUser(['access toolbar'], [$site], ['administer site']);
    $this->drupalLogin($site_admin);

    // Verify that a site admin can see the site's structure link.
    $this->drupalGet($site_manager->getSiteHomePage($site));
    $this->assertSession()->linkByHrefExists("/group/node/{$site->id()}/admin/structure");

    // Verify that a site admin cannot see the site's structure link for site's
    // he doesn't administer.
    $site2 = $this->createGroup($site_type->id());
    $this->drupalGet($site_manager->getSiteHomePage($site));
    $this->assertSession()->linkByHrefNotExists("/group/node/{$site2->id()}/admin/structure");
  }

  /**
   * Test the og_sm_admin_menu_load_site_switcher() function.
   */
  public function testSiteSwitcher() {
    global $base_path;

    $this->config('system.site')->set('page.front', '/admin/content')->save();

    // Create Sites.
    $site_type_manager = OgSm::siteTypeManager();
    $site_manager = OgSm::siteManager();

    $site_type = $this->createGroupNodeType('site');
    $site_type_manager->setIsSiteType($site_type, TRUE);
    $site_type->save();
    $site1 = $this->createGroup($site_type->id());
    $site2 = $this->createGroup($site_type->id());

    // Create Users.
    $administrator = $this->createUser([], NULL, TRUE);
    $userDefault = $this->drupalCreateUser(['access toolbar']);
    $userSite1Admin = $this->createGroupUser(['access toolbar'], [$site1], ['administer site']);
    $userSite1And2Admin = $this->createGroupUser(['access toolbar'], [$site1, $site2], ['administer site']);

    // As administrator outside Sites.
    $this->drupalLogin($administrator);
    $this->assertSiteSwitcherContains([
      $base_path => 'Platform',
      $base_path . 'node/' . $site1->id() => $site1->label(),
      $base_path . 'node/' . $site2->id() => $site2->label(),
    ], 'Administrator gets menu with all Sites, platform is current item.');

    // As administrator outside Sites.
    $this->drupalLogin($userDefault);
    $this->assertNoSiteSwitcher("Users who can't administer Sites don't have the menu item.");

    // As admin inside a Site.
    $this->drupalLogin($administrator);
    $this->drupalGet($site_manager->getSiteHomePage($site1));
    $this->assertSiteSwitcherContains([
      $base_path . 'node/' . $site1->id() => $site1->label(),
      $base_path . '' => 'Platform',
      $base_path . 'node/' . $site2->id() => $site2->label(),
    ], 'Administrator gets menu with all Sites, Site 1 is current item.');

    // As user 1 within Site 1.
    $this->drupalLogin($userSite1Admin);
    $this->drupalGet($site_manager->getSiteHomePage($site1));
    $this->assertNoSiteSwitcher('User 1 has only 1 Site, no need for Site switcher.');

    // As user 2 within Site 1.
    $this->drupalLogin($userSite1And2Admin);
    $this->drupalGet($site_manager->getSiteHomePage($site1));
    $this->assertSiteSwitcherContains([
      $base_path . 'node/' . $site1->id() => $site1->label(),
      $base_path . 'node/' . $site2->id() => $site2->label(),
    ], 'User 2 gets menu with all the Sites he has access to, Site 1 is current item.');
  }

  /**
   * Asserts that the site switcher contains the expected links.
   *
   * @param array $expected_links
   *   An array of the expected links, keyed with the link's url, value is
   *   the link's label. The first item is the tab's link.
   * @param string $message
   *   Additional information about the test.
   */
  public function assertSiteSwitcherContains(array $expected_links, $message = '') {
    $actual_links = [];

    $tab = $this->cssSelect('#toolbar-item-site-switcher')[0];
    $actual_links[$tab->getAttribute('href')] = $tab->getText();

    $links = $this->cssSelect('#toolbar-item-site-switcher-tray ul.toolbar-menu li a');
    foreach ($links as $link) {
      $actual_links[$link->getAttribute('href')] = $link->getText();
    }

    $this->assertSame($expected_links, $actual_links, $message);
  }

  /**
   * Asserts that there is no site switcher tab.
   *
   * @param string $message
   *   Additional information about the test.
   */
  public function assertNoSiteSwitcher($message = '') {
    $actual = $this->getSession()->getPage()->getContent();
    $regex = '/' . preg_quote('id="toolbar-item-site-switcher"', '/') . '/ui';
    $this->assertFalse(preg_match($regex, $actual), $message);
  }

}
