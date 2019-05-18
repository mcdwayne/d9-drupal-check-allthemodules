<?php

namespace Drupal\Tests\entity_pilot_map_config\Kernel;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Url;
use Drupal\entity_pilot\AccountInterface;
use Drupal\entity_pilot\Data\FlightManifest;
use Drupal\entity_pilot\TransportInterface;
use Drupal\entity_pilot_map_config\BundleMappingInterface;
use Drupal\entity_pilot_map_config\Entity\BundleMapping;
use Drupal\entity_pilot_map_config\Entity\FieldMapping;
use Drupal\entity_pilot_map_config\FieldMappingInterface;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests MappingHandler service.
 *
 * @group entity_pilot
 *
 * @coversDefaultClass Drupal\entity_pilot_map_config\MappingHandler
 */
class MappingHandlerTest extends KernelTestBase {

  /**
   * UUID of test article node.
   */
  const TEST_ARTICLE_UUID = '8384692b-379c-4067-b000-bea20ef3aaca';

  /**
   * UUID of test page node.
   */
  const TEST_PAGE_UUID = '7bec3ab2-cc87-488e-a607-7d70fb243e5f';

  /**
   * Field mapping.
   *
   * @var \Drupal\entity_pilot_map_config\FieldMappingInterface
   */
  protected $fieldMapping;

  /**
   * Bundle mapping.
   *
   * @var \Drupal\entity_pilot_map_config\BundleMappingInterface
   */
  protected $bundleMapping;

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
    $this->installSchema('system', ['router']);
    \Drupal::service('router.builder')->rebuild();
    $this->installConfig('entity_pilot_map_config_test');
    $this->bundleMapping = BundleMapping::create([
      'id' => 'test_mapping',
      'label' => 'Test mapping',
    ]);
    $bundle_mapping = [
      [
        'entity_type' => 'node',
        'source_bundle_name' => 'article',
        'destination_bundle_name' => 'post',
      ],
      [
        'entity_type' => 'node',
        'source_bundle_name' => 'page',
        'destination_bundle_name' => BundleMappingInterface::IGNORE_BUNDLE,
      ],
    ];
    $this->bundleMapping->setMappings($bundle_mapping);
    $this->bundleMapping->save();
    $this->fieldMapping = FieldMapping::create([
      'id' => 'test_mapping',
      'label' => 'Test mapping',
    ]);
    $field_mapping = [
      [
        'entity_type' => 'node',
        'source_field_name' => 'field_image',
        'destination_field_name' => 'field_images',
        'field_type' => 'image',
      ],
      [
        'entity_type' => 'node',
        'source_field_name' => 'path',
        'destination_field_name' => FieldMappingInterface::IGNORE_FIELD,
        'field_type' => 'path',
      ],
      [
        'entity_type' => 'node',
        'source_field_name' => 'comment',
        'destination_field_name' => FieldMappingInterface::IGNORE_FIELD,
        'field_type' => 'comment',
      ],
      [
        'entity_type' => 'node',
        'source_field_name' => 'field_tags',
        'destination_field_name' => FieldMappingInterface::IGNORE_FIELD,
        'field_type' => 'entity_reference',
      ],
    ];
    $this->fieldMapping->setMappings($field_mapping);
    $this->fieldMapping->save();
  }

  /**
   * Tests apply mapping.
   *
   * @covers ::applyMappingPair
   */
  public function testApplyMapping() {
    $flights = Json::decode(file_get_contents(drupal_get_path('module', 'entity_pilot') . '/tests/src/Functional/Data/flights.json'));
    $flights = array_intersect_key($flights, [1, 2]);
    $flights = FlightManifest::fromArray($flights, "a22a0b2884fd73c4e211d68e1f031051");
    /** @var \Drupal\entity_pilot\Data\FlightManifestInterface $flight */
    $flight = reset($flights);
    /** @var \Drupal\entity_pilot\TransportInterface $transport */
    $transport = $this->createMock(TransportInterface::class);
    /** @var \Drupal\entity_pilot\AccountInterface $account */
    $account = $this->createMock(AccountInterface::class);
    $contents = $flight->getTransposedContents(Url::fromRoute('<front>', [], ['absolute' => TRUE])
      ->toString(), $transport, $account);
    $passengers = Json::decode($contents);
    /** @var \Drupal\entity_pilot_map_config\MappingHandlerInterface $handler */
    $handler = \Drupal::service('entity_pilot_map_config.mapping_handler');
    $passengers = $handler->applyMappingPair($passengers, $this->fieldMapping, $this->bundleMapping);
    $node = $passengers[self::TEST_ARTICLE_UUID];
    // Check bundle mapping applied.
    $path = parse_url($node['_links']['type']['href'], PHP_URL_PATH);
    $this->assertEquals('/rest/type/node/post', $path);
    // Check field mapping applied.
    $link_manager = \Drupal::service('rest.link_manager');
    $this->assertNotEmpty($node['_embedded'][$link_manager->getRelationUri('node', 'post', 'field_images')]);
    $this->assertTrue(!isset($node['_embedded'][$link_manager->getRelationUri('node', 'article', 'field_image')]));
    // Test the NULL handler removed the path and field_tags field.
    $this->assertTrue(!isset($node['path']));
    $this->assertTrue(!isset($node['comment']));
    $this->assertTrue(!isset($node['_embedded'][$link_manager->getRelationUri('node', 'article', 'field_tags')]));
    $this->assertTrue(!isset($node['_embedded'][$link_manager->getRelationUri('node', 'post', 'field_tags')]));
    // This one should be removed because the bundle is mapped to ignore.
    $this->assertTrue(!isset($passengers[self::TEST_PAGE_UUID]));
  }

}
