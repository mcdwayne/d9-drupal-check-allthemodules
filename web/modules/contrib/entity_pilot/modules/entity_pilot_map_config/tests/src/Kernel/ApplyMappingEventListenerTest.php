<?php

namespace Drupal\Tests\entity_pilot_map_config\Kernel;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Url;
use Drupal\entity_pilot\Data\FlightManifest;
use Drupal\entity_pilot\Entity\Account;
use Drupal\entity_pilot\Entity\Arrival;
use Drupal\entity_pilot\TransportInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\NodeInterface;

/**
 * Tests listener for passengers event fired from entity_pilot.customs service.
 *
 * @group entity_pilot
 */
class ApplyMappingEventListenerTest extends KernelTestBase {

  /**
   * Expected file UUID.
   */
  const EXPECTED_FILE_UUID = '82c0651e-9bf9-4de7-9800-be1d6a5ae5a4';

  use TestMappingTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'entity_pilot_map_config_test',
    'entity_pilot',
    'entity_pilot_map_config',
    'serialization',
    'hal',
    'node',
    'user',
    'rest',
    'text',
    'file',
    'field',
    'options',
    'system',
    'image',
    'filter',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('ep_arrival');
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('file');
    $this->installConfig('node');
    $this->installConfig('node');
    $this->installSchema('system', ['router']);
    \Drupal::service('router.builder')->rebuild();
    $this->installConfig('entity_pilot_map_config_test');
    $this->createTestMappings();
  }

  /**
   * Tests prepare passengers event listener.
   */
  public function testPreparePassengersEventListener() {
    /** @var \Drupal\entity_pilot\CustomsInterface $customs */
    $customs = \Drupal::service('entity_pilot.customs');
    $account = Account::create([
      'id' => 'test',
      'label' => 'test',
      'blackBoxKey' => '123456',
      'carrierId' => 1,
      'secret' => hex2bin("a22a0b2884fd73c4e211d68e1f031051"),
    ]);
    $account->save();
    $flights = Json::decode(file_get_contents(drupal_get_path('module', 'entity_pilot') . '/tests/src/Functional/Data/flights.json'));
    $flights = array_intersect_key($flights, [1, 2]);
    $flights = FlightManifest::fromArray($flights, "a22a0b2884fd73c4e211d68e1f031051");
    /** @var \Drupal\entity_pilot\Data\FlightManifestInterface $flight */
    $flight = reset($flights);
    /** @var \Drupal\entity_pilot\TransportInterface $transport */
    $transport = $this->createMock(TransportInterface::class);
    $contents = $flight->getTransposedContents(Url::fromRoute('<front>', [], ['absolute' => TRUE])
      ->toString(), $transport, $account);
    $flight->setContents(Json::decode($contents));
    $arrival = Arrival::create([
      'remote_id' => $flight->getRemoteId(),
      'contents' => $flight->getContents(TRUE),
      'info' => $flight->getInfo(),
      'account' => $account->id(),
      'mapping_fields' => 'test_mapping',
      'mapping_bundles' => 'test_mapping',
    ]);
    $entities = $customs->screen($arrival, TRUE);
    /** @var \Drupal\node\NodeInterface $entity */
    $entity = $entities[MappingHandlerTest::TEST_ARTICLE_UUID];
    $this->assertNotNull($entity);
    $this->assertInstanceOf(NodeInterface::class, $entity);
    // Test bundle mapping was applied.
    $this->assertEquals('post', $entity->bundle());
    // Test field mapping was applied.
    $this->assertNotEmpty($entity->field_images);
    $this->assertEquals(self::EXPECTED_FILE_UUID, $entity->field_images->entity->uuid());
    // Test field_tags not populated (missing).
    $this->assertEmpty($entity->field_tags);
  }

}
