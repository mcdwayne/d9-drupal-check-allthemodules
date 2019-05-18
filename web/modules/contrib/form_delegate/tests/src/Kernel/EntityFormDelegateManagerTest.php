<?php

namespace Drupal\Tests\form_delegate\Kernel;

use Drupal\Component\Plugin\PluginBase;
use Drupal\form_delegate\EntityFormDelegateManager;
use Drupal\KernelTests\KernelTestBase;

/**
 * Class EntityFormDelegateManagerTest
 *
 * @package Drupal\Tests\form_delegate\Kernel
 *
 * @group form_delegate
 */
class EntityFormDelegateManagerTest extends KernelTestBase {

  /**
   * The modules that will be enabled during testing.
   *
   * @var array
   */
  public static $modules = [
    'user',
    'node',
    'entity_form_delegate_test',
    'form_delegate'
  ];

  /**
   * The entity form delegate manager service which is used to test the 'getAlters' method.
   *
   * @var  EntityFormDelegateManager
   */
  protected $entityFormDelegateManager;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->installConfig('form_delegate');
    $this->entityFormDelegateManager = $this->container->get('plugin.manager.form_delegate');
  }

  /**
   * Testing the 'getAlters' method of the 'EntityFormDelegateManager' class.
   */
  public function testGetAlters() {
    $alterPlugins = $this->entityFormDelegateManager->getAlters('node', 'test_bundle', 'default', 'test_form_display_mode');

    // Tests if the '$alterPlugins' not empty.
    self::assertNotEmpty($alterPlugins);

    // Tests if the '$alterPlugins' array contains the 'test_entity_form_title_alter' plugin.
    self::assertArrayHasKey('test_entity_form_title_alter', $alterPlugins);

    /** @var PluginBase $alterPlugin */
    $titleAlterPlugin = $alterPlugins['test_entity_form_title_alter'];

    // Tests if the 'getAlters' method gets the proper plugin with the correct definition
    // which should guarantee that the alter will be performed in the correct circumstances.
    self::assertEquals($titleAlterPlugin->getPluginId(), 'test_entity_form_title_alter');
    self::assertEquals($titleAlterPlugin->getPluginDefinition()['bundle'], 'test_bundle');
    self::assertEquals($titleAlterPlugin->getPluginDefinition()['entity'], 'node');
    self::assertEquals($titleAlterPlugin->getPluginDefinition()['display'], 'test_form_display_mode');
    self::assertContains('default', $titleAlterPlugin->getPluginDefinition()['operation']);
    self::assertContains('edit', $titleAlterPlugin->getPluginDefinition()['operation']);

    // Resets the internal pointer of the array to the zero position in case we use PHP 5.6.
    reset($alterPlugins);
    // Tests if the plugins of type 'EntityFormDelegate' are prioritized properly.
    self::assertEquals(current($alterPlugins)->getPluginDefinition()['priority'], 2);
  }

}
