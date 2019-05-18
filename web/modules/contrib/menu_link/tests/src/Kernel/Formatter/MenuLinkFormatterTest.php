<?php

namespace Drupal\Tests\menu_link\Kernel\Formatter;

use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\entity_test\Entity\EntityTestMul;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the menu link field formatters.
 *
 * @group menu_link
 */
class MenuLinkFormatterTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['menu_link', 'entity_test', 'field', 'user', 'system', 'language'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('entity_test_mul');

    FieldStorageConfig::create([
      'field_name' => 'field_menu_link',
      'entity_type' => 'entity_test_mul',
      'type' => 'menu_link',
    ])->save();

    FieldConfig::create([
      'field_name' => 'field_menu_link',
      'entity_type' => 'entity_test_mul',
      'bundle' => 'entity_test_mul',
    ])->save();

  }

  /**
   * Tests the menu_link and menu_link_breadcrumb field formatters.
   */
  public function testMenuLinkFormatters() {
    $entity_test_mul = EntityTestMul::create([
      'type' => 'entity_test_mul',
      'name' => 'test',
      'field_menu_link' => [
        'menu_name' => 'test_menu',
        'title' => 'test title 1',
        'description' => 'test description',
      ],
    ]);

    /** @var \Drupal\Core\Menu\MenuLinkTree $menu_tree */
    $menu_tree = \Drupal::service('menu.link_tree');
    $parameters = new MenuTreeParameters();
    $parameters->addCondition('title', 'test title 1');
    $entity_test_mul->save();
    $result = $menu_tree->load('test_menu', $parameters);
    $menu_link = reset($result);
    // Add another entity as a child of the first one.
    $entity_test_mul2 = EntityTestMul::create([
      'type' => 'entity_test_mul',
      'name' => 'test',
      'field_menu_link' => [
        'menu_name' => 'test_menu',
        'parent' => $menu_link->link->getPluginId(),
        'title' => 'test title 2',
        'description' => 'test description 2',
      ],
    ]);
    $entity_test_mul2->save();

    // Test that the menu_link formatter outputs plain text.
    $content = $this->renderLink($entity_test_mul2, 'menu_link', ['link_to_target' => FALSE]);
    $this->assertRaw('<div>test title 2</div>', $content);

    // Test that the menu_link formatter outputs a link.
    $content2 = $this->renderLink($entity_test_mul2, 'menu_link', ['link_to_target' => TRUE]);
    $this->assertRaw('<div><a href="/entity_test_mul/manage/2" title="test description 2" hreflang="en">test title 2</a></div>', $content2);

    // Test that the breadcrumb formatter outputs links.
    $content3 = $this->renderLink($entity_test_mul2, 'menu_link_breadcrumb', ['link_to_target' => TRUE]);
    $this->assertRaw('<a href="/entity_test_mul/manage/1">test title 1</a>', $content3);
    $this->assertRaw('<a href="/entity_test_mul/manage/2">test title 2</a>', $content3);

    // Test that the breadcrumb formatter outputs plain text.
    $content4 = $this->renderLink($entity_test_mul2, 'menu_link_breadcrumb', ['link_to_target' => FALSE]);
    $this->assertRaw('test title 1', $content4);
    $this->assertRaw('test title 2', $content4);
    $this->assertNoRaw('<a href="/entity_test_mul/manage/1">test title 1</a>', $content3);
    $this->assertNoRaw('<a href="/entity_test_mul/manage/2">test title 2</a>', $content3);

    // Test that we skip the last element when parents_only is enabled.
    $content5 = $this->renderLink($entity_test_mul2, 'menu_link_breadcrumb', ['link_to_target' => FALSE, 'parents_only' => TRUE]);
    $this->assertRaw('test title 1', $content5);
    $this->assertNoRaw('test title 2', $content5);
  }

  /**
   * Renders a menu link field.
   *
   * @param $entity
   *   The entity to render the link for.
   * @param string $type
   *   The ID of the field formatter.
   * @param string $settings
   *   The field formatter settings.
   *
   * @return string
   *   The rendered output.
   */
  protected function renderLink($entity, $type, $settings) {
    $display = entity_get_display('entity_test_mul', 'entity_test_mul', 'default')
      ->setComponent('field_menu_link', [
        'type' => $type,
        'settings' => $settings,
      ]);
    $content = $display->build($entity);
    return $this->render($content);
  }

}
