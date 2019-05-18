<?php

namespace Drupal\Tests\entity_pilot\Kernel;

use Drupal\Component\Serialization\Json;
use Drupal\entity_pilot\Data\FlightManifest;
use Drupal\entity_pilot\Entity\Account;
use Drupal\entity_pilot\Entity\Arrival;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests prepare passengers event fired from entity_pilot.customs service.
 *
 * @group entity_pilot
 */
class PreparePassengersEventTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'entity_pilot_event_test',
    'entity_pilot',
    'serialization',
    'hal',
    'node',
    'taxonomy',
    'user',
    'rest',
    'text',
    'file',
    'image',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('ep_arrival');
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('file');
  }

  /**
   * Tests prepare passengers event.
   */
  public function testPreparePassengersEvent() {
    /** @var \Drupal\entity_pilot\CustomsInterface $customs */
    $customs = \Drupal::service('entity_pilot.customs');
    $account = Account::create([
      'id' => 'test',
      'label' => 'test',
      'blackBoxKey' => '123456',
      'carrierId' => 1,
      'legacy_secret' => "a22a0b2884fd73c4e211d68e1f031051",
    ]);
    $account->save();
    $flights = Json::decode(file_get_contents(drupal_get_path('module', 'entity_pilot') . '/tests/src/Functional/Data/flights.json'));
    $flights = array_intersect_key($flights, [1, 2]);
    $flights = FlightManifest::fromArray($flights, "a22a0b2884fd73c4e211d68e1f031051");
    /** @var \Drupal\entity_pilot\Data\FlightManifestInterface $flight */
    $flight = reset($flights);
    $arrival = Arrival::create([
      'remote_id' => $flight->getRemoteId(),
      'contents' => $flight->getContents(TRUE),
      'info' => $flight->getInfo(),
      'account' => $account->id(),
    ]);
    $customs->screen($arrival, TRUE);
    $state = \Drupal::state()->get('entity_pilot_test_event.result', FALSE);
    $this->assertTrue($state);
  }

}
