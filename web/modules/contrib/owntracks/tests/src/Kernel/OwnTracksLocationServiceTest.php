<?php

namespace Drupal\Tests\owntracks\Kernel;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\owntracks\Entity\OwnTracksLocation;

/**
 * Class OwnTracksLocationServiceTest.
 *
 * @covers \Drupal\owntracks\OwnTracksLocationService
 *
 * @group owntracks
 */
class OwnTracksLocationServiceTest extends EntityKernelTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['owntracks', 'options'];

  /**
   * The OwnTracks Location service.
   *
   * @var \Drupal\owntracks\OwnTracksLocationService
   */
  protected $ownTracksLocationService;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('owntracks_location');
    $this->ownTracksLocationService = $this->container->get('owntracks.location_service');
  }

  /**
   * Tests the owntracks location service.
   */
  public function testLocationService() {
    $account = $this->createUser([], ['create owntracks entities', 'view own owntracks entities']);
    $uid = $account->id();
    $tid = '5X';
    $date = DrupalDateTime::createFromArray([
      'day'    => 10,
      'month'  => 10,
      'year'   => 2012,
      'hour'   => 12,
      'minute' => 0,
      'second' => 0,
    ]);

    // Expected user, timestamp within range, expected tracker ID.
    OwnTracksLocation::create([
      'uid' => $uid,
      'lat' => 7.12345678,
      'lon' => 53.12345678,
      'tst' => (int) $date->format('U') + 43199,
      'tid' => $tid,
    ])->save();

    // Expected user, timestamp within range, expected tracker ID.
    OwnTracksLocation::create([
      'uid' => $uid,
      'lat' => 6.12345678,
      'lon' => 54.12345678,
      'tst' => (int) $date->format('U') - 43200,
      'tid' => $tid,
    ])->save();

    // Expected user, timestamp above range, expected tracker ID.
    OwnTracksLocation::create([
      'uid' => $uid,
      'lat' => 5.12345678,
      'lon' => 55.12345678,
      'tst' => (int) $date->format('U') + 43200,
      'tid' => $tid,
    ])->save();

    // Expected user, timestamp below range, expected tracker ID.
    OwnTracksLocation::create([
      'uid' => $uid,
      'lat' => 4.12345678,
      'lon' => 56.12345678,
      'tst' => (int) $date->format('U') - 43201,
      'tid' => $tid,
    ])->save();

    // Expected user, timestamp within range, other tracker ID.
    OwnTracksLocation::create([
      'uid' => $uid,
      'lat' => 3.12345678,
      'lon' => 57.12345678,
      'tst' => (int) $date->format('U'),
      'tid' => '6X',
    ])->save();

    // Other user, timestamp within range, expected tracker ID.
    OwnTracksLocation::create([
      'uid' => $this->createUser([], ['create owntracks entities', 'view own owntracks entities'])->id(),
      'lat' => 2.12345678,
      'lon' => 58.12345678,
      'tst' => (int) $date->format('U'),
      'tid' => $tid,
    ])->save();

    $actual = $this->ownTracksLocationService->getUserTrack($account, $date, $tid);
    $this->assertEquals([[6.12345678, 54.12345678], [7.12345678, 53.12345678]], $actual);
  }

}
