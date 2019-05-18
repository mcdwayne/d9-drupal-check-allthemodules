<?php

namespace Drupal\Tests\client_config_care\Kernel;

use Drupal;
use Drupal\client_config_care\ConfigBlockerEntityStorage;
use Drupal\client_config_care\Entity\ConfigBlockerEntity;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * @group client_config_care
 */
class ConfigBlockerEntityTest extends EntityKernelTestBase {

  /**
   * @var array
   */
  public static $modules = [
  	'client_config_care'
	];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
		$this->installEntitySchema('config_blocker_entity');
  }

  public function testConfigBlockerEntityLoadById(): void {
    $entityPreSaved = ConfigBlockerEntity::create([
      'name'           => 'test',
      'user_operation' => 'save',
    ]);
		self::assertEquals($entityPreSaved->save(), '1');

		$loadedEntity = ConfigBlockerEntity::load('1');
    self::assertEquals('test', $loadedEntity->get('name')->first()->getValue()['value']);
  }

  public function testConfigBlockerEntitySelectByName(): void {
    $entityPreSaved = ConfigBlockerEntity::create([
      'name'           => 'test',
      'user_operation' => 'save',
    ]);
    self::assertEquals($entityPreSaved->save(), '1');

    /**
     * @var EntityTypeManagerInterface $entityTypeManager
     */
    $entityTypeManager = Drupal::service('entity_type.manager');
    $configBlockerEntityStorage = $entityTypeManager->getStorage('config_blocker_entity');
    $configBlockerEntities = $configBlockerEntityStorage->loadByProperties(['name' => 'test']);

    self::assertEquals(TRUE, reset($configBlockerEntities) instanceof ConfigBlockerEntity);
    self::assertCount(1, $configBlockerEntities);
    self::assertEquals('1', reset($configBlockerEntities)->id());
  }

  public function testConfigBlockerEntitySelectAll(): void {
    $entityPreSaved = ConfigBlockerEntity::create([
      'name'           => 'test',
      'user_operation' => 'save',
    ]);
    self::assertEquals($entityPreSaved->save(), '1');
    $entityPreSaved = ConfigBlockerEntity::create([
      'name'           => 'test2',
      'user_operation' => 'save',
    ]);
    self::assertEquals($entityPreSaved->save(), '1');
    $entityPreSaved = ConfigBlockerEntity::create([
      'name'           => 'test3',
      'user_operation' => 'save',
    ]);
    self::assertEquals($entityPreSaved->save(), '1');

    /**
     * @var EntityTypeManagerInterface $entityTypeManager
     */
    $entityTypeManager = Drupal::service('entity_type.manager');
    $configBlockerEntityStorage = $entityTypeManager->getStorage('config_blocker_entity');
    $entities = $configBlockerEntityStorage->loadByProperties();

    self::assertCount(3, $entities);
  }

  public function testConfigBlockerEntitiesDeleteAll(): void {
    $entityPreSaved = ConfigBlockerEntity::create([
      'name'           => 'test',
      'user_operation' => 'save',
    ]);
    self::assertEquals($entityPreSaved->save(), '1');
    $entityPreSaved = ConfigBlockerEntity::create([
      'name'           => 'test2',
      'user_operation' => 'save',
    ]);
    self::assertEquals($entityPreSaved->save(), '1');
    $entityPreSaved = ConfigBlockerEntity::create([
      'name'           => 'test3',
      'user_operation' => 'save',
    ]);
    self::assertEquals($entityPreSaved->save(), '1');

    /**
     * @var EntityTypeManagerInterface $entityTypeManager
     */
    $entityTypeManager = Drupal::service('entity_type.manager');
    $configBlockerEntityStorage = $entityTypeManager->getStorage('config_blocker_entity');
    $configBlockerEntityStorage->delete($configBlockerEntityStorage->loadByProperties());

    self::assertCount(0, $configBlockerEntityStorage->loadByProperties());
  }

  public function testNonexistentEntitiesOnInstall(): void {
    /**
     * @var EntityTypeManagerInterface $entityTypeManager
     */
    $entityTypeManager = Drupal::service('entity_type.manager');
    $configBlockerEntityStorage = $entityTypeManager->getStorage('config_blocker_entity');
    $entities = $configBlockerEntityStorage->loadByProperties();

    self::assertCount(0, $entities);
  }

  public function testExistingConfigBlockerStorageMethod(): void {
    ConfigBlockerEntity::create([
      'name'           => 'lorem.ipsum.dolor.sit.amet',
      'user_operation' => 'save',
    ])->save();

    /**
     * @var EntityTypeManagerInterface $entityTypeManager
     */
    $entityTypeManager = Drupal::service('entity_type.manager');
    /** @var ConfigBlockerEntityStorage $configBlockerEntityStorage */
    $configBlockerEntityStorage = $entityTypeManager->getStorage('config_blocker_entity');
    self::assertTrue($configBlockerEntityStorage->isBlockerExisting('lorem.ipsum.dolor.sit.amet'));
    self::assertFalse($configBlockerEntityStorage->isBlockerExisting('not.existing.config.name'));
  }

}
