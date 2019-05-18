<?php

namespace Drupal\Tests\owntracks\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\owntracks\Entity\OwnTracksWaypoint;

/**
 * @coversDefaultClass \Drupal\owntracks\Entity\OwnTracksWaypoint
 * @group owntracks
 */
class OwnTracksWaypointTest extends EntityKernelTestBase {

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
    'description' => 'valid',
    'lat'         => -90.1,
    'lon'         => 180.1,
    'rad'         => -1,
    'tst'         => 0,
  ];

  /**
   * Sample valid data.
   *
   * @var array
   */
  public static $sampleValidData = [
    'description' => 'valid',
    'lat'         => -90,
    'lon'         => 180,
    'rad'         => 1,
    'tst'         => 123456,
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('owntracks_waypoint');
  }

  /**
   * Tests the owntracks waypoint validation.
   */
  public function testValidation() {
    $entity = OwnTracksWaypoint::create(static::$sampleInvalidData);
    $violations = $entity->validate();
    $this->assertEquals(4, $violations->count());
  }

  /**
   * Tests the owntracks waypoint storage.
   */
  public function testStorage() {
    $entity = OwnTracksWaypoint::create(static::$sampleValidData);
    $violations = $entity->validate();
    $this->assertEquals(0, $violations->count());
    $entity->save();
    $this->assertEquals([-90, 180], $entity->getLocation());
  }

  /**
   * Tests the owntracks waypoint storage.
   */
  public function testEntityStorageException() {
    $this->setExpectedException('\Drupal\Core\Entity\EntityStorageException');
    OwnTracksWaypoint::create(static::$sampleInvalidData)->save();
  }

}
