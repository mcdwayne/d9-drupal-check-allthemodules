<?php

namespace Drupal\Tests\client_config_care\Kernel;

use Drupal\client_config_care\ConfigBlockerEntityStorage;
use Drupal\client_config_care\Deactivator;
use Drupal\Core\Config\Config;
use Drupal\Core\Site\Settings;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;


class DeactivatorTest extends EntityKernelTestBase {

  /**
   * @var array
   */
  public static $modules = [
    'config_events_test'
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
  }

  public function testDeactivatedOnFalseSetting(): void {
    new Settings([
      'client_config_care' => [
        'deactivated' => FALSE,
      ],
    ]);

    self::assertFalse(Settings::get('client_config_care')['deactivated']);

    \Drupal::service('module_installer')->install(['client_config_care']);
    $this->installEntitySchema('config_blocker_entity');

    /**
     * @var Deactivator $deactivator
     */
    $deactivator = \Drupal::service('client_config_care.deactivator');

    self::assertFalse($deactivator->isDeactivated());

    $name = 'config_events_test.test';

    $config = new Config($name, \Drupal::service('config.storage'), \Drupal::service('event_dispatcher'), \Drupal::service('config.typed'));
    $config->set('key', 'initial');
    $config->save();

    /**
     * @var ConfigBlockerEntityStorage $configBlockerEntityStorage
     */
    $configBlockerEntityStorage = \Drupal::service('entity_type.manager')->getStorage('config_blocker_entity');

    self::assertTrue($configBlockerEntityStorage->isBlockerExisting($name));
  }

  public function testNoSetting(): void {
    new Settings([]);

    self::assertNull(Settings::get('client_config_care'));

    \Drupal::service('module_installer')->install(['client_config_care']);
    $this->installEntitySchema('config_blocker_entity');

    /**
     * @var Deactivator $deactivator
     */
    $deactivator = \Drupal::service('client_config_care.deactivator');

    self::assertFalse($deactivator->isDeactivated());

    $name = 'config_events_test.test';

    $config = new Config($name, \Drupal::service('config.storage'), \Drupal::service('event_dispatcher'), \Drupal::service('config.typed'));
    $config->set('key', 'initial');
    $config->save();

    /**
     * @var ConfigBlockerEntityStorage $configBlockerEntityStorage
     */
    $configBlockerEntityStorage = \Drupal::service('entity_type.manager')->getStorage('config_blocker_entity');

    self::assertTrue($configBlockerEntityStorage->isBlockerExisting($name));
  }

  public function testDeactivatedOnTrueSetting(): void {
    new Settings([
      'client_config_care' => [
        'deactivated' => TRUE,
      ],
    ]);

    self::assertTrue(Settings::get('client_config_care')['deactivated']);

    \Drupal::service('module_installer')->install(['client_config_care']);
    $this->installEntitySchema('config_blocker_entity');

    /**
     * @var Deactivator $deactivator
     */
    $deactivator = \Drupal::service('client_config_care.deactivator');

    self::assertTrue($deactivator->isDeactivated());

    $name = 'config_events_test.test';

    $config = new Config($name, \Drupal::service('config.storage'), \Drupal::service('event_dispatcher'), \Drupal::service('config.typed'));
    $config->set('key', 'initial');
    $config->save();

    /**
     * @var ConfigBlockerEntityStorage $configBlockerEntityStorage
     */
    $configBlockerEntityStorage = \Drupal::service('entity_type.manager')->getStorage('config_blocker_entity');

    self::assertFalse($configBlockerEntityStorage->isBlockerExisting($name));
  }

}
