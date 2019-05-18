<?php

namespace Drupal\Tests\og_sm_menu\Kernel;

use Drupal\og_sm\OgSm;
use Drupal\Tests\og_sm\Kernel\OgSmKernelTestBase;

/**
 * Tests the site menu manager service.
 *
 * @group og_sm
 */
class SiteMenuManagerTest extends OgSmKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'og_menu',
    'og_sm_menu',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->installConfig(['og_menu', 'og_sm_menu']);
    $this->installEntitySchema('ogmenu');
    $this->installEntitySchema('ogmenu_instance');
    $this->config('og_menu.settings')->set('autocreate', TRUE)->save();
  }

  /**
   * Test Site helpers.
   */
  public function testSiteMenuApi() {
    $site_type = $this->createGroupNodeType(OgSmKernelTestBase::TYPE_IS_GROUP);
    OgSm::siteTypeManager()->setIsSiteType($site_type, TRUE);
    $site_type->save();

    $site1 = $this->createGroup($site_type->id());
    $this->setOgContextToGroup($site1);

    /* @var \Drupal\og_sm_menu\SiteMenuManagerInterface $site_menu_manager */
    $site_menu_manager = $this->container->get('og_sm.site_menu_manager');

    $site1_menu = $site_menu_manager->getMenuBySite($site1);
    $this->assertNotEmpty($site1_menu);
    $this->assertCount(1, $site_menu_manager->getAllMenus());

    $this->createGroup($site_type->id());
    $this->assertCount(2, $site_menu_manager->getAllMenus());

    $this->config('og_menu.settings')->set('autocreate', FALSE)->save();
    $site3 = $this->createGroup($site_type->id());
    $this->assertCount(2, $site_menu_manager->getAllMenus());
    $this->assertEmpty($site_menu_manager->getMenuBySite($site3));
    $site_menu_manager->createMenu($site3);
    $this->assertNotEmpty($site_menu_manager->getMenuBySite($site3));

    $this->assertEquals($site1_menu, $site_menu_manager->getCurrentMenu());
  }

}
