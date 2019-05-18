<?php

namespace Drupal\Tests\client_config_care\Unit;

use Drupal\client_config_care\Deactivator;
use Drupal\client_config_care\SettingsFactory;
use Drupal\client_config_care\SettingsModel;
use Drupal\Tests\UnitTestCase;

class DeactivatorTest extends UnitTestCase {

  public function setUp()
  {
    parent::setUp();
  }

  public function testIsNotDeactivated(): void {
    $settingsModel = new SettingsModel(FALSE);
    $settingsFactory = $this->createMock(SettingsFactory::class);
    $settingsFactory->method('create')
     ->willReturn($settingsModel);

    $deactivator = new Deactivator($settingsFactory);

    self::assertFalse($deactivator->isDeactivated());
    self::assertTrue($deactivator->isNotDeactivated());

    $settingsModel = new SettingsModel();
    $settingsFactory = $this->createMock(SettingsFactory::class);
    $settingsFactory->method('create')
      ->willReturn($settingsModel);

    $deactivator = new Deactivator($settingsFactory);

    self::assertFalse($deactivator->isDeactivated());
    self::assertTrue($deactivator->isNotDeactivated());
  }

  public function testIsDeactivated(): void {
    $settingsModel = new SettingsModel(TRUE);
    $settingsFactory = $this->createMock(SettingsFactory::class);
    $settingsFactory->method('create')
      ->willReturn($settingsModel);

    $deactivator = new Deactivator($settingsFactory);

    self::assertTrue($deactivator->isDeactivated());
    self::assertFalse($deactivator->isNotDeactivated());
  }

}
