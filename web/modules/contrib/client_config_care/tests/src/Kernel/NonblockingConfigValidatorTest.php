<?php

namespace Drupal\Tests\client_config_care\Kernel;

use Drupal;
use Drupal\client_config_care\Entity\ConfigBlockerEntity;
use Drupal\client_config_care\Validator\NonblockingConfigValidator;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * @group client_config_care
 */
class NonblockingConfigValidatorTest extends EntityKernelTestBase
{

  /**
   * @var array
   */
  public static $modules = [
    'client_config_care'
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp()
  {
    parent::setUp();
    $this->installEntitySchema('config_blocker_entity');
  }

  /**
   * @expectedException Drupal\client_config_care\Exception\ExistingConfigBlockerException
   */
  public function testEnsureNonblockingConfig(): void {
    ConfigBlockerEntity::create([
      'name'           => 'lorem.ipsum.dolor.sit.amet',
      'user_operation' => 'save',
    ])->save();

    /**
     * @var NonblockingConfigValidator $nonblockingValidator
     */
    $nonblockingConfigValidator = \Drupal::service('client_config_care.nonblocking_validator');
    $nonblockingConfigValidator->ensureNonblocking('lorem.ipsum.dolor.sit.amet');
  }

}
