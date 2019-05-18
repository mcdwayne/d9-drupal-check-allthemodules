<?php

namespace Drupal\Tests\ad_entity\Kernel;

use Drupal\ad_entity\Entity\AdEntityInterface;
use Drupal\ad_entity\TargetingCollection;
use Drupal\ad_entity_test\Plugin\ad_entity\AdType\TestType;
use Drupal\ad_entity_test\Plugin\ad_entity\AdView\TestView;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\Tests\ad_entity\Traits\AdEntityKernelTrait;

/**
 * Tests the ad_entity type.
 *
 * @group ad_entity
 */
class AdEntityTypeTest extends EntityKernelTestBase {

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
   * Test the creation of an ad_entity instance.
   */
  public function testCreation() {
    $ad_entity = $this->createNewAdEntity();
    $this->assertTrue($ad_entity instanceof AdEntityInterface);
  }

  /**
   * Test for assigned plugins to expect.
   */
  public function testPluginsAssigned() {
    $ad_entity = $this->createNewAdEntity();
    $type_plugin = $ad_entity->getTypePlugin();
    $this->assertTrue($type_plugin instanceof TestType);
    $view_plugin = $ad_entity->getViewPlugin();
    $this->assertTrue($view_plugin instanceof TestView);
  }

  /**
   * Test the available context methods.
   */
  public function testContextMethods() {
    $ad_entity = $this->createNewAdEntity();
    $this->assertTrue(is_array($ad_entity->getContextData()));
    $this->assertEmpty($ad_entity->getContextData());
    $this->assertTrue(is_array($ad_entity->getContextDataForPlugin('targeting')));
    $this->assertEmpty($ad_entity->getContextDataForPlugin('targeting'));
    $this->assertTrue($ad_entity->getTargetingFromContextData() instanceof TargetingCollection);
    $this->assertTrue($ad_entity->getTargetingFromContextData()->isEmpty());

    // The returned TargetingCollection should not be able to modify
    // targeting context data of the Advertising entity.
    $collection = $ad_entity->getTargetingFromContextData();
    $this->assertNotSame($collection, $ad_entity->getTargetingFromContextData());
    $collection->add('testkey', 'testval');
    $this->assertTrue($ad_entity->getTargetingFromContextData()->isEmpty());
  }

}
