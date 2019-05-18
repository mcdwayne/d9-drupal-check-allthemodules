<?php

namespace Drupal\Tests\config_selector\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the Active service.
 *
 * @group config_selector
 *
 * @coversDefaultClass \Drupal\config_selector\ActiveEntity
 */
class ActiveEntityTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'config_test',
    'config_selector',
    'config_selector_test_one',
  ];

  /**
   * The config_test entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $testEntityStorage;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->testEntityStorage = $this->container->get('entity_type.manager')->getStorage('config_test');
  }

  /**
   * @covers ::get
   */
  public function testGet() {
    /** @var \Drupal\config_selector\ActiveEntity $service */
    $service = $this->container->get('config_selector.active');
    /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $entity */
    $entity = $this->testEntityStorage->create(['id' => 'test_1']);
    $entity->setThirdPartySetting('config_selector', 'feature', 'test');
    $entity->save();

    $this->assertSame('test_1', $service->get('config_test', 'test')->id());

    // Disable the entity meaning getIdFromFeature() cannot return an ID.
    $entity->setStatus(FALSE)->save();
    $this->assertNull($service->get('config_test', 'test'));

    // Create another entity in a different feature.
    $entity2 = $this->testEntityStorage->create(['id' => 'test_2']);
    $entity2->setThirdPartySetting('config_selector', 'feature', 'test_two');
    $entity2->save();

    $this->assertNull($service->get('config_test', 'test'));
    $this->assertSame('test_2', $service->get('config_test', 'test_two')->id());

    // Move entity 2 to the same feature.
    $entity2->setThirdPartySetting('config_selector', 'feature', 'test')->save();
    $this->assertSame('test_2', $service->get('config_test', 'test')->id());
    $this->assertNull($service->get('config_test', 'test_two'));

    // Test priority sorting when there is more than one active.
    $entity
      ->setStatus(TRUE)
      ->setThirdPartySetting('config_selector', 'priority', 1)
      ->save();
    $entity2->setThirdPartySetting('config_selector', 'priority', 0)->save();
    $this->assertSame('test_1', $service->get('config_test', 'test')->id());
    $entity2->setThirdPartySetting('config_selector', 'priority', 2)->save();
    $this->assertSame('test_2', $service->get('config_test', 'test')->id());
  }

  /**
   * @covers ::getFromEntity
   */
  public function testGetFromEntity() {
    /** @var \Drupal\config_selector\ActiveEntity $service */
    $service = $this->container->get('config_selector.active');
    /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $entity */
    $entity = $this->testEntityStorage->create(['id' => 'test_1']);
    $entity->setThirdPartySetting('config_selector', 'feature', 'test');
    $entity->save();

    $this->assertSame('test_1', $service->getFromEntity($entity)->id());

    // Disable the entity because it is the only one we will return the same ID.
    $entity->setStatus(FALSE)->save();
    $this->assertSame('test_1', $service->getFromEntity($entity)->id());

    // Create another entity.
    $entity2 = $this->testEntityStorage->create(['id' => 'test_2']);
    $entity2->save();

    // test_2 is not in feature so should return itself.
    $this->assertSame('test_1', $service->getFromEntity($entity)->id());
    $this->assertSame('test_2', $service->getFromEntity($entity2)->id());

    // Move test_2 into it's own feature.
    $entity2->setThirdPartySetting('config_selector', 'feature', 'test_two');
    // Still get test_1 for the test feature because test_2 is in another
    // feature.
    $this->assertSame('test_1', $service->getFromEntity($entity)->id());
    $this->assertSame('test_2', $service->getFromEntity($entity2)->id());

    // Move entity 2 to the same feature.
    $entity2->setThirdPartySetting('config_selector', 'feature', 'test')->save();
    $this->assertSame('test_2', $service->getFromEntity($entity)->id());
    $this->assertSame('test_2', $service->getFromEntity($entity2)->id());
  }

}
