<?php

namespace Drupal\Tests\owntracks\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\owntracks\Entity\OwnTracksTransition;

/**
 * @coversDefaultClass \Drupal\owntracks\Entity\OwnTracksTransition
 * @group owntracks
 */
class OwnTracksTransitionTest extends EntityKernelTestBase {

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
    'wtst'        => 0,
    'acc'         => -1,
    'description' => 'valid',
    'event'       => 'invalid',
    'lat'         => -90.1,
    'lon'         => 180.1,
    't'           => 'invalid',
    'tid'         => 'valid',
    'tst'         => 0,
  ];

  /**
   * Sample valid data.
   *
   * @var array
   */
  public static $sampleValidData = [
    'wtst'        => 23456,
    'acc'         => 0,
    'description' => 'valid',
    'event'       => 'enter',
    'lat'         => -90,
    'lon'         => 180,
    't'           => 'c',
    'tid'         => 'bo',
    'tst'         => 123456,
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('owntracks_transition');
  }

  /**
   * Tests the owntracks transition validation.
   */
  public function testValidation() {
    $entity = OwnTracksTransition::create(static::$sampleInvalidData);
    $violations = $entity->validate();
    $this->assertEquals(6, $violations->count());
  }

  /**
   * Tests the owntracks transition storage.
   */
  public function testStorage() {
    $entity = OwnTracksTransition::create(static::$sampleValidData);
    $violations = $entity->validate();
    $this->assertEquals(0, $violations->count());
    $entity->save();
    $this->assertEquals([-90, 180], $entity->getLocation());
  }

  /**
   * Tests the owntracks transition storage.
   */
  public function testEntityStorageException() {
    $this->setExpectedException('\Drupal\Core\Entity\EntityStorageException');
    OwnTracksTransition::create(static::$sampleInvalidData)->save();
  }

}
