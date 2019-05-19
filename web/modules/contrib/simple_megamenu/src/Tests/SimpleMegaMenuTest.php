<?php

namespace Drupal\simple_megamenu\Tests;

use Drupal\Core\Url;
use Drupal\simple_megamenu\Entity\SimpleMegaMenu;
use Drupal\simpletest\WebTestBase;
use Drupal\menu_link_content\Entity\MenuLinkContent;

/**
 * Simple test to ensure that main page loads with module enabled.
 *
 * @group simple_megamenu
 */
class SimpleMegaMenuTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'language',
    'user',
    'link',
    'file',
    'image',
    'content_translation',
    'text',
    'menu_ui',
    'menu_link_content',
    'field',
    'field_ui',
    'simple_megamenu',
    'simple_megamenu_example',
  ];

  /**
   * A user with permission to administer site configuration.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * A user with permission to view mega menu entities only.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $normalUser;

  /**
   * The profile to install as a basis for testing.
   *
   * @var string
   */
  protected $profile = 'standard';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->normalUser = $this->drupalCreateUser([
      'view published simple mega menu entities',
    ]);

    $this->user = $this->drupalCreateUser([
      'administer site configuration',
      'administer menu',
      'administer blocks',
      'access simple mega menu entities canonical page',
      'view published simple mega menu entities',
      'view unpublished simple mega menu entities',
      'add simple mega menu entities',
      'edit simple mega menu entities',
      'access simple mega menu overview',
    ]);
    $this->drupalLogin($this->user);

    // Set the mmain menu block ti an unlimited level.
    $block_main_menu_path = '/admin/structure/block/manage/bartik_main_menu';
    $edit = [
      'settings[depth]' => '0',
    ];
    $this->drupalPostForm($block_main_menu_path, $edit, t('Save block'));
  }

  /**
   * Creates a menu link given text and path.
   *
   * @param string $text
   *   The menu link text.
   * @param string $path
   *   The menu link path.
   *   Available path : 'route:<front>' or 'internal:/people' or
   *   'entity:node/' . $node->id().
   * @param int $weight
   *   The menu link weight.
   * @param string $menu
   *   The menu to add the link to.
   * @param int $expanded
   *   The menu link is expanded or not if it has children.
   * @param string $parent
   *   The parent menu item uuid to attach the link to.
   * @param string $langcode
   *   The langcode.
   *
   * @return \Drupal\menu_link_content\Entity\MenuLinkContent
   *   The saved menu link.
   */
  public function createMenuLink($text, $path, $weight = 0, $menu = 'main', $expanded = 1, $parent = NULL, $langcode = 'en') {
    /* @var  \Drupal\menu_link_content\Entity\MenuLinkContent $menu_link */
    $menu_link = MenuLinkContent::create([
      'title' => $text,
      'link' => ['uri' => $path],
      'menu_name' => $menu,
      'weight' => $weight,
      'expanded' => $expanded,
      'langcode' => $langcode,
    ]);
    if ($parent !== NULL) {
      $menu_link->set('parent', 'menu_link_content:' . $parent);
    }
    $menu_link->save();
    return $menu_link;
  }

  /**
   * Create a MegaMenu entity.
   *
   * @param string $type
   *   The type.
   * @param string $title
   *   The title.
   * @param string $langcode
   *   The langcode.
   * @param string $uid
   *   The author uid.
   * @param array $fields
   *   Fields to set.
   * @param bool $status
   *   The publishing status.
   *
   * @return \Drupal\simple_megamenu\Entity\SimpleMegaMenuInterface
   *   The mega menu created.
   */
  public function createMegaMenu($type, $title, $langcode, $uid = '1', array $fields = [], $status = TRUE) {
    /* @var  \Drupal\menu_link_content\Entity\MenuLinkContent $menu_link */
    $mega_menu = SimpleMegaMenu::create([
      'type' => $type,
      'name' => $title,
      'uid' => $uid,
      'status' => $status,
      'langcode' => $langcode,
    ]);
    if (empty($fields)) {
      $mega_menu->save();
      return $mega_menu;
    }

    else {
      foreach ($fields as $field_name => $value) {
        if ($mega_menu->hasField($field_name)) {
          $mega_menu->set($field_name, $value);
        }
      }

      $mega_menu->save();
      return $mega_menu;
    }
  }

  /**
   * Tests that the home page loads with a 200 response.
   */
  public function testMegaMenu() {

    $content_path = '/admin/content/simple_mega_menu/add/megamenu';
    // Check that the field appears in the form.
    $this->drupalGet($content_path);
    $this->assertFieldByName('name[0][value]');
    $this->assertFieldByName('field_image_title[0][value]');
    $this->assertFieldByName('field_image_link[0][uri]');
    $this->assertFieldByName('field_text[0][value]');
    $this->assertFieldByName('field_links[0][uri]');
    $this->assertFieldByName('field_links[0][title]');
    $this->assertText(t('The name of the Simple mega menu entity.'));
    $this->assertResponse(200);

    // Create a mage menu entity with some fields filled.
    $fields = [
      'field_text' => [
        'value' => 'CTA text for mega menu',
      ],
      'field_links' => [
        0 => [
          'uri' => 'https://www.flocondetoile.fr',
          'title' => 'See Flocon de toile',
        ],
        1 => [
          'uri' => 'route:<front>',
          'title' => 'Contact us',
        ],
      ],
    ];
    /* @var \Drupal\simple_megamenu\Entity\SimpleMegaMenu $mega_menu */
    $mega_menu = $this->createMegaMenu('megamenu', 'Services', 'en', $this->user->id(), $fields);

    // Create some Menu link content.
    $menu_service = $this->createMenuLink('Services main', 'route:<front>');
    $menu_service_child = $this->createMenuLink('Services child', 'route:<front>', 0, 'main', 1, $menu_service->uuid());

    $menu_path = '/admin/structure/menu/item/';

    $this->drupalGet($menu_path . $menu_service->id() . '/edit');
    $this->assertResponse(200);
    $this->assertFieldByName('simple_mega_menu');

    $edit = [
      'simple_mega_menu' => $mega_menu->label() . ' (' . $mega_menu->id() . ')',
    ];
    $this->drupalPostForm($menu_path . $menu_service->id() . '/edit', $edit, t('Save'));
    $this->assertText(t('The menu link has been saved.'));

    $this->drupalGet(Url::fromRoute('<front>'));
    $this->assertText(t('Services main'));
    // Check the Meega menu is present.
    $this->assertText(t('CTA text for mega menu'));
    $this->assertText(t('See Flocon de toile'));
    $this->assertText(t('Contact us'));

    // Access to the canonical page.
    $this->drupalGet('/admin/content/simple_mega_menu/' . $mega_menu->id());
    $this->assertResponse('200');

    $this->drupalLogout();
    $this->drupalLogin($this->normalUser);

    $this->drupalGet('/admin/content/simple_mega_menu/' . $mega_menu->id());
    $this->assertResponse('404');

    /* @var \Drupal\simple_megamenu\Entity\SimpleMegaMenu $mega_menu */
    $mega_menu2 = $this->createMegaMenu('megamenu', 'Services2', 'en', $this->user->id(), $fields);
    $mega_menu2->delete();

  }

}
