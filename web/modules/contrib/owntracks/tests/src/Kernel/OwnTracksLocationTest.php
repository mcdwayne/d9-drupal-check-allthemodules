<?php

namespace Drupal\Tests\owntracks\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\owntracks\Entity\OwnTracksLocation;

/**
 * @coversDefaultClass \Drupal\owntracks\Entity\OwnTracksLocation
 * @group owntracks
 */
class OwnTracksLocationTest extends EntityKernelTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['owntracks', 'options'];

  /**
   * Sample invalid data.
   *
   * @var array
   */
  public static $sampleInvalidData = [
    'uid'         => -1,
    'acc'         => -1,
    'alt'         => 'invalid',
    'batt'        => 101,
    'cog'         => 361,
    'description' => 'valid',
    'event'       => 'invalid',
    'lat'         => -90.1,
    'lon'         => 180.1,
    'rad'         => -1,
    't'           => 'invalid',
    'tid'         => 'valid',
    'tst'         => 0,
    'vac'         => -1,
    'vel'         => -1,
    'p'           => -1,
    'con'         => 'invalid',
  ];

  /**
   * Sample valid data.
   *
   * @var array
   */
  public static $sampleValidData = [
    'acc'         => 0,
    'alt'         => 0,
    'batt'        => 100,
    'cog'         => 360,
    'description' => 'valid',
    'event'       => 'enter',
    'lat'         => -90,
    'lon'         => 180,
    'rad'         => 0,
    't'           => 'u',
    'tid'         => 'bo',
    'tst'         => 123456,
    'vac'         => 0,
    'vel'         => 0,
    'p'           => 0,
    'con'         => 'm',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('owntracks_location');
  }

  /**
   * Tests the owntracks location validation.
   */
  public function testValidation() {
    $entity = OwnTracksLocation::create(static::$sampleInvalidData);
    $violations = $entity->validate();
    $this->assertEquals(14, $violations->count());
  }

  /**
   * Tests the owntracks location storage.
   */
  public function testStorage() {
    $entity = OwnTracksLocation::create(static::$sampleValidData);
    $violations = $entity->validate();
    $this->assertEquals(0, $violations->count());
    $entity->save();
    $this->assertEquals([-90, 180], $entity->getLocation());
  }

  /**
   * Tests the owntracks location storage.
   */
  public function testEntityStorageException() {
    $this->setExpectedException('\Drupal\Core\Entity\EntityStorageException');
    OwnTracksLocation::create(static::$sampleInvalidData)->save();
  }

}
