<?php

namespace Drupal\Tests\entity_pilot_map_config\Kernel;

use Drupal\Component\Serialization\Json;
use Drupal\entity_pilot\Data\FlightManifest;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests configuration difference manager functionality.
 *
 * @coversDefaultClass Drupal\entity_pilot_map_config\ConfigurationDifferenceManager
 *
 * @group entity_pilot
 */
class ConfigurationDifferenceManagerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'entity_pilot_map_config',
    'entity_pilot_map_config_test',
    'entity_pilot',
    'serialization',
    'hal',
    'rest',
    'text',
    'node',
    'user',
    'system',
    'field',
    'file',
    'image',
    'filter',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('ep_arrival');
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installConfig('node');
    $this->installConfig('entity_pilot_map_config_test');
  }

  /**
   * Tests computeDifference.
   *
   * @covers ::computeDifference
   */
  public function testComputeDifference() {
    /** @var \Drupal\entity_pilot_map_config\ConfigurationDifferenceManagerInterface $manager */
    $manager = \Drupal::service('entity_pilot_map_config.difference_manager');
    $flights = Json::decode(file_get_contents(drupal_get_path('module', 'entity_pilot') . '/tests/src/Functional/Data/flights.json'));
    $flights = array_intersect_key($flights, [1, 2]);
    $flights = FlightManifest::fromArray($flights, "a22a0b2884fd73c4e211d68e1f031051");
    $flight = reset($flights);
    $difference = $manager->computeDifference($flight);
    $this->assertEquals([
      'node' => [
        'field_image' => 'image',
        'path' => 'path',
        'comment' => 'comment',
        'field_tags' => 'entity_reference',
      ],
      'user' => [
        'user_picture' => 'image',
      ],
    ], $difference->getMissingFields());
    $this->assertEquals(['node' => ['page', 'article']], $difference->getMissingBundles());
    $this->assertEquals(['taxonomy_term'], $difference->getMissingEntityTypes());
  }

}
