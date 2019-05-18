<?php

namespace Drupal\Tests\client_config_care\Kernel;

use Drupal;
use Drupal\client_config_care\ConfigBlockerEntityStorage;
use Drupal\client_config_care\Entity\ConfigBlockerEntity;
use Drupal\client_config_care\Fixture\EntityCreator;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * @group client_config_care
 */
class EntityCreatorTest extends EntityKernelTestBase {

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
    $entityCreator = new EntityCreator();

    $entityCreator->createEntities();

    self::assertCount(5, ConfigBlockerEntity::loadMultiple());
  }

}
