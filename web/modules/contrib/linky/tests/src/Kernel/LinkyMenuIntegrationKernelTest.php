<?php

namespace Drupal\Tests\linky\Kernel;

use Drupal\linky\Entity\Linky;
use Drupal\menu_link_content\Entity\MenuLinkContent;

/**
 * Tests Linky and menu_link_content entity integration.
 *
 * @group linky
 */
class LinkyMenuIntegrationKernelTest extends LinkyKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'linky',
    'link',
    'dynamic_entity_reference',
    'user',
    'menu_link_content',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('menu_link_content');
  }

  /**
   * Tests integration with menu_link_content entities.
   */
  public function testMenuLinkContentIntegration() {
    $link = Linky::create([
      'link' => [
        'uri' => 'http://example.com',
        'title' => 'Example.com',
      ],
    ]);
    $link->save();
    $menu_link = MenuLinkContent::create([
      'title' => 'Test',
      'menu_name' => 'main',
      'bundle' => 'menu_link_content',
      'link' => [['uri' => 'entity:linky/' . $link->id()]],
    ]);
    $menu_link->save();
    // See menu_link_content_entity_predelete().
    $link->delete();
    $this->assertEmpty(MenuLinkContent::load($menu_link->id()));
  }

}
