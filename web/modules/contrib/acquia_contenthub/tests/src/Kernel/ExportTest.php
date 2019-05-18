<?php

namespace Drupal\Tests\acquia_contenthub\Kernel;

use Acquia\ContentHubClient\CDF\CDFObject;
use Acquia\ContentHubClient\CDF\CDFObjectInterface;
use Drupal\Component\Serialization\Yaml;
use Drupal\Component\Uuid\Uuid;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\user\Entity\User;

/**
 * Class ExportTest.
 *
 * @group orca_ignore
 *
 * @package Drupal\Tests\acquia_contenthub\Kernel
 */
class ExportTest extends EntityKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'user',
    'image',
    'file',
    'node',
    'field',
    'taxonomy',
    'depcalc',
    'acquia_contenthub',
    'acquia_contenthub_publisher',
  ];

  /**
   * Acquia ContentHub export queue.
   *
   * @var \Drupal\acquia_contenthub_publisher\ContentHubExportQueue
   */
  protected $contentHubQueue;

  /**
   * Queue.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected $queue;

  /**
   * Queue worker.
   *
   * @var \Drupal\Core\Queue\QueueWorkerInterface
   */
  protected $queueWorker;

  /**
   * Stream Wrapper Manager service.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected $streamWrapperManager;

  /**
   * Content Hub Publisher Tracker service.
   *
   * @var \Drupal\acquia_contenthub_publisher\PublisherTracker
   */
  protected $publisherTracker;

  /**
   * CDF Object.
   *
   * @var \Acquia\ContentHubClient\CDF\CDFObject
   */
  protected $cdfObject;

  /**
   * Temporary storage for node type UUIDs.
   *
   * @var array
   */
  protected $nodeTypeUuids = [];

  /**
   * Temporary storage for taxonomy vocabulary UUIDs.
   *
   * @var array
   */
  protected $vocabularyUuids = [];

  /**
   * Temporary storage for taxonomy term UUIDs.
   *
   * @var array
   */
  protected $termUuids = [];

  /**
   * Temporary storage for field UUIDs.
   *
   * @var array
   */
  protected $fieldUuids = [];

  /**
   * Temporary storage for user UUIDs.
   *
   * @var array
   */
  protected $userUuids = [];

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  public function setUp() {
    parent::setUp();

    $this->installEntitySchema('taxonomy_term');
    $this->installSchema('acquia_contenthub_publisher', ['acquia_contenthub_publisher_export_tracking']);
    $this->installSchema('node', ['node_access']);
    $this->installEntitySchema('file');
    $this->installSchema('file', ['file_usage']);
    $this->installSchema('user', ['users_data']);
    $this->installConfig([
      'acquia_contenthub',
      'acquia_contenthub_publisher',
      'system',
      'field',
      'node',
      'file',
      'user',
      'taxonomy',
    ]);

    $origin_uuid = '00000000-0000-0001-0000-123456789123';

    // Acquia ContentHub export queue service.
    $this->contentHubQueue = $this->container->get('acquia_contenthub_publisher.acquia_contenthub_export_queue');

    $cdf_object = $this->getMockBuilder(CDFObjectInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $cdf_object->method('getOrigin')
      ->willReturn($origin_uuid);

    // Mock Acquia ContentHub Client.
    $response = $this->getMockBuilder('\Psr\Http\Message\ResponseInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $response->method('getStatusCode')
      ->willReturn(202);

    $contenthub_client = $this->getMockBuilder('\Acquia\ContentHubClient\ContentHubClient')
      ->disableOriginalConstructor()
      ->getMock();
    $contenthub_client->method('putEntities')
      ->with($this->captureArg($this->cdfObject))
      ->willReturn($response);
    $contenthub_client->method('deleteEntity')
      ->willReturn($response);
    $contenthub_client->method('getEntity')
      ->willReturn($cdf_object);

    $contenthub_client_factory = $this->getMockBuilder('\Drupal\acquia_contenthub\Client\ClientFactory')
      ->disableOriginalConstructor()
      ->getMock();
    $contenthub_client_factory->method('getClient')
      ->willReturn($contenthub_client);
    $this->container->set('acquia_contenthub.client.factory', $contenthub_client_factory);

    $contenthub_settings = $this->getMockBuilder('\Acquia\ContentHubClient\Settings')
      ->disableOriginalConstructor()
      ->getMock();
    $contenthub_settings->method('getUuid')
      ->willReturn($origin_uuid);

    $contenthub_client_factory->method('getSettings')
      ->willReturn($contenthub_settings);

    $contenthub_client->method('getSettings')
      ->willReturn($contenthub_settings);

    // Setup queue.
    $queue_factory = $this->container->get('queue');
    $queue_worker_manager = $this->container->get('plugin.manager.queue_worker');
    $name = 'acquia_contenthub_publish_export';
    $this->queueWorker = $queue_worker_manager->createInstance($name);
    $this->queue = $queue_factory->get($name);

    // Add stream wrapper manager service.
    $this->streamWrapperManager = \Drupal::service('stream_wrapper_manager');

    // Add Content Hub tracker service.
    $this->publisherTracker = \Drupal::service('acquia_contenthub_publisher.tracker');
  }

  /**
   * Captures $objects argument value of "putEntities" method.
   *
   * @param mixed $argument
   *   A method's argument.
   *
   * @return \PHPUnit\Framework\Constraint\Callback
   *   Callback.
   *
   * @see \Drupal\acquia_contenthub_publisher\Plugin\QueueWorker\ContentHubExportQueueWorker::processItem()
   */
  protected function captureArg(&$argument) {
    return $this->callback(function ($argument_to_mock) use (&$argument) {
      $argument = $argument_to_mock;
      return TRUE;
    });
  }

  /**
   * Tests Acquia ContentHub export queue.
   *
   * @see ContentHubExportQueue
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testQueue() {
    // Initial queue state and "purge" operation.
    $expected = 0;
    $this->assertTrue($this->contentHubQueue->getQueueCount() > $expected);
    $this->contentHubQueue->purgeQueues();
    $this->assertEquals($expected, $this->contentHubQueue->getQueueCount());

    // Node types.
    $this->createNodeTypes($items_expected);
    $expected += $items_expected;
    $this->assertEquals($expected, $this->contentHubQueue->getQueueCount());

    // Nodes.
    list($nid1, $nid2) = $this->createNodes($items_expected);
    $expected += $items_expected;
    $this->assertEquals($expected, $this->contentHubQueue->getQueueCount());

    // Users.
    list($uid1,) = $this->createUsers($items_expected);
    $expected += $items_expected;
    $this->assertEquals($expected, $this->contentHubQueue->getQueueCount());

    // Taxonomy vocabulary.
    $this->createTaxonomyVocabulary($items_expected);
    $expected += $items_expected;
    $this->assertEquals($expected, $this->contentHubQueue->getQueueCount());

    // Taxonomy terms.
    list($tid1, $tid2, $tid3) = $this->createTaxonomyTerms($items_expected);
    $expected += $items_expected;
    $this->assertEquals($expected, $this->contentHubQueue->getQueueCount());

    // Field storages.
    $this->createFieldStorages($items_expected);
    $expected += $items_expected;
    $this->assertEquals($expected, $this->contentHubQueue->getQueueCount());

    // Fields.
    $this->createFields($items_expected);
    $expected += $items_expected;
    $this->assertEquals($expected, $this->contentHubQueue->getQueueCount());

    // Node with custom field.
    $nid3 = $this->createNodeWithField($items_expected);
    $expected += $items_expected;
    $this->assertEquals($expected, $this->contentHubQueue->getQueueCount());

    // Node with entity reference.
    $nid4 = $this->createNodeWithReference($tid1, $tid2, $tid3, $items_expected);
    $expected += $items_expected;
    $this->assertEquals($expected, $this->contentHubQueue->getQueueCount());

    // File entity.
    $this->createFile($uid1, $items_expected);
    $expected += $items_expected;
    $this->assertEquals($expected, $this->contentHubQueue->getQueueCount());

    // Purge queue.
    $this->contentHubQueue->purgeQueues();
    $this->assertEquals(0, $this->contentHubQueue->getQueueCount());

    // Node update.
    $this->updateNode($nid3, $items_expected);
    $this->assertEquals($items_expected, $this->contentHubQueue->getQueueCount());

    // Purge queue.
    $this->contentHubQueue->purgeQueues();
    $this->assertEquals(0, $this->contentHubQueue->getQueueCount());

    // Node delete.
    $this->deleteNodes([$nid1, $nid2, $nid3, $nid4]);
    $this->assertEquals(0, $this->contentHubQueue->getQueueCount());
  }

  /**
   * Tests Acquia ContentHub content/configuration export.
   *
   * @throws \Exception
   */
  public function testPublishing() {
    $this->contentHubQueue->purgeQueues();

    // Node types.
    $cdf_expectations = [];
    $this->createNodeTypes($items_expected, $cdf_expectations);
    $this->processQueue($items_expected,
      $cdf_expectations,
      [
        $this,
        'validateNodeTypeCdfObject',
      ]
    );

    // Nodes.
    $cdf_expectations = [];
    $this->createNodes($items_expected, $cdf_expectations);
    $this->processQueue($items_expected,
      $cdf_expectations,
      [
        $this,
        'validateNodeCdfObject',
      ]
    );

    // Users.
    $cdf_expectations = [];
    list($uid1,) = $this->createUsers($items_expected, $cdf_expectations);
    $this->processQueue($items_expected,
      $cdf_expectations,
      [
        $this,
        'validateUserCdfObject',
      ]
    );

    // Taxonomy vocabulary.
    $cdf_expectations = [];
    $this->createTaxonomyVocabulary($items_expected, $cdf_expectations);
    $this->processQueue($items_expected,
      $cdf_expectations,
      [
        $this,
        'validateTaxonomyVocabularyCdfObject',
      ]
    );

    // Taxonomy terms.
    $cdf_expectations = [];
    list($tid1, $tid2, $tid3) = $this->createTaxonomyTerms($items_expected, $cdf_expectations);
    $this->processQueue($items_expected,
      $cdf_expectations,
      [
        $this,
        'validateTaxonomyTermCdfObject',
      ]
    );

    // Field storages.
    $cdf_expectations = [];
    $this->createFieldStorages($items_expected, $cdf_expectations);
    $this->processQueue($items_expected,
      $cdf_expectations,
      [
        $this,
        'validateFieldStorageCdfObject',
      ]
    );

    // Fields.
    $cdf_expectations = [];
    $this->createFields($items_expected, $cdf_expectations);
    $this->processQueue($items_expected,
      $cdf_expectations,
      [
        $this,
        'validateFieldCdfObject',
      ]
    );

    // Node with text field.
    $cdf_expectations = [];
    $nid = $this->createNodeWithField($items_expected, $cdf_expectations);
    $this->processQueue($items_expected,
      $cdf_expectations,
      [
        $this,
        'validateNodeCdfObject',
      ]
    );

    // Node with entity reference field.
    $cdf_expectations = [];
    $this->createNodeWithReference($tid1, $tid2, $tid3, $items_expected, $cdf_expectations);
    $this->processQueue($items_expected,
      $cdf_expectations,
      [
        $this,
        'validateNodeCdfObject',
      ]
    );

    // File.
    $cdf_expectations = [];
    $this->createFile($uid1, $items_expected, $cdf_expectations);
    $this->processQueue($items_expected,
      $cdf_expectations,
      [
        $this,
        'validateFileCdfObject',
      ]
    );

    // Node update.
    $cdf_expectations = [];
    $this->updateNode($nid, $items_expected, $cdf_expectations);
    $this->processQueue($items_expected,
      $cdf_expectations,
      [
        $this,
        'validateNodeCdfObject',
      ]
    );
  }

  /**
   * Tests publishing of node and user profile.
   *
   * @throws \Exception
   */
  public function testPublishingUserProfile() {
    $this->contentHubQueue->purgeQueues();

    // Create "user_picture" image field.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'user_picture',
      'entity_type' => 'user',
      'type' => 'image',
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'field_name' => 'user_picture',
      'entity_type' => 'user',
      'bundle' => 'user',
    ]);
    $field->save();
    $this->processQueue(2);

    // Users.
    $cdf_expectations = [];
    list($uid,) = $this->createUsers($items_expected, $cdf_expectations);
    $this->processQueue(2);

    // Node types.
    $cdf_expectations = [];
    $this->createNodeTypes($items_expected, $cdf_expectations);
    $this->processQueue(2);

    // Nodes.
    $cdf_expectations = [];
    list($nid,) = $this->createNodes($items_expected, $cdf_expectations, [$uid]);
    $this->processQueue(2);

    // Create a dummy profile image file.
    $filename = 'avatar.jpg';
    $uri = 'public://avatar.jpg';
    $filemime = 'image/jpeg';
    $file = File::create();
    $file->setOwnerId($uid);
    $file->setFilename($filename);
    $file->setMimeType($filemime);
    $file->setFileUri($uri);
    $file->set('status', FILE_STATUS_PERMANENT);
    $file->save();
    $fid = $file->id();
    $this->fieldUuids[] = $file->uuid();

    // Update user profile image.
    $user = User::load($uid);
    $user->set('user_picture', $fid);
    $user->save();
    $this->processQueue(2);

    // Update node authored by the user.
    $cdf_expectations = [];
    $this->updateAuthoredNode($nid, $items_expected, $cdf_expectations);
    $this->processQueue($items_expected,
      $cdf_expectations,
      [
        $this,
        'validateNodeCdfObject',
      ]
    );
  }

  /**
   * Tests deleting content.
   *
   * @see ContentHubExportQueue
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Exception
   */
  public function testDelete() {
    $this->contentHubQueue->purgeQueues();

    // Node types.
    $cdf_expectations = [];
    $this->createNodeTypes($items_expected, $cdf_expectations);
    $this->processQueue($items_expected,
      $cdf_expectations,
      [
        $this,
        'validateNodeTypeCdfObject',
      ]
    );

    // Nodes.
    $cdf_expectations = [];
    list($nid1, $nid2) = $this->createNodes($items_expected, $cdf_expectations);

    // Node delete.
    $this->deleteNodes([$nid1, $nid2], $cdf_expectations);

    // Process queue with missing entities shouldn't throw an error.
    $this->processQueue($items_expected,
      $cdf_expectations,
      [
        $this,
        'assertTrue',
      ]
    );

    $this->assertEquals(0, $this->contentHubQueue->getQueueCount());
  }

  /**
   * Creates sample node types.
   *
   * @param int $items_expected
   *   Expected number of items in the queue.
   * @param array $cdf_expectations
   *   The sets of expectation arguments for CDF object validation.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createNodeTypes(&$items_expected, array &$cdf_expectations = []) {
    // Node types.
    $label1 = 'Test Content Type #1';
    $bundle1 = 'test_content_type';
    $node_type1 = NodeType::create([
      'type' => $bundle1,
      'name' => $label1,
    ]);
    $node_type1->save();
    $this->nodeTypeUuids[] = $node_type1->uuid();

    $label2 = 'Test Content Type #2';
    $bundle2 = 'test_content_type2';
    $node_type2 = NodeType::create([
      'type' => $bundle2,
      'name' => $label2,
    ]);
    $node_type2->save();
    $this->nodeTypeUuids[] = $node_type2->uuid();

    $items_expected = 2;

    // Setup CDF expectations.
    $dependencies = [
      'module' => [
        'node',
      ],
    ];
    $cdf_expectations = [
      [
        $dependencies,
        $bundle1,
        $label1,
      ],
      [
        $dependencies,
        $bundle2,
        $label2,
      ],
    ];
  }

  /**
   * Creates node samples.
   *
   * @param int $items_expected
   *   Expected number of items in the queue.
   * @param array $cdf_expectations
   *   The sets of expectation arguments for CDF object validation.
   * @param array $uids
   *   Optional list of author ids.
   *
   * @return array
   *   List on node ids.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createNodes(&$items_expected, array &$cdf_expectations = [], array $uids = []) {
    $uid = array_shift($uids);
    $bundle = 'test_content_type';
    $title1 = $this->getRandomGenerator()->word(15);
    $values = [
      'title' => $title1,
      'type' => $bundle,
      'status' => Node::PUBLISHED,
      'uid' => $uid ? $uid : 0,
    ];
    $node1 = Node::create($values);
    $node1->save();

    $uid = array_shift($uids);
    $title2 = $this->getRandomGenerator()->word(15);
    $values = [
      'title' => $title2,
      'type' => $bundle,
      'status' => Node::NOT_PUBLISHED,
      'uid' => $uid ? $uid : 0,
    ];
    $node2 = Node::create($values);
    $node2->save();

    $items_expected = 2;
    $cdf_expectations = [
      [
        $title1,
        $bundle,
        Node::PUBLISHED,
      ],
      [
        $title2,
        $bundle,
        Node::NOT_PUBLISHED,
      ],
    ];

    return [
      $node1->id(),
      $node2->id(),
    ];
  }

  /**
   * Creates sample Drupal user entities.
   *
   * @param int $items_expected
   *   Expected number of items in the queue.
   * @param array $cdf_expectations
   *   The sets of expectation arguments for CDF object validation.
   *
   * @return array
   *   List of user ids.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createUsers(&$items_expected, array &$cdf_expectations = []) {
    $name1 = $this->randomString();
    $email1 = 'email1@example.com';
    $user1 = User::create([
      'uid' => 1,
      'name' => $name1,
      'mail' => $email1,
    ]);
    $user1->save();
    $this->userUuids[] = $user1->uuid();

    $name2 = $this->randomString();
    $email2 = 'email2@example.com';
    $user2 = User::create([
      'uid' => 2,
      'name' => $name2,
      'mail' => $email2,
    ]);
    $user2->save();
    $this->userUuids[] = $user2->uuid();

    $items_expected = 2;

    $cdf_expectations = [
      [
        $name1,
        $email1,
      ],
      [
        $name2,
        $email2,
      ],
    ];

    return [
      $user1->id(),
      $user2->id(),
    ];
  }

  /**
   * Creates a sample taxonomy vocabulary.
   *
   * @param int $items_expected
   *   Expected number of items in the queue.
   * @param array $cdf_expectations
   *   The sets of expectation arguments for CDF object validation.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createTaxonomyVocabulary(&$items_expected, array &$cdf_expectations = []) {
    $name1 = $this->randomString();
    $vid1 = $this->randomMachineName();
    $description1 = $this->randomString(128);
    $weight1 = rand(-100, 100);
    $vocabulary1 = Vocabulary::create([
      'name' => $name1,
      'vid' => $vid1,
      'description' => $description1,
      'weight' => $weight1,
    ]);
    $vocabulary1->save();
    $this->vocabularyUuids[] = $vocabulary1->uuid();

    $name2 = $this->randomString();
    $description2 = $this->randomString(128);
    $weight2 = rand(-100, 100);
    $vocabulary2 = Vocabulary::create([
      'name' => $name2,
      'vid' => 'test_vocabulary',
      'description' => $description2,
      'weight' => $weight2,
    ]);
    $vocabulary2->save();
    $this->vocabularyUuids[] = $vocabulary2->uuid();

    $items_expected = 2;

    $cdf_expectations = [
      [
        $vid1,
        $name1,
        $description1,
        $weight1,
      ],
      [
        'test_vocabulary',
        $name2,
        $description2,
        $weight2,
      ],
    ];
  }

  /**
   * Creates sample taxonomy terms.
   *
   * @param int $items_expected
   *   Expected number of items in the queue.
   * @param array $cdf_expectations
   *   The sets of expectation arguments for CDF object validation.
   *
   * @return array
   *   List of taxonomy term ids.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createTaxonomyTerms(&$items_expected, array &$cdf_expectations = []) {
    $bundle = 'test_vocabulary';
    $this->termUuids = [];

    $name1 = $this->randomMachineName(20);
    $description1 = $this->randomMachineName(32);
    $term1 = Term::create([
      'description' => [['value' => $description1]],
      'name' => $name1,
      'vid' => $bundle,
      'uid' => 1,
    ]);
    $term1->save();
    $this->termUuids[] = $term1->uuid();

    $name2 = $this->randomMachineName(20);
    $description2 = $this->randomMachineName(32);
    $term2 = Term::create([
      'description' => [['value' => $description2]],
      'name' => $name2,
      'vid' => $bundle,
      'uid' => 2,
    ]);
    $term2->save();
    $this->termUuids[] = $term2->uuid();

    $name3 = $this->randomMachineName(20);
    $description3 = $this->randomMachineName(32);
    $term3 = Term::create([
      'description' => [['value' => $description3]],
      'name' => $name3,
      'vid' => $bundle,
      'uid' => 3,
    ]);
    $term3->save();
    $this->termUuids[] = $term3->uuid();

    $items_expected = 3;
    $cdf_expectations = [
      [
        $name1,
        $bundle,
        $description1,
      ],
      [
        $name2,
        $bundle,
        $description2,
      ],
      [
        $name3,
        $bundle,
        $description3,
      ],
    ];

    return [
      $term1->id(),
      $term2->id(),
      $term3->id(),
    ];
  }

  /**
   * Creates fields.
   *
   * @param int $items_expected
   *   Expected number of items in the queue.
   * @param array $cdf_expectations
   *   The sets of expectation arguments for CDF object validation.
   *
   * @see \Drupal\Tests\acquia_contenthub\Kernel\ExportTest::createFieldStorages()
   * @see \Drupal\Tests\acquia_contenthub\Kernel\ExportTest::createNodeTypes()
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createFields(&$items_expected, array &$cdf_expectations = []) {
    // Text field.
    $field_definition = [
      'field_name' => 'field_1',
      'entity_type' => 'node',
      'bundle' => 'test_content_type',
    ];
    FieldConfig::create($field_definition)->save();

    // Entity reference field.
    $field_definition = [
      'field_name' => 'field_term_reference',
      'entity_type' => 'node',
      'bundle' => 'test_content_type',
    ];
    FieldConfig::create($field_definition)->save();

    $items_expected = 2;

    $cdf_expectations = [
      [
        'field_1',
        ['node'],
        'string_long',
        'test_content_type',
        [],
      ],
      [
        'field_term_reference',
        ['node', 'taxonomy'],
        'entity_reference',
        'test_content_type',
        ['handler' => 'default:taxonomy_term', 'handler_settings' => []],
      ],
    ];
  }

  /**
   * Creates field storages.
   *
   * @param int $items_expected
   *   Expected number of items in the queue.
   * @param array $cdf_expectations
   *   The sets of expectation arguments for CDF object validation.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createFieldStorages(&$items_expected, array &$cdf_expectations = []) {
    // Text field.
    $field_storage_definition1 = [
      'field_name' => 'field_1',
      'type' => 'string_long',
      'entity_type' => 'node',
    ];
    $field_storage1 = FieldStorageConfig::create($field_storage_definition1);
    $field_storage1->save();
    $this->fieldUuids[] = $field_storage1->uuid();

    // Entity reference field.
    $field_storage_definition2 = [
      'entity_type' => 'node',
      'field_name' => 'field_term_reference',
      'type' => 'entity_reference',
      'settings' => [
        'target_type' => 'taxonomy_term',
      ],
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ];
    $field_storage2 = FieldStorageConfig::create($field_storage_definition2);
    $field_storage2->save();
    $this->fieldUuids[] = $field_storage2->uuid();

    $items_expected = 2;
    $cdf_expectations = [
      [
        'field_1',
        'string_long',
        ['node'],
        ['case_sensitive' => FALSE],
        1,
      ],
      [
        'field_term_reference',
        'entity_reference',
        ['node', 'taxonomy'],
        ['target_type' => 'taxonomy_term'],
        FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      ],
    ];
  }

  /**
   * Creates a node with a field value.
   *
   * @param int $items_expected
   *   Expected number of items in the queue.
   * @param array $cdf_expectations
   *   The sets of expectation arguments for CDF object validation.
   *
   * @return int
   *   Node id.
   *
   * @see \Drupal\Tests\acquia_contenthub\Kernel\ExportTest::createFields()
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createNodeWithField(&$items_expected, array &$cdf_expectations = []) {
    $title = $this->getRandomGenerator()->word(32);
    $bundle = 'test_content_type';
    $field_value = $this->getRandomGenerator()->string(256);
    $values = [
      'title' => $title,
      'type' => $bundle,
      'status' => Node::PUBLISHED,
      'field_1' => [
        'value' => $field_value,
      ],
    ];
    $node = Node::create($values);
    $node->save();

    $items_expected = 1;
    $field = [
      'field_1' => [
        'value' => [
          'en' => [
            'value' => $field_value,
          ],
        ],
      ],
      'field_term_reference' => [
        'value' => [
          'en' => [],
        ],
      ],
    ];
    $field_metadata = [
      'field_1' => [
        'type' => 'string_long',
      ],
      'field_term_reference' => [
        'type' => 'entity_reference',
        'target' => 'taxonomy_term',
      ],
    ];
    $cdf_expectations = [
      [$title, $bundle, Node::PUBLISHED, $field, $field_metadata],
    ];

    return $node->id();
  }

  /**
   * Creates a node with an entity reference field.
   *
   * @param int $tid1
   *   Id of a taxonomy term.
   * @param int $tid2
   *   Id of a taxonomy term.
   * @param int $tid3
   *   Id of a taxonomy term.
   * @param int $items_expected
   *   Expected number of items in the queue.
   * @param array $cdf_expectations
   *   The sets of expectation arguments for CDF object validation.
   *
   * @return int
   *   Node id.
   *
   * @see \Drupal\Tests\acquia_contenthub\Kernel\ExportTest::createFields()
   * @see \Drupal\Tests\acquia_contenthub\Kernel\ExportTest::createTaxonomyTerms()
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createNodeWithReference($tid1, $tid2, $tid3, &$items_expected, array &$cdf_expectations = []) {
    $title = $this->getRandomGenerator()->word(32);
    $bundle = 'test_content_type';
    $node = Node::create([
      'type' => 'test_content_type',
      'title' => $title,
      'status' => Node::NOT_PUBLISHED,
      'field_term_reference' => [
        ['target_id' => $tid1],
        ['target_id' => $tid2],
        ['target_id' => $tid3],
      ],
    ]);
    $node->save();

    $items_expected = 1;
    $field = [
      'field_1' => [],
      'field_term_reference' => [
        'value' => [
          'en' => $this->termUuids,
        ],
      ],
    ];
    $field_metadata = [
      'field_1' => [
        'type' => 'string_long',
      ],
      'field_term_reference' => [
        'type' => 'entity_reference',
        'target' => 'taxonomy_term',
      ],
    ];
    $cdf_expectations = [
      [$title, $bundle, Node::NOT_PUBLISHED, $field, $field_metadata],
    ];

    return $node->id();
  }

  /**
   * Creates a Drupal file entity.
   *
   * @param int $uid
   *   Id of the owner (user).
   * @param int $items_expected
   *   Expected number of items in the queue.
   * @param array $cdf_expectations
   *   The sets of expectation arguments for CDF object validation.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createFile($uid, &$items_expected, array &$cdf_expectations = []) {
    $filename = 'contenthub_export_test.txt';
    $uri = 'public://contenthub_export_test.txt';
    $filemime = 'text/plain';
    $content = $this->getRandomGenerator()->paragraphs(5);

    $file = File::create();
    $file->setOwnerId($uid);
    $file->setFilename($filename);
    $file->setMimeType($filemime);
    $file->setFileUri($uri);
    $file->set('status', FILE_STATUS_PERMANENT);
    $file->save();
    file_put_contents($file->getFileUri(), $content);

    $items_expected = 1;

    $cdf_expectations = [
      [$filename, $uri, $filemime],
    ];
  }

  /**
   * Updates the node.
   *
   * @param int $nid
   *   Node id.
   * @param int $items_expected
   *   Expected number of items in the queue.
   * @param array $cdf_expectations
   *   The sets of expectation arguments for CDF object validation.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function updateNode($nid, &$items_expected, array &$cdf_expectations = []) {
    $title = $this->randomString(30);
    $field_value = $this->randomString(100);

    $node = Node::load($nid);
    $node->set('title', $title);
    $node->set('field_1', $field_value);
    $node->set('status', Node::NOT_PUBLISHED);
    $node->save();

    $items_expected = 1;

    $bundle = 'test_content_type';
    $field = [
      'field_1' => [
        'value' => [
          'en' => [
            'value' => $field_value,
          ],
        ],
      ],
      'field_term_reference' => [
        'value' => [
          'en' => [],
        ],
      ],
    ];
    $field_metadata = [
      'field_1' => [
        'type' => 'string_long',
      ],
      'field_term_reference' => [
        'type' => 'entity_reference',
        'target' => 'taxonomy_term',
      ],
    ];

    $cdf_expectations = [
      [$title, $bundle, Node::NOT_PUBLISHED, $field, $field_metadata],
    ];
  }

  /**
   * Deletes nodes.
   *
   * @param int[] $nids
   *   Node nids.
   * @param array $cdf_expectations
   *   CDF expectations.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function deleteNodes(array $nids, array &$cdf_expectations = []) {
    foreach ($nids as $nid) {
      $node = Node::load($nid);
      $uuid = $node->uuid();
      $node->delete();

      $trackRecord = $this->publisherTracker->get($uuid);
      $this->assertEmpty($trackRecord, "No tracking record for deleted entity.");

      $cdf_expectations[] = TRUE;
    }
  }

  /**
   * Processes queue items.
   *
   * @param int $items_expected
   *   Number of items to expect in the queue.
   * @param array $cdf_expectations
   *   List of CDF object expectations.
   * @param callable $callback
   *   CDF Object verification method.
   *
   * @throws \Exception
   */
  protected function processQueue($items_expected, array $cdf_expectations = [], callable $callback = NULL) {
    $items = 0;
    while ($item = $this->queue->claimItem()) {
      $this->queueWorker->processItem($item->data);
      $this->queue->deleteItem($item);
      if ($callback) {
        $callback(...$cdf_expectations[$items]);
      }
      $items++;
    }
    $this->assertEquals($items_expected, $items);
  }

  /**
   * Performs basic CDF Object validation.
   */
  protected function validateBaseCdfObject() {
    $cdf = $this->cdfObject;
    $this->assertNotEmpty($cdf);

    // Validate Origin attribute.
    $origin = $cdf->getOrigin();
    $this->assertEquals('00000000-0000-0001-0000-123456789123', $origin);

    // Validate date values.
    $iso8601_regex = '/^(?:[1-9]\d{3}-(?:(?:0[1-9]|1[0-2])-(?:0[1-9]|1\d|2[0-8])|(?:0[13-9]|1[0-2])-(?:29|30)|(?:0[13578]|1[02])-31)|(?:[1-9]\d(?:0[48]|[2468][048]|[13579][26])|(?:[2468][048]|[13579][26])00)-02-29)T(?:[01]\d|2[0-3]):[0-5]\d:[0-5]\d(?:Z|[+-][01]\d:[0-5]\d)$/';
    $date_created = $cdf->getCreated();
    $date_created_is_valid = preg_match($iso8601_regex, $date_created);
    $this->assertTrue($date_created_is_valid);
    $date_modified = $cdf->getModified();
    $date_modified_is_valid = preg_match($iso8601_regex, $date_modified);
    $this->assertTrue($date_modified_is_valid);

    // Validate UUID.
    $uuid = $cdf->getUuid();
    $this->assertTrue(Uuid::isValid($uuid));

    // Validate "base_url" attribute.
    $base_url = $this->getCdfAttribute($cdf, 'base_url');
    $url = Url::fromUserInput('/', ['absolute' => TRUE]);
    $url = $url->toString();
    $this->assertEquals($url, $base_url);

    // Validate "hash" attribute.
    // "40" is the default length of sha1 hash string.
    $hash = $this->getCdfAttribute($cdf, 'hash');
    $this->assertEquals(40, strlen($hash));

    // Validate "data" attribute.
    $this->assertNotEmpty($this->getCdfDataAttribute($cdf));
  }

  /**
   * Validates node type CDF object.
   *
   * @param array $dependencies_expected
   *   Dependencies.
   * @param string $bundle_expected
   *   Expected node's bundle machine name.
   * @param string $bundle_label_expected
   *   Expected bundle label.
   */
  protected function validateNodeTypeCdfObject(array $dependencies_expected, $bundle_expected, $bundle_label_expected) {
    $this->validateBaseCdfObject();
    $cdf = $this->cdfObject;

    // Validate Type attribute.
    $type = $cdf->getType();
    $this->assertEquals('drupal8_config_entity', $type);

    // Validate "Data" attribute.
    $data = $this->getCdfDataAttribute($cdf);
    $data_expected = [
      'en' => [
        'uuid' => $cdf->getUuid(),
        'langcode' => 'en',
        'status' => TRUE,
        'dependencies' => [],
        'name' => $bundle_label_expected,
        'type' => $bundle_expected,
        'description' => NULL,
        'help' => NULL,
        'new_revision' => TRUE,
        'preview_mode' => 1,
        'display_submitted' => TRUE,
      ],
    ];
    $this->assertEquals($data_expected, $data);

    // Validate Metadata attribute.
    $metadata_expected = [
      'default_language' => 'en',
      'dependencies' => $dependencies_expected,
    ];
    $metadata = $cdf->getMetadata();
    unset($metadata['data']);
    $this->assertEquals($metadata_expected, $metadata);

    // Validate Entity type.
    $entity_type = $this->getCdfAttribute($cdf, 'entity_type');
    $this->assertEquals($entity_type, $entity_type);

    $label = $cdf->getAttribute('label');
    $this->assertEquals([
      'en' => $bundle_label_expected,
      'und' => $bundle_label_expected,
    ], $label->getValue());
  }

  /**
   * Validated "node with a text field" CDF object.
   *
   * @param string $title_expected
   *   Expected node title.
   * @param string $bundle_expected
   *   Expected bundle.
   * @param int $status_expected
   *   Expected node publishing status.
   * @param array $field_expected
   *   Expected field value.
   * @param array $field_metadata_expected
   *   Expected field metadata.
   *
   * @see \Drupal\Tests\acquia_contenthub\Kernel\ExportTest::createNodeTypes()
   * @see \Drupal\Tests\acquia_contenthub\Kernel\ExportTest::createTaxonomyVocabulary()
   * @see \Drupal\Tests\acquia_contenthub\Kernel\ExportTest::createTaxonomyTerms()
   */
  protected function validateNodeCdfObject($title_expected, $bundle_expected, $status_expected, array $field_expected = [], array $field_metadata_expected = []) {
    $this->validateBaseCdfObject();

    $cdf = $this->cdfObject;

    // Validate Type attribute.
    $type = $cdf->getType();
    $this->assertEquals('drupal8_content_entity', $type);

    // Validate Metadata attribute.
    $metadata = $cdf->getMetadata();
    $this->assertNotEmpty($metadata['dependencies']['entity']);

    // Validate Metadata's "dependencies" element.
    $uuids = array_keys($metadata['dependencies']['entity']);
    $content_type_uuid = reset($uuids);
    foreach ($uuids as $uuid) {
      $has_node_type_dependency = in_array($uuid, $this->nodeTypeUuids, TRUE);
      $has_taxonomy_vocabulary_dependency = in_array($uuid, $this->vocabularyUuids, TRUE);
      $has_taxonomy_term_dependency = in_array($uuid, $this->termUuids, TRUE);
      $has_user_dependency = in_array($uuid, $this->userUuids, TRUE);
      $has_file_dependency = in_array($uuid, $this->fieldUuids, TRUE);
      $has_valid_dependency = $has_node_type_dependency
        || $has_taxonomy_vocabulary_dependency
        || $has_taxonomy_term_dependency
        || $has_user_dependency
        || $has_file_dependency;
      $this->assertTrue($has_valid_dependency);
    }

    unset($metadata['dependencies']);
    unset($metadata['data']);
    $metadata_expected = [
      'default_language' => 'en',
      'field' => [
        'uuid' => [
          'type' => 'uuid',
        ],
        'langcode' => [
          'type' => 'language',
        ],
        'type' => [
          'type' => 'entity_reference',
          'target' => 'node_type',
        ],
        'revision_timestamp' => [
          'type' => 'created',
        ],
        'revision_uid' => [
          'type' => 'entity_reference',
          'target' => 'user',
        ],
        'revision_log' => [
          'type' => 'string_long',
        ],
        'status' => [
          'type' => 'boolean',
        ],
        'title' => [
          'type' => 'string',
        ],
        'uid' => [
          'type' => 'entity_reference',
          'target' => 'user',
        ],
        'created' => [
          'type' => 'created',
        ],
        'changed' => [
          'type' => 'changed',
        ],
        'promote' => [
          'type' => 'boolean',
        ],
        'sticky' => [
          'type' => 'boolean',
        ],
        'default_langcode' => [
          'type' => 'boolean',
        ],
        'revision_default' => [
          'type' => 'boolean',
        ],
        'revision_translation_affected' => [
          'type' => 'boolean',
        ],
      ],
      'languages' => ['en'],
      'version' => 2,
    ];
    $metadata_expected['field'] = array_merge($metadata_expected['field'], $field_metadata_expected);
    $this->assertEquals($metadata_expected, $metadata);

    // Validate "Data" attribute.
    $data = $this->getCdfDataAttribute($cdf);
    $created_timestamp = $data['created']['value']['en']['value'];
    $is_timestamp = is_numeric($created_timestamp) && (int) $created_timestamp == $created_timestamp;
    $this->assertTrue($is_timestamp);
    unset($data['revision_uid']);
    unset($data['uid']);
    $data_expected = [
      'uuid' => [
        'value' => [
          'en' => [
            'value' => $cdf->getUuid(),
          ],
        ],
      ],
      'type' => [
        'value' => [
          'en' => $content_type_uuid,
        ],
      ],
      'revision_timestamp' => [
        'value' => [
          'en' => [
            'value' => $created_timestamp,
          ],
        ],
      ],
      'revision_log' => [],
      'status' => [
        'value' => [
          'en' => (string) $status_expected,
        ],
      ],
      'title' => [
        'value' => [
          'en' => $title_expected,
        ],
      ],
      'created' => [
        'value' => [
          'en' => [
            'value' => $created_timestamp,
          ],
        ],
      ],
      'changed' => [
        'value' => [
          'en' => [
            'value' => $created_timestamp,
          ],
        ],
      ],
      'promote' => [
        'value' => [
          'en' => '1',
        ],
      ],
      'sticky' => [
        'value' => [
          'en' => '0',
        ],
      ],
      'default_langcode' => [
        'value' => [
          'en' => '1',
        ],
      ],
      'revision_default' => [
        'value' => [
          'en' => '1',
        ],
      ],
      'revision_translation_affected' => [
        'value' => [
          'en' => '1',
        ],
      ],
    ];
    $data_expected = array_merge($data_expected, $field_expected);
    $this->assertEquals($data_expected, $data);

    // Validate entity type.
    $entity_type = $this->getCdfAttribute($cdf, 'entity_type');
    $this->assertEquals('node', $entity_type);

    // Validate bundle.
    $bundle = $this->getCdfAttribute($cdf, 'bundle');
    $this->assertEquals($bundle_expected, $bundle);

    // Validate node title.
    $title = $this->getCdfAttribute($cdf, 'label', 'en');
    $this->assertEquals($title_expected, $title);
  }

  /**
   * Validates Drupal user CDF object.
   *
   * @param string $name_expected
   *   Expected user name.
   * @param string $email_expected
   *   Expected user email.
   */
  protected function validateUserCdfObject($name_expected, $email_expected) {
    $this->validateBaseCdfObject();

    $cdf = $this->cdfObject;

    // Validate Type attribute.
    $type = $cdf->getType();
    $this->assertEquals('drupal8_content_entity', $type);

    // Validate Metadata attribute.
    $metadata = $cdf->getMetadata();
    $metadata_expected = [
      'default_language' => 'en',
      'field' => [
        'uuid' => ['type' => 'uuid'],
        'langcode' => ['type' => 'language'],
        'preferred_langcode' => ['type' => 'language'],
        'preferred_admin_langcode' => ['type' => 'language'],
        'name' => ['type' => 'string'],
        'pass' => ['type' => 'password'],
        'mail' => ['type' => 'email'],
        'timezone' => ['type' => 'string'],
        'status' => ['type' => 'boolean'],
        'created' => ['type' => 'created'],
        'changed' => ['type' => 'changed'],
        'access' => ['type' => 'timestamp'],
        'login' => ['type' => 'timestamp'],
        'init' => ['type' => 'email'],
        'roles' => [
          'type' => 'entity_reference',
          'target' => 'user_role',
        ],
        'default_langcode' => ['type' => 'boolean'],
      ],
      'languages' => ['en'],
      'version' => 2,
      'user_data' => [],
    ];
    unset($metadata['data']);
    unset($metadata['field']['user_picture']);
    $this->assertEquals($metadata_expected, $metadata);

    // Validate "Data" attribute.
    $data = $this->getCdfDataAttribute($cdf);
    $created_timestamp = $data['created']['value']['en']['value'];
    $is_timestamp = is_numeric($created_timestamp) && (int) $created_timestamp == $created_timestamp;
    $this->assertTrue($is_timestamp);
    $data_expected = [
      'uuid' => [
        'value' => [
          'en' => [
            'value' => $cdf->getUuid(),
          ],
        ],
      ],
      'preferred_langcode' => [
        'value' => [
          'en' => 'en',
        ],
      ],
      'preferred_admin_langcode' => [],
      'pass' => [],
      'init' => [],
      'roles' => [
        'value' => [
          'en' => [],
        ],
      ],
      'name' => [
        'value' => [
          'en' => $name_expected,
        ],
      ],
      'mail' => [
        'value' => [
          'en' => [
            'value' => $email_expected,
          ],
        ],
      ],
      'timezone' => [
        'value' => [
          'en' => '',
        ],
      ],
      'status' => [
        'value' => [
          'en' => '0',
        ],
      ],
      'created' => [
        'value' => [
          'en' => [
            'value' => $created_timestamp,
          ],
        ],
      ],
      'changed' => [
        'value' => [
          'en' => [
            'value' => $created_timestamp,
          ],
        ],
      ],
      'access' => [
        'value' => [
          'en' => [
            'value' => '0',
          ],
        ],
      ],
      'login' => [
        'value' => [
          'en' => [
            'value' => '0',
          ],
        ],
      ],
      'default_langcode' => [
        'value' => [
          'en' => '1',
        ],
      ],
    ];
    unset($data['user_picture']);
    $this->assertEquals($data_expected, $data);

    // Validate entity type.
    $entity_type = $this->getCdfAttribute($cdf, 'entity_type');
    $this->assertEquals('user', $entity_type);

    // Validate bundle.
    $bundle = $this->getCdfAttribute($cdf, 'bundle');
    $this->assertEquals('user', $bundle);

    // Validate username.
    $name = $this->getCdfAttribute($cdf, 'username');
    $this->assertEquals($name_expected, $name);
    $label = $this->getCdfAttribute($cdf, 'label', 'en');
    $this->assertEquals($name_expected, $label);

    // Validate email.
    $email = $this->getCdfAttribute($cdf, 'mail');
    $this->assertEquals($email_expected, $email);
  }

  /**
   * Validates taxonomy vocabulary CDF object.
   *
   * @param string $vid_expected
   *   Expected vocabulary id.
   * @param string $name_expected
   *   Expected vocabulary name.
   * @param string $description_expected
   *   Expected vocabulary description.
   * @param int $weight_expected
   *   Expected vocabulary weight.
   */
  protected function validateTaxonomyVocabularyCdfObject($vid_expected, $name_expected, $description_expected, $weight_expected) {
    $this->validateBaseCdfObject();

    $cdf = $this->cdfObject;

    // Validate Type attribute.
    $type = $cdf->getType();
    $this->assertEquals('drupal8_config_entity', $type);

    // Validate Metadata attribute.
    $metadata = $cdf->getMetadata();
    $metadata_expected = [
      'default_language' => 'en',
      'dependencies' => [
        'module' => ['taxonomy'],
      ],
    ];
    unset($metadata['data']);
    $this->assertEquals($metadata_expected, $metadata);

    // Validate "Data" attribute.
    $data = $this->getCdfDataAttribute($cdf);
    $data_expected = [
      'en' => [
        'uuid' => $cdf->getUuid(),
        'langcode' => 'en',
        'status' => TRUE,
        'dependencies' => [],
        'name' => $name_expected,
        'vid' => $vid_expected,
        'description' => $description_expected,
        'hierarchy' => 0,
        'weight' => $weight_expected,
      ],
    ];
    $this->assertEquals($data_expected, $data);

    // Validate entity type.
    $entity_type = $this->getCdfAttribute($cdf, 'entity_type');
    $this->assertEquals('taxonomy_vocabulary', $entity_type);

    // Validate vocabulary name.
    $name = $this->getCdfAttribute($cdf, 'label', 'en');
    $this->assertEquals($name_expected, $name);
  }

  /**
   * Validates taxonomy term CDF object.
   *
   * @param string $name_expected
   *   Expected term name.
   * @param string $bundle_expected
   *   Expected bundle (vocabulary).
   * @param string $description_expected
   *   Expected description.
   */
  protected function validateTaxonomyTermCdfObject($name_expected, $bundle_expected, $description_expected) {
    $this->validateBaseCdfObject();

    $cdf = $this->cdfObject;

    // Validate Type attribute.
    $type = $cdf->getType();
    $this->assertEquals('drupal8_content_entity', $type);

    // Validate Metadata attribute.
    $metadata = $cdf->getMetadata();
    $this->assertNotEmpty($metadata['dependencies']['entity']);
    $vocabulary_uuid = key($metadata['dependencies']['entity']);
    unset($metadata['dependencies']);
    $metadata_expected = [
      'default_language' => 'en',
      'field' => [
        'uuid' => [
          'type' => 'uuid',
        ],
        'langcode' => [
          'type' => 'language',
        ],
        'vid' => [
          'type' => 'entity_reference',
          'target' => 'taxonomy_vocabulary',
        ],
        'status' => [
          'type' => 'boolean',
        ],
        'name' => [
          'type' => 'string',
        ],
        'description' => [
          'type' => 'text_long',
        ],
        'weight' => [
          'type' => 'integer',
        ],
        'parent' => [
          'type' => 'entity_reference',
          'target' => 'taxonomy_term',
        ],
        'changed' => [
          'type' => 'changed',
        ],
        'default_langcode' => [
          'type' => 'boolean',
        ],
      ],
      'languages' => ['en'],
      'version' => 2,
    ];
    unset($metadata['data']);
    $this->assertEquals($metadata_expected, $metadata);

    // Validate "Data" attribute.
    $data = $this->getCdfDataAttribute($cdf);
    $changed_timestamp = $data['changed']['value']['en']['value'];
    $is_timestamp = is_numeric($changed_timestamp) && (int) $changed_timestamp == $changed_timestamp;
    $this->assertTrue($is_timestamp);
    $data_expected = [
      'uuid' => [
        'value' => [
          'en' => [
            'value' => $cdf->getUuid(),
          ],
        ],
      ],
      'parent' => [],
      'vid' => [
        'value' => [
          'en' => $vocabulary_uuid,
        ],
      ],
      'status' => [
        'value' => [
          'en' => '1',
        ],
      ],
      'name' => [
        'value' => [
          'en' => $name_expected,
        ],
      ],
      'description' => [
        'field_type' => 'text_long',
        'value' => [
          'en' => [['value' => $description_expected, 'format' => NULL]],
        ],
      ],
      'weight' => [
        'value' => [
          'en' => '0',
        ],
      ],
      'changed' => [
        'value' => [
          'en' => [
            'value' => $changed_timestamp,
          ],
        ],
      ],
      'default_langcode' => [
        'value' => [
          'en' => '1',
        ],
      ],
    ];
    $this->assertEquals($data_expected, $data);

    // Validate entity type.
    $entity_type = $this->getCdfAttribute($cdf, 'entity_type');
    $this->assertEquals('taxonomy_term', $entity_type);

    // Validate bundle.
    $bundle = $this->getCdfAttribute($cdf, 'bundle');
    $this->assertEquals($bundle_expected, $bundle);

    // Validate term name.
    $name = $this->getCdfAttribute($cdf, 'label', 'en');
    $this->assertEquals($name_expected, $name);
  }

  /**
   * Validates field storage CDF object.
   *
   * @param string $name_expected
   *   Expected field name.
   * @param string $type_expected
   *   Expected field type.
   * @param array $dependencies_expected
   *   List of expected dependencies (modules).
   * @param array $settings_expected
   *   Expected specific field settings.
   * @param int $cardinality_expected
   *   Expected field cardinality.
   */
  protected function validateFieldStorageCdfObject($name_expected, $type_expected, array $dependencies_expected, array $settings_expected, $cardinality_expected) {
    $this->validateBaseCdfObject();

    $cdf = $this->cdfObject;
    $id_expected = 'node.' . $name_expected;

    // Validate Type attribute.
    $type = $cdf->getType();
    $this->assertEquals('drupal8_config_entity', $type);

    // Validate Metadata attribute.
    $metadata = $cdf->getMetadata();
    $metadata_expected = [
      'default_language' => 'en',
      'dependencies' => [
        'module' => array_merge($dependencies_expected, ['field']),
      ],
    ];
    unset($metadata['data']);
    $this->assertEquals($metadata_expected, $metadata);

    // Validate "Data" attribute.
    $data = $this->getCdfDataAttribute($cdf);
    $data_expected = [
      'en' => [
        'uuid' => $cdf->getUuid(),
        'langcode' => 'en',
        'status' => TRUE,
        'dependencies' => [
          'module' => $dependencies_expected,
        ],
        'id' => $id_expected,
        'field_name' => $name_expected,
        'entity_type' => 'node',
        'type' => $type_expected,
        'settings' => $settings_expected,
        'module' => 'core',
        'locked' => FALSE,
        'cardinality' => $cardinality_expected,
        'translatable' => TRUE,
        'indexes' => [],
        'persist_with_no_fields' => FALSE,
        'custom_storage' => FALSE,
      ],
    ];
    $this->assertEquals($data_expected, $data);

    // Validate entity type.
    $entity_type = $this->getCdfAttribute($cdf, 'entity_type');
    $this->assertEquals('field_storage_config', $entity_type);

    // Validate field id.
    $id = $this->getCdfAttribute($cdf, 'label', 'en');
    $this->assertEquals($id_expected, $id);
  }

  /**
   * Validates field CDF object.
   *
   * @param string $id_expected
   *   Expected field id.
   * @param array $dependencies_expected
   *   Expected list of dependencies (modules).
   * @param string $type_expected
   *   Expected field type.
   * @param string $bundle_expected
   *   Expected bundle.
   * @param array $settings_expected
   *   Expected list of specific settings.
   *
   * @see \Drupal\Tests\acquia_contenthub\Kernel\ExportTest::createFieldStorages()
   * @see \Drupal\Tests\acquia_contenthub\Kernel\ExportTest::createNodeTypes()
   */
  protected function validateFieldCdfObject($id_expected, array $dependencies_expected, $type_expected, $bundle_expected, array $settings_expected) {
    $this->validateBaseCdfObject();

    $cdf = $this->cdfObject;

    // Validate Type attribute.
    $type = $cdf->getType();
    $this->assertEquals('drupal8_config_entity', $type);

    // Validate Metadata attribute.
    $metadata = $cdf->getMetadata();
    $this->assertNotEmpty($metadata['dependencies']['entity']);
    $uuids = array_keys($metadata['dependencies']['entity']);
    foreach ($uuids as $uuid) {
      $has_field_dependency = in_array($uuid, $this->fieldUuids, TRUE);
      $has_node_type_dependency = in_array($uuid, $this->nodeTypeUuids, TRUE);
      $has_valid_dependency = $has_field_dependency || $has_node_type_dependency;
      $this->assertTrue($has_valid_dependency);
    }
    unset($metadata['dependencies']['entity']);
    $metadata_expected = [
      'default_language' => 'en',
      'dependencies' => [
        'module' => array_merge($dependencies_expected, ['field']),
      ],
    ];
    unset($metadata['data']);
    $this->assertEquals($metadata_expected, $metadata);

    // Validate "Data" attribute.
    $data = $this->getCdfDataAttribute($cdf);
    $data_expected = [
      'en' => [
        'uuid' => $cdf->getUuid(),
        'langcode' => 'en',
        'status' => TRUE,
        'dependencies' => [
          'config' => [
            'field.storage.node.' . $id_expected,
            'node.type.' . $bundle_expected,
          ],
        ],
        'id' => 'node.test_content_type.' . $id_expected,
        'field_name' => $id_expected,
        'entity_type' => 'node',
        'bundle' => $bundle_expected,
        'label' => $id_expected,
        'description' => '',
        'required' => FALSE,
        'translatable' => TRUE,
        'default_value' => [],
        'default_value_callback' => '',
        'settings' => $settings_expected,
        'field_type' => $type_expected,
      ],
    ];
    $this->assertEquals($data_expected, $data);

    // Validate entity type.
    $entity_type = $this->getCdfAttribute($cdf, 'entity_type');
    $this->assertEquals('field_config', $entity_type);

    // Validate field id.
    $id = $this->getCdfAttribute($cdf, 'label', 'en');
    $this->assertEquals($id_expected, $id);
  }

  /**
   * Validates file CDFObject.
   *
   * @param string $filename_expected
   *   Expected file name.
   * @param string $uri_expected
   *   Expected file URI.
   * @param string $filemime_expected
   *   Expected file type (mime).
   */
  protected function validateFileCdfObject($filename_expected, $uri_expected, $filemime_expected) {
    $this->validateBaseCdfObject();

    $cdf = $this->cdfObject;

    // Validate Type attribute.
    $type = $cdf->getType();
    $this->assertEquals('drupal8_content_entity', $type);

    // Validate Metadata attribute.
    $metadata = $cdf->getMetadata();
    $this->assertNotEmpty($metadata['dependencies']['entity']);
    $uuids = array_keys($metadata['dependencies']['entity']);
    $owner_uuid = reset($uuids);
    $has_user_dependency = in_array($owner_uuid, $this->userUuids, TRUE);
    $this->assertTrue($has_user_dependency);

    unset($metadata['dependencies']);
    $metadata_expected = [
      'default_language' => 'en',
      'field' => [
        'uuid' => ['type' => 'uuid'],
        'langcode' => ['type' => 'language'],
        'uid' => [
          'type' => 'entity_reference',
          'target' => 'user',
        ],
        'filename' => ['type' => 'string'],
        'uri' => ['type' => 'file_uri'],
        'filemime' => ['type' => 'string'],
        'filesize' => ['type' => 'integer'],
        'status' => ['type' => 'boolean'],
        'created' => ['type' => 'created'],
        'changed' => ['type' => 'changed'],
      ],
      'languages' => ['en'],
      'version' => 2,
    ];
    unset($metadata['data']);
    $this->assertEquals($metadata_expected, $metadata);

    // Validate "Data" attribute.
    $data = $this->getCdfDataAttribute($cdf);
    $created_timestamp = $data['created']['value']['en']['value'];
    $is_timestamp = is_numeric($created_timestamp) && (int) $created_timestamp == $created_timestamp;
    $this->assertTrue($is_timestamp);
    $data_expected = [
      'uuid' => [
        'value' => [
          'en' => [
            'value' => $cdf->getUuid(),
          ],
        ],
      ],
      'uid' => [
        'value' => [
          'en' => [$owner_uuid],
        ],
      ],
      'filename' => [
        'value' => [
          'en' => $filename_expected,
        ],
      ],
      'uri' => [
        'value' => [
          'en' => [
            'value' => $uri_expected,
          ],
        ],
      ],
      'filemime' => [
        'value' => ['en' => $filemime_expected],
      ],
      'filesize' => [],
      'status' => [
        'value' => ['en' => '1'],
      ],
      'created' => [
        'value' => [
          'en' => ['value' => $created_timestamp],
        ],
      ],
      'changed' => [
        'value' => [
          'en' => ['value' => $created_timestamp],
        ],
      ],
    ];
    $this->assertEquals($data_expected, $data);

    // Validate entity type.
    $entity_type = $this->getCdfAttribute($cdf, 'entity_type');
    $this->assertEquals('file', $entity_type);

    // Validate entity bundle.
    $entity_type = $this->getCdfAttribute($cdf, 'bundle');
    $this->assertEquals('file', $entity_type);

    // Validate file name.
    $filename = $this->getCdfAttribute($cdf, 'label', 'en');
    $this->assertEquals($filename_expected, $filename);

    // Validate file location.
    $file_location = $this->getCdfAttribute($cdf, 'file_location');

    /** @var \Drupal\Core\StreamWrapper\LocalStream $stream_wrapper */
    $stream_wrapper = $this->streamWrapperManager->getViaUri($uri_expected);
    $directory_path = $stream_wrapper->getDirectoryPath();
    $file_location_expected = Url::fromUri('base:' . $directory_path . '/' . file_uri_target($uri_expected), ['absolute' => TRUE])->toString();
    $this->assertEquals($file_location_expected, $file_location);

    // Validate file location.
    $file_scheme = $this->getCdfAttribute($cdf, 'file_scheme');
    $this->assertEquals('public', $file_scheme);

    // Validate file URI.
    $file_uri = $this->getCdfAttribute($cdf, 'file_uri');
    $this->assertEquals($uri_expected, $file_uri);
  }

  /**
   * Returns CDF Attribute value.
   *
   * @param \Acquia\ContentHubClient\CDF\CDFObject $cdf
   *   CDF Object.
   * @param string $name
   *   Attribute name.
   * @param string $langcode
   *   Language code.
   *
   * @return mixed
   *   Attribute's value.
   */
  protected function getCdfAttribute(CDFObject $cdf, $name, $langcode = LanguageInterface::LANGCODE_NOT_SPECIFIED) {
    $attribute = $cdf->getAttribute($name)->getValue();
    if (!isset($attribute[$langcode])) {
      return NULL;
    }

    return $attribute[$langcode];
  }

  /**
   * Returns decoded value of CDFDataAttribute object.
   *
   * @param \Acquia\ContentHubClient\CDF\CDFObject $cdf
   *   CDF Object.
   *
   * @return mixed
   *   Decoded value.
   */
  protected function getCdfDataAttribute(CDFObject $cdf) {
    $metadata = $cdf->getMetadata();
    if (!isset($metadata['data'])) {
      return [];
    }
    $data = base64_decode($metadata['data']);

    return Yaml::decode($data);
  }

  /**
   * Updates the authored node.
   *
   * @param int $nid
   *   Node id.
   * @param int $items_expected
   *   Expected number of items in the queue.
   * @param array $cdf_expectations
   *   The sets of expectation arguments for CDF object validation.
   *
   * @see \Drupal\Tests\acquia_contenthub\Kernel\ExportTest::testPublishingUserProfile
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function updateAuthoredNode($nid, &$items_expected, array &$cdf_expectations = []) {
    $title = $this->randomString(30);
    $node = Node::load($nid);
    $node->set('title', $title);
    $node->save();

    $items_expected = 1;
    $cdf_expectations = [
      [$title, 'test_content_type', Node::PUBLISHED, [], []],
    ];
  }

}
