<?php

namespace Drupal\Tests\release_tracker\Kernel;

use Drupal\KernelTests\KernelTestBase;
use InvalidArgumentException;

/**
 * Tests the release tracker service.
 *
 * @coversDefaultClass \Drupal\release_tracker\ReleaseTracker
 *
 * @group release_tracker
 */
class ReleaseTrackerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['release_tracker', 'system'];

  /**
   * The release tracker service.
   *
   * @var \Drupal\release_tracker\ReleaseTrackerInterface
   */
  protected $releaseTracker;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig('release_tracker');
    $this->releaseTracker = $this->container->get('release_tracker.release_tracker');
  }

  /**
   * Tests the bump method.
   *
   * @covers ::bump
   *
   * @dataProvider bumpProvider
   */
  public function testBump($type, $expected_value, $initial) {
    $this->releaseTracker->bump($type);
    $this->assertEquals($expected_value, $this->releaseTracker->getCurrentRelease());
  }

  /**
   * Data provider for testBump.
   */
  public function bumpProvider() {
    return [
      'patch' => ['patch', '1.0.1', FALSE],
      'minor' => ['minor', '1.1.0', FALSE],
      'major' => ['major', '2.0.0', FALSE],
    ];
  }

  /**
   * Tests the bump method using the default.
   *
   * @covers ::bump
   */
  public function testDefaultBump() {
    $this->releaseTracker->bump();
    $this->assertEquals('1.0.1', $this->releaseTracker->getCurrentRelease());
  }

  /**
   * Tests bumping using a invalid type.
   *
   * @covers ::bump
   */
  public function testInvalidBumpType() {
    $this->setExpectedException(InvalidArgumentException::class, "Type must be one of 'major', 'minor' or 'patch'.");
    $this->releaseTracker->bump('invalid');
  }

  /**
   * Tests the getCurrentRelease method.
   *
   * @covers ::getCurrentRelease
   */
  public function testGetCurrentRelease() {
    $this->assertEquals('1.0.0', $this->releaseTracker->getCurrentRelease());
  }

  /**
   * Tests the setReleaseNumber method.
   *
   * @covers ::setReleaseNumber
   *
   * @dataProvider setReleaseNumberProvider
   */
  public function testSetReleaseNumber($number, $exception) {
    if ($exception) {
      $this->setExpectedException($exception);
    }
    $this->releaseTracker->setReleaseNumber($number);
    $this->assertEquals($number, $this->releaseTracker->getCurrentRelease());
  }

  /**
   * Data provider for testSetReleaseNumber.
   *
   * @return array
   */
  public function setReleaseNumberProvider() {
    return [
      'valid' => ['18.5.1975', FALSE],
      'invalid string' => ['woot.woot.woot', InvalidArgumentException::class],
      'invalid parts part 1' => ['1', InvalidArgumentException::class],
      'invalid parts part 2' => ['1.2', InvalidArgumentException::class],
      'invalid parts period' => ['1.2.', InvalidArgumentException::class],
      'invalid parts too many' => ['1.2.3.4', InvalidArgumentException::class],
    ];
  }

}
