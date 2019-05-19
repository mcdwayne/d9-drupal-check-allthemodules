<?php

namespace Drupal\Tests\menu_link\Kernel;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\entity_test\Entity\EntityTestMul;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests the menu link field.
 *
 * @group menu_link
 */
class MenuLinkFieldKernelTest extends KernelTestBase {

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

    FieldStorageConfig::create([
      'field_name' => 'field_menu_link2',
      'entity_type' => 'entity_test_mul',
      'type' => 'menu_link',
      'settings' => [
        'menu_link_per_translation' => TRUE,
      ],
    ])->save();

    FieldConfig::create([
      'field_name' => 'field_menu_link2',
      'entity_type' => 'entity_test_mul',
      'bundle' => 'entity_test_mul',
    ])->save();
  }

  protected function enableMultilingual() {
    ConfigurableLanguage::createFromLangcode('es')
      ->save();

    $this->container->get('kernel')->rebuildContainer();
  }

  /**
   * {@inheritdoc}
   */
  public function testMenuTreeSave() {
    $entity_test_mul = EntityTestMul::create([
      'type' => 'entity_test_mul',
      'name' => 'test',
      'field_menu_link' => [
        'menu_name' => 'test_menu',
        'title' => 'test title',
        'description' => 'test description',
      ],
    ]);
    $entity_test_mul->save();

    /** @var \Drupal\Core\Menu\MenuLinkTree $menu_tree */
    $menu_tree = \Drupal::service('menu.link_tree');

    $parameters = new MenuTreeParameters();
    $parameters->addCondition('title', 'test title');
    $result = $menu_tree->load('test_menu', $parameters);
    $this->assertCount(1, $result);

    $menu_link = reset($result);
    $this->assertEquals(1, $menu_link->depth);
    $this->assertFalse($menu_link->hasChildren);
    $this->assertEquals('test title', $menu_link->link->getTitle());
    $this->assertEquals('', $menu_link->link->getParent());
    $this->assertEquals('test description', $menu_link->link->getDescription());
    $this->assertEquals('test_menu', $menu_link->link->getMenuName());
    $this->assertTrue($menu_link->link->getUrlObject()->isRouted());
    $this->assertEquals('entity.entity_test_mul.canonical', $menu_link->link->getUrlObject()->getRouteName());
    $this->assertEquals(['entity_test_mul' => $entity_test_mul->id()], $menu_link->link->getUrlObject()->getRouteParameters());

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

    $parameters = new MenuTreeParameters();
    $parameters->addCondition('title', 'test title 2');
    $result = $menu_tree->load('test_menu', $parameters);
    $this->assertCount(1, $result);

    $menu_link2 = reset($result);
    $this->assertEquals(2, $menu_link2->depth);
    $this->assertFalse($menu_link2->hasChildren);
    $this->assertEquals('test title 2', $menu_link2->link->getTitle());
    $this->assertEquals($menu_link->link->getPluginId(), $menu_link2->link->getParent());
    $this->assertEquals('test description 2', $menu_link2->link->getDescription());
    $this->assertEquals('test_menu', $menu_link2->link->getMenuName());
    $this->assertTrue($menu_link2->link->getUrlObject()->isRouted());
    $this->assertEquals('entity.entity_test_mul.canonical', $menu_link2->link->getUrlObject()->getRouteName());
    $this->assertEquals(['entity_test_mul' => $entity_test_mul2->id()], $menu_link2->link->getUrlObject()->getRouteParameters());
  }

  /**
   * Test the title/description translation.
   */
  public function testTitleAndDescriptionTranslation() {
    $this->enableMultilingual();

    $entity_test_mul = EntityTestMul::create([
      'type' => 'entity_test_mul',
      'name' => 'test',
      'field_menu_link' => [
        'menu_name' => 'test_menu',
        'title' => 'test title EN',
        'description' => 'test description EN',
      ],
    ]);
    $entity_test_mul->save();

    /** @var \Drupal\Core\Menu\MenuLinkTree $menu_tree */
    $menu_tree = \Drupal::service('menu.link_tree');

    $parameters = new MenuTreeParameters();
    $result = $menu_tree->load('test_menu', $parameters);
    $this->assertCount(1, $result);

    $menu_link = reset($result);
    $this->assertEquals(1, $menu_link->depth);
    $this->assertFalse($menu_link->hasChildren);
    $this->assertEquals('test title EN', $menu_link->link->getTitle());
    $this->assertEquals('', $menu_link->link->getParent());
    $this->assertEquals('test description EN', $menu_link->link->getDescription());
    $this->assertEquals('test_menu', $menu_link->link->getMenuName());
    $this->assertTrue($menu_link->link->getUrlObject()->isRouted());
    $this->assertEquals('entity.entity_test_mul.canonical', $menu_link->link->getUrlObject()->getRouteName());
    $this->assertEquals(['entity_test_mul' => $entity_test_mul->id()], $menu_link->link->getUrlObject()->getRouteParameters());

    $entity_test_mul_es = $entity_test_mul->addTranslation('es');
    $entity_test_mul_es->set('field_menu_link', [
      'menu_name' => 'test_menu',
      'title' => 'test title ES',
      'description' => 'test description ES',
    ]);
    $entity_test_mul_es->save();

    // Load the default language aka. EN.

    $parameters = new MenuTreeParameters();
    $result = $menu_tree->load('test_menu', $parameters);
    $this->assertCount(1, $result);

    $menu_link = reset($result);
    $this->assertEquals(1, $menu_link->depth);
    $this->assertFalse($menu_link->hasChildren);
    $this->assertEquals('test title EN', $menu_link->link->getTitle());
    $this->assertEquals('', $menu_link->link->getParent());
    $this->assertEquals('test description EN', $menu_link->link->getDescription());
    $this->assertEquals('test_menu', $menu_link->link->getMenuName());
    $this->assertTrue($menu_link->link->getUrlObject()->isRouted());
    $this->assertEquals('entity.entity_test_mul.canonical', $menu_link->link->getUrlObject()->getRouteName());
    $this->assertEquals(['entity_test_mul' => $entity_test_mul->id()], $menu_link->link->getUrlObject()->getRouteParameters());
    $this->assertEquals('test description EN', $menu_link->link->getUrlObject()->getOptions()['attributes']['title']);

    // Load the ES version.
    $reflection = new \ReflectionClass(\Drupal::languageManager());
    $property = $reflection->getProperty('negotiatedLanguages');
    $property->setAccessible(TRUE);
    $property->setValue(\Drupal::languageManager(), [LanguageInterface::TYPE_CONTENT => ConfigurableLanguage::load('es')]);
    $this->assertEquals('es', \Drupal::languageManager()->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId());

    $parameters = new MenuTreeParameters();
    $result = $menu_tree->load('test_menu', $parameters);
    $this->assertCount(1, $result);

    $menu_link_es = reset($result);
    $this->assertEquals('test title ES', $menu_link_es->link->getTitle());
    $this->assertEquals('', $menu_link_es->link->getParent());
    $this->assertEquals('test description ES', $menu_link_es->link->getDescription());
    $this->assertEquals('test_menu', $menu_link_es->link->getMenuName());
    $this->assertTrue($menu_link_es->link->getUrlObject()->isRouted());
    $this->assertEquals('entity.entity_test_mul.canonical', $menu_link_es->link->getUrlObject()->getRouteName());
    $this->assertEquals(['entity_test_mul' => $entity_test_mul->id()], $menu_link_es->link->getUrlObject()->getRouteParameters());
    $this->assertEquals('test description ES', $menu_link_es->link->getUrlObject()->getOptions()['attributes']['title']);
    $this->assertEquals('es', $menu_link_es->link->getUrlObject()->getOptions()['language']->getId());
  }

  /**
   * Tests the capability to provide multiple menu links and translate them.
   */
  public function testTranslationWithMultipleMenuItems() {
    $this->enableMultilingual();

    $entity_test_mul = EntityTestMul::create([
      'type' => 'entity_test_mul',
      'name' => 'test',
      'field_menu_link2' => [
        'menu_name' => 'test_menu',
        'title' => 'test title EN',
        'description' => 'test description EN',
      ],
    ]);
    $entity_test_mul->save();

    $entity_test_mul_es = $entity_test_mul->addTranslation('es');
    $entity_test_mul_es->set('field_menu_link2', [
      'menu_name' => 'test_menu_es',
      'title' => 'test title ES',
      'description' => 'test description ES',
    ]);
    $entity_test_mul_es->save();

    /** @var \Drupal\Core\Menu\MenuLinkTree $menu_tree */
    $menu_tree = \Drupal::service('menu.link_tree');

    $parameters = new MenuTreeParameters();
    $result = $menu_tree->load('test_menu', $parameters);
    $this->assertCount(1, $result);

    $menu_link = reset($result);
    $this->assertEquals(1, $menu_link->depth);
    $this->assertFalse($menu_link->hasChildren);
    $this->assertEquals('test title EN', $menu_link->link->getTitle());
    $this->assertEquals('', $menu_link->link->getParent());
    $this->assertEquals('test description EN', $menu_link->link->getDescription());
    $this->assertEquals('test_menu', $menu_link->link->getMenuName());
    $this->assertTrue($menu_link->link->getUrlObject()->isRouted());
    $this->assertEquals('entity.entity_test_mul.canonical', $menu_link->link->getUrlObject()->getRouteName());
    $this->assertEquals(['entity_test_mul' => $entity_test_mul->id()], $menu_link->link->getUrlObject()->getRouteParameters());
    $this->assertEquals('test description EN', $menu_link->link->getUrlObject()->getOptions()['attributes']['title']);

    $parameters = new MenuTreeParameters();
    $result = $menu_tree->load('test_menu2', $parameters);
    $this->assertCount(1, $result);

    $menu_link_es = reset($result);
    $this->assertEquals('test title ES', $menu_link_es->link->getTitle());
    $this->assertEquals('', $menu_link_es->link->getParent());
    $this->assertEquals('test description ES', $menu_link_es->link->getDescription());
    $this->assertEquals('test_menu', $menu_link_es->link->getMenuName());
    $this->assertTrue($menu_link_es->link->getUrlObject()->isRouted());
    $this->assertEquals('entity.entity_test_mul.canonical', $menu_link_es->link->getUrlObject()->getRouteName());
    $this->assertEquals(['entity_test_mul' => $entity_test_mul->id()], $menu_link_es->link->getUrlObject()->getRouteParameters());
    $this->assertEquals('test description ES', $menu_link_es->link->getUrlObject()->getOptions()['attributes']['title']);
    $this->assertEquals('es', $menu_link_es->link->getUrlObject()->getOptions()['language']->getId());

  }

}
