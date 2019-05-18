<?php

namespace Drupal\Tests\acquia_contenthub\Kernel;

use Acquia\ContentHubClient\CDFDocument;
use Acquia\ContentHubClient\CDF\CDFObject;
use Acquia\ContentHubClient\ContentHubClient;
use Drupal\acquia_contenthub\Client\ClientFactory;
use Drupal\acquia_contenthub_subscriber\Plugin\QueueWorker\ContentHubImportQueueWorker;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\acquia_contenthub\Kernel\Stubs\DrupalVersion;

/**
 * Class UnserializationTest.
 *
 * @group acquia_contenthub
 *
 * @package Drupal\Tests\acquia_contenthub\Kernel
 */
class UnserializationTest extends EntityKernelTestBase {

  use DrupalVersion;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'user',
    'file',
    'node',
    'field',
    'taxonomy',
    'depcalc',
    'acquia_contenthub',
    'acquia_contenthub_subscriber',
  ];

  /**
   * Queue worker instance.
   *
   * @var \Drupal\acquia_contenthub_subscriber\Plugin\QueueWorker\ContentHubImportQueueWorker
   */
  protected $contentHubImportQueueWorker;

  /**
   * Client instance.
   *
   * @var \Acquia\ContentHubClient\ContentHubClient|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $contentHubClient;

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('taxonomy_term');
    $this->installSchema('acquia_contenthub_subscriber', ['acquia_contenthub_subscriber_import_tracking']);

    $this->contentHubClient = $this
      ->getMockBuilder(ContentHubClient::class)
      ->disableOriginalConstructor()
      ->getMock();

    $client_factory_mock = $this
      ->getMockBuilder(ClientFactory::class)
      ->disableOriginalConstructor()
      ->getMock();
    $client_factory_mock
      ->method('getClient')
      ->willReturn($this->contentHubClient);
    $this->container->set('acquia_contenthub.client.factory', $client_factory_mock);

    $this->contentHubImportQueueWorker = $this->getMockBuilder(ContentHubImportQueueWorker::class)
      ->setConstructorArgs([
        $this->container->get('acquia_contenthub_common_actions'),
        $this->container->get('acquia_contenthub.client.factory'),
        $this->container->get('config.factory'),
        $this->container->get('logger.factory'),
        [],
        NULL,
        NULL,
      ])
      ->setMethods(NULL)
      ->getMock();
  }

  /**
   * Tests configuration entity unserialization.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \ReflectionException
   */
  public function testConfigEntityUnserialization() {
    $cdf_document = $this->createCDFDocumentFromFixture('view_modes.json');

    $this->contentHubClient
      ->method('getEntities')
      ->willReturn($cdf_document);

    $item = new \stdClass();
    $item->uuids = implode(', ', ['fefd7eda-4244-4fe4-b9b5-b15b89c61aa8']);
    $this->contentHubImportQueueWorker->processItem($item);
    $view_mode = EntityViewMode::load('node.teaser');

    $this->assertNotEmpty($view_mode->id());
  }

  /**
   * Tests content entity unserialization.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \ReflectionException
   *
   * @see _acquia_contenthub_publisher_enqueue_entity()
   */
  public function testTaxonomyTermUnserialization() {
    $cdf_document = $this->createCDFDocumentFromFixture('taxonomy.json');

    $this->contentHubClient
      ->method('getEntities')
      ->willReturn($cdf_document);

    $item = new \stdClass();
    $item->uuids = implode(', ', ['de9606dc-56fa-4b09-bcb1-988533edc814']);
    $this->contentHubImportQueueWorker->processItem($item);

    // Checks that vocabulary has been imported.
    $vocabulary = Vocabulary::load('tags');
    $this->assertNotEmpty($vocabulary->id());
    $this->assertEquals('Tags', $vocabulary->label());

    // Checks that taxonomy has been imported.
    /** @var \Drupal\taxonomy\Entity\Term[] $taxonomy_terms */
    $taxonomy_terms = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadByProperties(['name' => 'tag1']);
    $this->assertNotEmpty($taxonomy_terms);

    $taxonomy_term = current($taxonomy_terms);
    $this->assertNotEmpty($taxonomy_term->id());
  }

  /**
   * Creates CDF document from fixture.
   *
   * @param string $fixture_filename
   *   Fixture file name.
   *
   * @return \Acquia\ContentHubClient\CDFDocument
   *   CDF document.
   *
   * @throws \ReflectionException
   */
  protected function createCdfDocumentFromFixture($fixture_filename): CDFDocument {
    $version_directory = $this->getDrupalVersion();
    $path_to_fixture = sprintf("%s/tests/fixtures/import/$version_directory/%s",
      drupal_get_path('module', 'acquia_contenthub'),
      $fixture_filename
    );
    $json = file_get_contents($path_to_fixture);
    $data = Json::decode($json);
    $document_parts = [];
    foreach ($data['entities'] as $entity) {
      $document_parts[] = $this->populateCdfObject($entity);
    }

    $cdf_document = new CDFDocument(...$document_parts);

    return $cdf_document;
  }

  /**
   * Populates CDF object from array.
   *
   * @param array $entity
   *   Entity.
   *
   * @return \Acquia\ContentHubClient\CDF\CDFObject
   *   Populated CDF object.
   *
   * @throws \Exception
   * @throws \ReflectionException
   *
   * @see \Acquia\ContentHubClient\ContentHubClient::getEntities()
   */
  protected function populateCdfObject(array $entity): CDFObject {
    $object = new CDFObject($entity['type'], $entity['uuid'], $entity['created'], $entity['modified'], $entity['origin'], $entity['metadata']);

    foreach ($entity['attributes'] as $attribute_name => $values) {
      // Refactor ClientHub.php: get rid of duplicated code blocks.
      if (!$attribute = $object->getAttribute($attribute_name)) {
        $class = !empty($object->getMetadata()['attributes'][$attribute_name]) ? $object->getMetadata()['attributes'][$attribute_name]['class'] : FALSE;
        if ($class && class_exists($class)) {
          $object->addAttribute($attribute_name, $values['type'], NULL, 'und', $class);
        }
        else {
          $object->addAttribute($attribute_name, $values['type'], NULL);
        }
        $attribute = $object->getAttribute($attribute_name);
      }

      $value_property = (new \ReflectionClass($attribute))->getProperty('value');
      $value_property->setAccessible(TRUE);
      $value_property->setValue($attribute, $values['value']);
    }

    return $object;
  }

}
