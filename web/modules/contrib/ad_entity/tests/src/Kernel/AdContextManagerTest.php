<?php

namespace Drupal\Tests\ad_entity\Kernel;

use Drupal\ad_entity\Plugin\ad_entity\AdContext\TargetingContext;
use Drupal\ad_entity\Plugin\ad_entity\AdContext\TurnoffContext;
use Drupal\ad_entity\Plugin\AdContextManager;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\Tests\ad_entity\Traits\AdEntityKernelTrait;

/**
 * Tests Advertising context.
 *
 * @coversDefaultClass \Drupal\ad_entity\Plugin\AdContextManager
 * @group ad_entity
 */
class AdContextManagerTest extends EntityKernelTestBase {

  use AdEntityKernelTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'ad_entity',
    'ad_entity_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['ad_entity']);
  }

  /**
   * Test the service instantiation.
   */
  public function testServiceCreation() {
    $manager = $this->getContextManager();
    $this->assertTrue($manager instanceof AdContextManager);
  }

  /**
   * Test the default plugins to expect.
   *
   * @covers ::loadContextPlugin
   */
  public function testDefaultPluginCreation() {
    $manager = $this->getContextManager();
    $targeting_plugin = $manager->loadContextPlugin('targeting');
    $turnoff_plugin = $manager->loadContextPlugin('turnoff');
    $this->assertTrue($targeting_plugin instanceof TargetingContext);
    $this->assertTrue($turnoff_plugin instanceof TurnoffContext);
  }

  /**
   * Test the handling of backend context data.
   *
   * @covers ::getContextData
   * @covers ::getContextDataForPlugin
   * @covers ::resetToPreviousContextData
   */
  public function testContextData() {
    $manager = $this->getContextManager();
    $this->assertEmpty($manager->getContextData());
    $this->assertEmpty($manager->getContextDataForPlugin('targeting'));
    $this->assertEmpty($manager->getContextDataForPlugin('turnoff'));

    $data = ['targeting' => ['testkey' => 'testval']];
    $manager->addContextData('targeting', $data);
    $this->assertNotEmpty($manager->getContextDataForPlugin('targeting'));
    $context_data = $manager->getContextDataForPlugin('targeting');
    $context_data_item = reset($context_data);
    $this->assertArrayHasKey('settings', $context_data_item);
    $this->assertArrayHasKey('apply_on', $context_data_item);
    if (isset($context_data_item['settings'])) {
      $this->assertArrayHasKey('targeting', $context_data_item['settings']);
      if (isset($context_data_item['settings']['targeting'])) {
        $this->assertArrayHasKey('testkey', $context_data_item['settings']['targeting']);
        if (isset($context_data_item['settings']['targeting']['testkey'])) {
          $this->assertEquals('testval', $context_data_item['settings']['targeting']['testkey']);
        }
      }
    }

    $ad_entity = $this->createNewAdEntity();
    $this->assertEquals('testval', $ad_entity->getTargetingFromContextData()->get('testkey'));

    $manager->resetToPreviousContextData();
    $this->assertEmpty($manager->getContextData());
  }

}
