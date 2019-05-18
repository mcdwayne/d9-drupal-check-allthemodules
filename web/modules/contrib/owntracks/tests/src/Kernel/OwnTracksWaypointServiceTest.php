<?php

namespace Drupal\Tests\owntracks\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\owntracks\Entity\OwnTracksWaypoint;

/**
 * Class OwnTracksWaypointServiceTest.
 *
 * @covers \Drupal\owntracks\OwnTracksWaypointService
 *
 * @group owntracks
 */
class OwnTracksWaypointServiceTest extends EntityKernelTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['owntracks'];

  /**
   * The OwnTracks Waypoint service.
   *
   * @var \Drupal\owntracks\OwnTracksWaypointService
   */
  protected $ownTracksWaypointService;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('owntracks_waypoint');
    $this->ownTracksWaypointService = $this->container
      ->get('owntracks.waypoint_service');
  }

  /**
   * Tests the owntracks waypoint service.
   */
  public function testWaypointService() {
    $uid = $this->createUser([], [
      'create owntracks entities',
      'view own owntracks entities',
    ])->id();
    $timestamp = 1349905800;
    $waypoint = OwnTracksWaypoint::create([
      'uid' => $uid,
      'description' => 'Office',
      'lat' => 7.2164046,
      'lon' => 51.4724399,
      'rad' => 100,
      'tst' => $timestamp,
    ]);
    $waypoint->save();
    $expected = $waypoint->id();
    $actual = $this->ownTracksWaypointService->getWaypointId($uid, $timestamp);
    $this->assertEquals($expected, $actual);
  }

}
