<?php

namespace Drupal\elastic_search\Elastic;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\elastic_search\Entity\ElasticIndexInterface;
use Drupal\elastic_search\Entity\FieldableEntityMap;
use Drupal\elastic_search\Entity\FieldableEntityMapInterface;
use Drupal\elastic_search\Mapping\Cartographer;
use Drupal\elastic_search\Mapping\ElasticMappingDslGenerator;
use Elasticsearch\Client;
use GuzzleHttp\Ring\Future\FutureArray;
use ONGR\ElasticsearchDSL\Query\MatchAllQuery;
use ONGR\ElasticsearchDSL\Search;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ElasticIndexManager
 *
 * @package Drupal\elastic_search\Elastic
 */
class ElasticIndexManager implements ContainerInjectionInterface {

  /**
   * @var EntityStorageInterface
   */
  protected $indexStorage;

  /**
   * @var EntityStorageInterface
   */
  protected $fieldableEntityMapStorage;

  /**
   * @var Client
   */
  protected $client;

  /**
   * @var Cartographer
   */
  protected $cartographer;

  /**
   * @var ElasticIndexGenerator
   */
  protected $indexGenerator;

  /**
   * @var ElasticMappingDslGenerator
   */
  protected $dslMappingGenerator;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var ElasticDocumentManager
   */
  protected $documentManager;

  /**
   * ElasticIndexManager constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface            $entityTypeManager
   * @param \Elasticsearch\Client                                     $client
   * @param \Drupal\elastic_search\Mapping\Cartographer               $cartographer
   * @param \Drupal\elastic_search\Elastic\ElasticIndexGenerator      $indexGenerator
   * @param \Drupal\elastic_search\Mapping\ElasticMappingDslGenerator $dslMappingGenerator
   * @param \Drupal\elastic_search\Elastic\ElasticDocumentManager     $documentManager
   *
   * @internal param \Drupal\Core\Entity\EntityStorageInterface $indexStorage
   * @internal param \Drupal\Core\Entity\EntityStorageInterface
   *   $fieldableEntityMapStorage
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager,
                              Client $client,
                              Cartographer $cartographer,
                              ElasticIndexGenerator $indexGenerator,
                              ElasticMappingDslGenerator $dslMappingGenerator,
                              ElasticDocumentManager $documentManager
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->indexStorage = $entityTypeManager->getStorage('elastic_index');
    $this->fieldableEntityMapStorage = $entityTypeManager->getStorage('fieldable_entity_map');
    $this->client = $client;
    $this->cartographer = $cartographer;
    $this->indexGenerator = $indexGenerator;
    $this->dslMappingGenerator = $dslMappingGenerator;
    $this->documentManager = $documentManager;
  }

  /**
   * @inheritDoc
   *
   * @throws \Exception
   */
  public static function create(ContainerInterface $container) {
    return new self($container->get('entity_type.manager'),
                    $container->get('elastic_search.connection_factory')
                              ->getElasticConnection(),
                    $container->get('elastic_search.mapping.cartographer'),
                    $container->get('elastic_search.indices.generator'),
                    $container->get('elastic_search.mapping.dsl_generator'),
                    $container->get('elastic_search.document.manager'));
  }

  /**
   * @param \Drupal\elastic_search\Entity\FieldableEntityMapInterface $fieldableEntityMap
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function markIndexForServerUpdateFromFieldMap(FieldableEntityMapInterface $fieldableEntityMap) {

    $indices = $this->indexStorage->loadByProperties(['mappingEntityId' => $fieldableEntityMap->id()]);
    $this->markIndicesForServerUpdate($indices);
  }

  /**
   * @param ElasticIndexInterface[] $indices
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function markIndicesForServerUpdate(array $indices) {

    /** @var \Drupal\elastic_search\Entity\ElasticIndex $index */
    foreach ($indices as $index) {
      $index->setNeedsUpdate();
      $index->save();
    }
  }

  /**
   * @param \Drupal\elastic_search\Entity\ElasticIndexInterface $elasticIndex
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function markIndexAsUpdated(ElasticIndexInterface $elasticIndex) {
    $elasticIndex->setNeedsUpdate(FALSE);
    $elasticIndex->save();
  }

  /**
   * @param \Drupal\elastic_search\Entity\ElasticIndexInterface $elasticIndex
   *
   * @return array
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Exception
   */
  public function deleteIndexOnServer(ElasticIndexInterface $elasticIndex): FutureArray {
    /** @var \GuzzleHttp\Ring\Future\FutureArray $result */
    $future = $this->deleteRemoteIndex($elasticIndex);
    $this->markIndicesForServerUpdate([$elasticIndex]);
    $state = $future['acknowledged']; //this forces the deletion call to be resolved
    return $future;//this is actually an array at this point due to the resolution
  }

  /**
   * @param \Drupal\elastic_search\Entity\ElasticIndexInterface $elasticIndex
   *
   * @return \GuzzleHttp\Ring\Future\FutureArray
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function deleteRemoteIndex(ElasticIndexInterface $elasticIndex): FutureArray {
    /** @var \GuzzleHttp\Ring\Future\FutureArray $result */
    $result = $this->client->indices()
                           ->delete([
                                      'index'  => $elasticIndex->getIndexName(),
                                      'client' => ['timeout' => 5, 'future' => 'lazy'],
                                    ]);
    $this->markIndicesForServerUpdate([$elasticIndex]);
    return $result;
  }

  /**
   * @param ElasticIndexInterface $elasticIndex
   *
   * @return bool
   *
   * @throws \Exception
   */
  public function updateIndexOnServer(ElasticIndexInterface $elasticIndex) {
    //No futures
    $index = ['index' => $elasticIndex->getIndexName(), 'client' => ['timeout' => 5]];
    $exists = $this->client->indices()->exists($index);
    $index = ['index' => $elasticIndex->getIndexName(), 'client' => ['timeout' => 5, 'future' => 'lazy']];
    if ($exists) {
      //If the index exists then just delete and recreate it.
      //TODO - Next version some work will be done around this
      try {
        $result = $this->client->indices()->delete($index);
        //Resolve the delete future to see if we can continue
        $dstate = ($result['acknowledged'] || $result['shards_acknowledged']);
      } catch (\Throwable $t) {
        drupal_set_message(json_encode($t->getMessage()),'error');
        return FALSE;
      }
    }
    $mapId = $elasticIndex->getMappingEntityId();
    $mapped = $this->dslMappingGenerator->generate([$mapId]);
    $mapped = $this->dslMappingGenerator->triggerTokenReplacement($mapped,
                                                                  $elasticIndex->getIndexLanguage());
    $index += [
      'body' => reset($mapped),
      //TODO - will we ever actually get multiples here?
    ];
    $result = $this->client->indices()->create($index);
    return ($result['acknowledged'] || $result['shards_acknowledged']);

  }

  /**
   * @param string $indexId
   * @param string $type
   */
  public function clearIndexDocuments(string $indexId, string $type) {
    //TODO - should hand off to document manager
    $search = new Search();
    $matchAllQuery = new MatchAllQuery();
    $search->addQuery($matchAllQuery);
    $params = [
      'index' => $indexId,
      'type'  => $type,
      'body'  => $search->toArray(),
    ];
    $response = $this->client->deleteByQuery($params);
    //TODO - response handling
  }


  /**
   * @param \Drupal\elastic_search\Entity\ElasticIndexInterface $elasticIndex
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function getDocumentsThatShouldBeInIndex(ElasticIndexInterface $elasticIndex): array {
    $id = FieldableEntityMap::getEntityAndBundle($elasticIndex->getMappingEntityId());
    $storage = $this->entityTypeManager->getStorage($id['entity']);
    $query = $storage->getQuery();
    if ($id['entity'] !== $id['bundle']) {
      $query->condition('type', $id['bundle']);
    }
    $query->condition('langcode', $elasticIndex->getIndexLanguage());
    try {
      $results = $query->execute();
    } catch (\Throwable $t) {
      return [];
    }

    return $storage->loadMultiple($results);
  }

}