<?php

namespace Drupal\Tests\revealjs_node\Functional;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\revealjs_node\Helper;
use Drupal\Tests\BrowserTestBase;

/**
 * Simple test to ensure that main page loads with module enabled.
 *
 * @group revealjs_node
 */
class LoadTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['revealjs_node', 'block'];

  /**
   * List of neccessary modules.
   *
   * @var array
   */
  private static $neededModules = [
    'revealjs',
    'revealjs_node',
    'node',
    'ckeditor',
    'block',
    'options',
  ];

  /**
   * A user with permission to administer site configuration.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * Tests whether all modules, themes and content-types are installed.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function testInstallation() {

    /** @var \Drupal\Core\Extension\ModuleHandler $moduleHandler */
    $moduleHandler = \Drupal::service('module_handler');

    /** @var \Drupal\Core\Extension\Extension $module */
    foreach (self::$neededModules as $moduleName) {
      try {
        $module = $moduleHandler->getModule($moduleName);
        $this->assertInstanceOf('\Drupal\Core\Extension\Extension', $module);
      }
      catch (\Exception $ex) {
        $this->fail(t('The module @module is not installed', ['@module' => $moduleName]));
      }
    }

    /** @var \Drupal\Core\Extension\ThemeHandler $themeHandler */
    $themeHandler = \Drupal::service('theme_handler');
    try {
      $theme = $themeHandler->getTheme('revealjs_theme');
      $this->assertInstanceOf('\Drupal\Core\Extension\Extension', $theme);
    }
    catch (\Exception $ex) {
      $this->fail('The Theme revealjs_theme was not loaded');
    }
    $entityType = \Drupal::entityTypeManager()->getDefinition('node');
    foreach (['presentation_theme', 'field_section'] as $fieldName) {
      $storageConfig = FieldStorageConfig::loadByName($entityType->id(), $fieldName);
      $this->assertInstanceOf('\Drupal\field\Entity\FieldStorageConfig', $storageConfig);
    }
    $bundleConfig = $entityType->getBundleConfigDependency(Helper::BUNDLE);
    $this->assertArrayHasKey('type', $bundleConfig);
    $this->assertArrayHasKey('name', $bundleConfig);
    $this->assertEquals('node.type.reveal_js_presentation', $bundleConfig['name']);
  }

  /**
   * Creates a "Reveal JS Presentation" node.
   */
  public function testNodeCreation() {
    $settings =
      [
        'type' => Helper::BUNDLE,
        'title' => $this->randomMachineName(8),
        'field_section' => [
          'value' => '<h1>test</h1>',
          'format' => Helper::BUNDLE,
        ],
        'presentation_theme' => ['value' => 'beige'],
        'uid' => \Drupal::currentUser()->id(),
      ];
    $node = Node::create($settings);
    $node->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
  }

}
