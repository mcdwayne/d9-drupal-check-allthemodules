<?php

namespace Drupal\Tests\bigmenu\Functional;

use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\system\Entity\Menu;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Big Menu interface.
 *
 * @group bigmenu
 */
class BigMenuUiTest extends BrowserTestBase {

  /**
   * A user with administration rights.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * A test menu.
   *
   * @var \Drupal\system\Entity\Menu
   */
  protected $menu;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'bigmenu',
    'menu_link_content',
    'menu_ui',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser(['access administration pages', 'administer menu']);
    $this->menu = Menu::load('main');
  }

  /**
   * Tests the Big Menu interface.
   */
  public function testBigMenuUi() {
    $this->drupalLogin($this->adminUser);

    // Add new menu items in a hierarchy.
    $item1 = MenuLinkContent::create([
      'title' => 'Item 1',
      'link' => [['uri' => 'internal:/']],
      'menu_name' => 'main',
    ]);
    $item1->save();
    $item1_1 = MenuLinkContent::create([
      'title' => 'Item 1 - 1',
      'link' => [['uri' => 'internal:/']],
      'menu_name' => 'main',
      'parent' => 'menu_link_content:' . $item1->uuid(),
    ]);
    $item1_1->save();
    $item1_1_1 = MenuLinkContent::create([
      'title' => 'Item 1 - 1 - 1',
      'link' => [['uri' => 'internal:/']],
      'menu_name' => 'main',
      'parent' => 'menu_link_content:' . $item1_1->uuid(),
    ]);
    $item1_1_1->save();

    $this->drupalGet('admin/structure/menu/manage/main');
    $this->assertSession()->linkExistsExact('Item 1');
    $this->assertSession()->linkNotExistsExact('Item 1 - 1');
    $this->assertSession()->linkNotExistsExact('Item 1 - 1 - 1');

    $href = $this->menu->toUrl('edit-form', [
      'query' => ['menu_link' => 'menu_link_content:' . $item1->uuid()],
    ])->toString();
    $this->assertSession()->linkByHrefExists($href);

    $this->clickLink('Edit child items');
    $this->assertSession()->linkExistsExact('Item 1');
    $this->assertSession()->linkExistsExact('Item 1 - 1');
    $this->assertSession()->linkNotExistsExact('Item 1 - 1 - 1');

    $this->clickLink('Edit child items');
    $this->assertSession()->linkNotExistsExact('Item 1');
    $this->assertSession()->linkExistsExact('Item 1 - 1');
    $this->assertSession()->linkExistsExact('Item 1 - 1 - 1');
  }

}
