<?php

namespace Drupal\virtual_entities\Entity\Query;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Query\QueryBase;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Entity\Query\ConditionInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use GuzzleHttp\ClientInterface;
use Drupal\virtual_entities\VirtualEntityDecoderServiceInterface;
use Drupal\virtual_entities\VirtualEntityStorageClientLoader;

/**
 * Class Query.
 *
 * @package Drupal\virtual_entities\Entity\Query
 */
class Query extends QueryBase implements QueryInterface {

  /**
   * The parameters to send to entity storage client.
   *
   * @var array
   */
  protected $parameters = [];

  /**
   * The decoder to decode the data.
   *
   * @var \Drupal\virtual_entities\VirtualEntityDecoderService
   */
  protected $decoder;

  /**
   * The HTTP client to fetch the data with.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The storage client manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $storageClientManager;

  /**
   * The storage client.
   *
   * @var \Drupal\virtual_entities\Plugin\VirtualEntityStorageClientPluginInterface
   */
  protected $storageClient;

  /**
   * Query constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   Virtual entity type.
   * @param string $conjunction
   *   Query condition.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $storage_client_manager
   *   Storage client plugin manager.
   * @param \Drupal\virtual_entities\VirtualEntityDecoderServiceInterface $decoder
   *   Decoder instance.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   GuzzleHttp client.
   * @param array $namespaces
   *   Current entity namespace.
   */
  public function __construct(EntityTypeInterface $entity_type, $conjunction, PluginManagerInterface $storage_client_manager, VirtualEntityDecoderServiceInterface $decoder, ClientInterface $http_client, array $namespaces) {
    parent::__construct($entity_type, $conjunction, $namespaces);

    $this->storageClientManager = $storage_client_manager;
    $this->decoder = $decoder;
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    return $this
      ->prepare()
      ->compile()
      ->addSort()
      ->finish()
      ->result();
  }

  /**
   * Prepares the basic query with proper metadata/tags and base fields.
   */
  public function prepare() {
    $this->setParameter('entityTypeId', $this->getEntityTypeId());
    $this->setParameter('entityType', $this->entityType);

    return $this;
  }

  /**
   * Compiles the conditions.
   *
   * @return \Drupal\virtual_entities\Entity\Query\Query
   *   Returns the called object.
   */
  protected function compile() {
    $this->condition->compile($this);

    return $this;
  }

  /**
   * Adds the sort to the build query.
   *
   * @return \Drupal\virtual_entities\Entity\Query\Query
   *   Returns the called object.
   */
  protected function addSort() {
    return $this;
  }

  /**
   * Finish the query by adding fields, GROUP BY and range.
   *
   * @return \Drupal\virtual_entities\Entity\Query\Query
   *   Returns the called object.
   */
  protected function finish() {
    // Page query.
    $this->initializePager();

    if ($this->range) {
      $start = $this->range['start'];
      $end = $this->range['length'];
      $this->setParameter('page_start', $start);
      $this->setParameter('page_size', $end);
    }

    return $this;
  }

  /**
   * Executes the query and returns the result.
   *
   * @return int|array
   *   Returns the query result as entity IDs.
   *
   * @see \Drupal\virtual_entities\Plugin\VirtualEntityStorageClientPlugin\Restful
   * @see \Drupal\field_ui\Form\FieldStorageConfigEditForm
   */
  protected function result() {
    // Return 0 if query field delta.
    if ($this->count) {
      $conditions = $this->condition->conditions();
      // Fix field settings.
      if (count($conditions) == 1 && (FALSE !== strpos($conditions[0]['field'], '.%delta'))) {
        return 0;
      }
    }

    // Result array.
    $result = [];

    // Set condition bundle if available..
    if (!empty($this->getBundle())) {
      $condition_bundle = $this->getBundle();
    }

    // Set bundles.
    if (isset($condition_bundle)) {
      $bundle_ids = $condition_bundle;
    }
    else {
      $bundle_ids = \Drupal::service('entity_type.bundle.info')->getBundleInfo($this->entityType->id());
    }

    if (!empty($bundle_ids) && is_array($bundle_ids)) {
      // Load storage client.
      $clientLoader = new VirtualEntityStorageClientLoader($this->storageClientManager);

      // Set the internal pointer of an array to its first element.
      reset($bundle_ids);

      foreach ($bundle_ids as $bundle_id => $bundle) {
        $this->setParameter('bundle_id', $bundle_id);
        // Fetch entities ids.
        $query_results = (array) $clientLoader->getStorageClient($bundle_id)->query($this->parameters);

        // Fetch entities.
        $bundle_entity_type = $this->entityType->getBundleEntityType();
        $bundle = \Drupal::entityTypeManager()->getStorage($bundle_entity_type)->load($bundle_id);

        foreach ($query_results as $query_result) {
          $query_result = (object) $query_result;
          if (FALSE === $bundle->getFieldMapping('id')) {
            continue;
          }
          // Continue if unique ID is not available.
          if (!isset($query_result->{$bundle->getFieldMapping('id')})) {
            continue;
          }
          $hashed_id = virtual_entities_hash($query_result->{$bundle->getFieldMapping('id')});
          $id = $bundle_id . '-' . $hashed_id;
          $result[$id] = $id;
        }
      }
    }

    // Return count numbers.
    if ($this->count) {
      return count($result);
    }

    return $result;
  }

  /**
   * Set the Parameter.
   *
   * @param string $key
   *   Parameter key.
   * @param string $value
   *   Parameter value.
   *
   * @return bool
   *   Set parameter.
   */
  public function setParameter($key, $value) {
    if ($key == $this->entityType->getKey('bundle')) {
      return FALSE;
    }
    $this->parameters[$key] = is_array($value) ? implode($value, ',') : $value;
  }

  /**
   * Get entity bundle from query conditions.
   *
   * @param \Drupal\Core\Entity\Query\ConditionInterface|null $condition
   *   Condition instance.
   *
   * @return bool|mixed
   *   Bundle name or FALSE.
   */
  protected function getBundle(ConditionInterface $condition = NULL) {
    if (is_null($condition)) {
      $condition = $this->condition;
    }

    $bundles = [];

    foreach ($condition->conditions() as $c) {
      if ($c['field'] instanceof ConditionInterface) {
        $bundle = $this->getBundle($c['field']);
        if ($bundle) {
          return $bundles[$bundle] = $bundle;
        }
      }
      else {
        if ($c['field'] == $this->entityType->getKey('bundle')) {
          if (is_array($c['value'])) {
            foreach ($c['value'] as $bundle) {
              $bundles[$bundle] = $bundle;
            }
          }
          else {
            $bundles[$c['value']] = $c['value'];
          }
        }
      }
    }

    return reset($bundles);
  }

}
