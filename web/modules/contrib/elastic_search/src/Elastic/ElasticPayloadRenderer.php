<?php
/**
 * Created by PhpStorm.
 * User: twhiston
 * Date: 01.06.17
 * Time: 19:21
 */

namespace Drupal\elastic_search\Elastic;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\elastic_search\Entity\ElasticIndexInterface;
use Drupal\elastic_search\Entity\FieldableEntityMap;
use Drupal\elastic_search\Entity\FieldableEntityMapInterface;
use Drupal\elastic_search\Exception\ElasticDocumentManagerRecursionException;
use Drupal\elastic_search\Exception\FieldMapperFlattenSkipException;
use Drupal\elastic_search\Exception\IndexNotFoundException;
use Drupal\elastic_search\Exception\MapNotFoundException;
use Drupal\elastic_search\Plugin\FieldMapperManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ElasticPayloadRenderer
 *
 * @package Drupal\elastic_search\Elastic
 */
class ElasticPayloadRenderer implements ElasticPayloadRendererInterface, ContainerInjectionInterface {

  /***
   * @var EntityTypeManagerInterface $entityTypeManage
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\elastic_search\Plugin\FieldMapperManager
   */
  protected $fieldMapperManager;

  /**
   * @var
   */
  private $currentDepth = 0;

  /**
   * @var
   */
  private $activeMaxDepth = 1;

  /**
   * ElasticPayloadRenderer constructor.
   *
   * @param EntityTypeManagerInterface                       $entityTypeManager
   * @param \Drupal\elastic_search\Plugin\FieldMapperManager $fieldMapperManager
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager,
                              FieldMapperManager $fieldMapperManager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->fieldMapperManager = $fieldMapperManager;
  }

  /**
   * @inheritDoc
   */
  public static function create(ContainerInterface $container) {
    return new self($container->get('entity_type.manager'),
                    $container->get('plugin.manager.elastic_field_mapper_plugin'));
  }

  /**
   * @inheritDoc
   *
   * @throws \Drupal\elastic_search\Exception\ElasticDocumentBuilderSkipException
   * @throws \Drupal\elastic_search\Exception\IndexNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\elastic_search\Exception\ElasticDocumentManagerRecursionException
   * @throws \Drupal\elastic_search\Exception\MapNotFoundException
   */
  public function buildDocumentPayload(EntityInterface $entity): array {

    $fem = $this->getFemFromEntity($entity);

    /** @var ElasticIndexInterface[] $indices */
    $indices = $this->getIndices($fem->id(), $entity->language()->getId());
    if (empty($indices)) {
      throw new IndexNotFoundException('No Indices were found for processing by Elastic Document Manager');
    }

    /** @var ElasticIndexInterface $firstIndex */
    $firstIndex = reset($indices);

    $mapData = $this->getMapping($entity, $fem, $firstIndex);

    return $this->createIndexPayload($mapData, $indices, $entity->uuid());
  }

  /**
   * @inheritDoc
   *
   * @throws \Drupal\elastic_search\Exception\ElasticDocumentBuilderSkipException
   * @throws \Drupal\elastic_search\Exception\IndexNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\elastic_search\Exception\ElasticDocumentManagerRecursionException
   * @throws \Drupal\elastic_search\Exception\MapNotFoundException
   */
  public function buildDeletePayload(EntityInterface $entity): array {

    $fem = $this->getFemFromEntity($entity);

    /** @var ElasticIndexInterface[] $indices */
    $indices = $this->getIndices($fem->id(), $entity->language()->getId());
    if (empty($indices)) {
      throw new IndexNotFoundException('No Indices were found for processing by Elastic Document Manager');
    }

    return $this->createDeletePayload($indices, $entity->uuid());
  }

  /**
   * @inheritDoc
   *
   * @throws \Drupal\elastic_search\Exception\ElasticDocumentBuilderSkipException
   * @throws \Drupal\elastic_search\Exception\IndexNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\elastic_search\Exception\ElasticDocumentManagerRecursionException
   * @throws \Drupal\elastic_search\Exception\MapNotFoundException
   */
  public function buildDeleteOrphanPayload(string $uuid, string $language, FieldableEntityMapInterface $fem): array {

    /** @var ElasticIndexInterface[] $indices */
    $indices = $this->getIndices($fem->id(), $language);
    if (empty($indices)) {
      throw new IndexNotFoundException('No Indices were found for processing by Elastic Document Manager. FEM:' .
                                       $fem->id() . ' Language:' . $language);
    }

    return $this->createDeletePayload($indices, $uuid);
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface                       $entity
   * @param \Drupal\elastic_search\Entity\FieldableEntityMapInterface $fem
   * @param \Drupal\elastic_search\Entity\ElasticIndexInterface       $index
   *
   * @return array
   *
   * @throws \Drupal\elastic_search\Exception\MapNotFoundException
   * @throws \Drupal\elastic_search\Exception\ElasticDocumentManagerRecursionException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function getMapping(EntityInterface $entity, FieldableEntityMapInterface $fem, ElasticIndexInterface $index) {
    $mappingEntityId = $index->getMappingEntityId();
    $fieldData = $entity->toArray();
    return $this->executeDocumentMappingProcess($mappingEntityId, $fieldData);

  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @return \Drupal\elastic_search\Entity\FieldableEntityMapInterface
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function getFemFromEntity(EntityInterface $entity): FieldableEntityMapInterface {
    return $this->getFemFromEntityId($entity->getEntityTypeId(), $entity->bundle());
  }

  protected function getFemFromEntityId(string $type, string $bundle): FieldableEntityMapInterface {

    $femName = FieldableEntityMap::getMachineName($type, $bundle);
    /** @var \Drupal\elastic_search\Entity\FieldableEntityMapInterface $fem */
    $fem = $this->entityTypeManager->getStorage('fieldable_entity_map')->load($femName);
    return $fem;
  }

  /**
   * @param string $femName
   * @param string $language
   *
   * @return \Drupal\elastic_search\Entity\ElasticIndexInterface[]
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function getIndices(string $femName, string $language): array {

    /** @var \Drupal\Core\Entity\EntityStorageInterface $storage */
    $indexStorage = $this->entityTypeManager->getStorage('elastic_index');
    /** @var \Drupal\elastic_search\Entity\ElasticIndexInterface[] $indices */
    $indices = $indexStorage->loadByProperties([
                                                 'mappingEntityId' => $femName,
                                                 'indexLanguage'   => $language,
                                               ]);
    return $indices;
  }

  /**
   * Builds a map, called from buildDocumentPayload
   *
   * @param string $mapId
   * @param array  $fieldData
   *
   * @return array
   *
   * @throws \Drupal\elastic_search\Exception\MapNotFoundException
   * @throws \Drupal\elastic_search\Exception\ElasticDocumentManagerRecursionException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function executeDocumentMappingProcess(string $mapId, array $fieldData): array {

    $this->currentDepth = 0;
    $fieldableEntityMapStorage = $this->entityTypeManager->getStorage('fieldable_entity_map');
    /** @var \Drupal\elastic_search\Entity\FieldableEntityMapInterface $fieldMap */
    $fieldMap = $fieldableEntityMapStorage->load($mapId);
    $this->activeMaxDepth = $fieldMap->getRecursionDepth();

    return $this->buildDataFromMap($mapId, $fieldData);
  }

  /**
   * Normalizes data from an entity by using field mapper plugins
   * Public to allow field mappers to call it back and start a recursive mapping process that respects index recursion
   * depth but it should not be called outside of this context
   *
   * @param string $mapId
   * @param array  $fieldData
   *
   * @return array
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\elastic_search\Exception\ElasticDocumentManagerRecursionException
   * @throws \Drupal\elastic_search\Exception\MapNotFoundException
   */
  public function buildDataFromMap(string $mapId, array $fieldData) {

    $fieldableEntityMapStorage = $this->entityTypeManager->getStorage('fieldable_entity_map');
    /** @var \Drupal\elastic_search\Entity\FieldableEntityMapInterface $fieldMap */
    $fieldMap = $fieldableEntityMapStorage->load($mapId);

    $output = [];

    if ($this->currentDepth > $this->activeMaxDepth) {
      //If we recurse above our max limit, throw an exception, which we catch below, and process as a simple_reference instead
      throw new ElasticDocumentManagerRecursionException('nesting depth exceeded');
    }

    ++$this->currentDepth;

    if (!$fieldMap) {
      throw new MapNotFoundException('Map not found: ' . $mapId);
    }
    $fieldMappingData = $fieldMap->getFields();

    foreach ($fieldData as $id => $data) {
      if (array_key_exists($id, $fieldMappingData)) {
        try {
          /** @var \Drupal\elastic_search\Plugin\FieldMapperInterface $fieldMapper */
          $fieldMapper = $this->fieldMapperManager->createInstance($fieldMappingData[$id]['map'][0]['type']);
          try {
            if (array_key_exists('langcode', $fieldData)) {
              $fieldMappingData[$id]['langcode'] = $fieldData['langcode'][0]['value'];
            }
            if ($fieldMappingData[$id]['map'][0]['type'] !== 'none') {
              $flattened = $fieldMapper->normalizeFieldData($id,
                                                            $data,
                                                            $fieldMappingData[$id]);
            } else {
              throw new FieldMapperFlattenSkipException;
            }
          } catch (ElasticDocumentManagerRecursionException $e) {
            /** @var \Drupal\elastic_search\Plugin\FieldMapperInterface $fieldMapper */
            $fieldMapper = $this->fieldMapperManager->createInstance('simple_reference');
            $flattened = $fieldMapper->normalizeFieldData($id,
                                                          $data,
                                                          $fieldMappingData[$id]);

          }
          $output[$id] = $flattened;
        } catch (FieldMapperFlattenSkipException $e) {
          //Do nothing if this type of exception is thrown as it means skip adding the data
          continue;
        }
      }
    }
    --$this->currentDepth;
    return $output;
  }

  /**
   * Builds the literal output array for use with the bulk endpoint
   *
   * @param array  $mapData
   * @param array  $indices
   * @param string $uuid
   *
   * @return array
   */
  public function createIndexPayload(array $mapData,
                                     array $indices,
                                     string $uuid): array {
    $payloads = ['body' => []];
    foreach ($indices as $index) {
      $payloads['body'][] = [
        'index' => [
          '_index' => $index->getIndexName(),
          '_type'  => $index->getIndexId(),
          '_id'    => $uuid,
        ],
      ];
      $payloads['body'][] = $mapData;
    }
    return $payloads;
  }

  /**
   * TODO - is this the best way to do this? do we ever map the same data to multiple indices?
   * Builds the literal output array for use with the bulk endpoint
   *
   * @param ElasticIndexInterface[] $indices
   * @param string                  $uuid
   *
   * @return array
   */
  public function createDeletePayload(array $indices,
                                      string $uuid): array {
    $payloads = ['body' => []];
    foreach ($indices as $index) {
      $payloads['body'][] = [
        'delete' => [
          '_index' => $index->getIndexName(),
          '_type'  => $index->getIndexId(),
          '_id'    => $uuid,
        ],
      ];
    }
    return $payloads;
  }

}