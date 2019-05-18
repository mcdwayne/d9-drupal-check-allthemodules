<?php

/**
 * @file
 * Contains \Drupal\collect\CaptureEntity.
 */

namespace Drupal\collect;

use Drupal\collect\Entity\Container;
use Drupal\collect\Model\ModelManagerInterface;
use Drupal\collect\Plugin\collect\Model\FieldDefinition;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Captures an entity to collect container.
 */
class CaptureEntity {

  /**
   * The mime type of the submitted data.
   */
  const MIMETYPE = 'application/json';

  /**
   * Request object to get HttpHost.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * SerializerInterface which is used for serialization.
   *
   * @var \Symfony\Component\Serializer\Normalizer\NormalizerInterface $serializer
   */
  protected $serializer;

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The injected model manager.
   *
   * @var \Drupal\collect\Model\ModelManagerInterface
   */
  protected $modelManager;

  /**
   * Set up a new CaptureEntity instance.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *    RequestStack object to get HttpHost.
   * @param \Symfony\Component\Serializer\Normalizer\NormalizerInterface $serializer
   *    SerializerInterface which is used for serialization.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *    The entity manager service.
   * @param \Drupal\collect\Model\ModelManagerInterface $model_manager
   *    The injected model manager.
   */
  public function __construct(RequestStack $request_stack, NormalizerInterface $serializer, EntityManagerInterface $entity_manager, ModelManagerInterface $model_manager) {
    $this->requestStack = $request_stack;
    $this->serializer = $serializer;
    $this->entityManager = $entity_manager;
    $this->modelManager = $model_manager;
  }

  /**
   * Captures an existing entity as a collect container.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to capture.
   * @param string $operation
   *   (optional) The entity operation that triggered the capturing.
   *
   * @return \Drupal\collect\CollectContainerInterface
   *   The new container containing the values from captured entity.
   */
  public function capture(ContentEntityInterface $entity, $operation = '') {
    $entities[$entity->getEntityTypeId() . ':' . $entity->id()] = $entity;
    collect_common_get_referenced_entities($entities, $entity, \Drupal::config('collect.settings')->get('entity_capture'));

    /** @var \Drupal\collect\CollectStorage $container_storage */
    $container_storage = $this->entityManager->getStorage('collect_container');

    $values_container = NULL;
    foreach (array_reverse($entities) as $captured_entity) {
      $containers = $this->createContainer($captured_entity, $operation);
      // Persist both fields and value containers.
      $container_storage->persist($containers['fields'], $this->modelManager->isModelRevisionable($containers['fields']));
      $values_container = $container_storage->persist($containers['values'], $this->modelManager->isModelRevisionable($containers['values']));
    }

    return $values_container;
  }


  /**
   * Creates a new collect container object from given entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The original content entity that should be captured.
   * @param string $operation
   *   The entity operation that triggered the capturing.
   *
   * @return array
   *   An array that contains fields and values of created container.
   */
  public function createContainer(ContentEntityInterface $entity, $operation) {
    $origin_uri = $entity->url('canonical', ['absolute' => TRUE]);

    $request = $this->requestStack->getCurrentRequest();

    if ($origin_uri == '') {
      $origin_uri = $request->getSchemeAndHttpHost() . $request->getBasePath() . '/entity/' . $entity->getEntityType()->id();
      if ($entity->getEntityType()->hasKey('bundle')) {
        $origin_uri .= '/' . $entity->bundle();
      }
      $origin_uri .= '/' . $entity->uuid();
    }

    $schema_uri = 'http://schema.md-systems.ch/collect/0.0.1/collectjson/' . $request->getHttpHost() . '/entity/' . $entity->getEntityType()->id();

    // Exclude key bundle from the schema URI for entites that do not have it.
    if ($entity->getEntityType()->hasKey('bundle')) {
      $schema_uri .= '/' . $entity->bundle();
    }

    // Create the values container.
    $values = $this->serializer->normalize($entity, 'collect_json');
    /* @var \Drupal\collect\Entity\Container $container */
    $values_container = Container::create([
      'origin_uri' => $origin_uri,
      'schema_uri' => $schema_uri,
      'type' => static::MIMETYPE,
      'data' => Json::encode(['values' => $values, 'operation' => $operation]),
    ]);

    // Create the fields container.
    $fields = $this->serializer->normalize($entity->getFieldDefinitions(), 'json');
    // Expand field definitions with URI definitions for entity reference fields.
    $fields = collect_common_add_uri_definitions($this->entityManager, $this->serializer, $entity->getFieldDefinitions(), $fields);
    $data = ['fields' => $fields];

    // Add entity type and bundle info to the container.
    $data['entity_type'] = $entity->getEntityTypeId();
    $bundle_entity_type = $this->entityManager->getDefinition($entity->getEntityTypeId())->getBundleEntityType();
    if ($bundle_entity_type && $bundle_entity_type != 'bundle') {
      $bundle_storage = $this->entityManager->getStorage($bundle_entity_type);
      $data['bundle'] = $this->serializer->normalize($bundle_storage->load($entity->bundle()));
      $data['bundle']['bundle_key'] = $this->entityManager->getDefinition($entity->getEntityTypeId())->getKey('bundle');
    }

    $fields_container = Container::create([
      'origin_uri' => $schema_uri,
      'schema_uri' => FieldDefinition::URI,
      'type' => static::MIMETYPE,
      'data' => Json::encode($data),
    ]);

    return [
      'values' => $values_container,
      'fields' => $fields_container,
    ];
  }

  /**
   * Captures a new entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to capture.
   *
   * @return \Drupal\collect\CollectContainerInterface
   *   The new container containing the captured entity.
   */
  public function captureEntityInsert(ContentEntityInterface $entity) {
    $container = $this->capture($entity, 'insert');
    return $container;
  }

  /**
   * Captures an entity update.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to capture.
   *
   * @return \Drupal\collect\CollectContainerInterface
   *   The new container containing the captured entity.
   */
  public function captureEntityUpdate(ContentEntityInterface $entity) {
    $container = $this->capture($entity, 'update');
    return $container;
  }

}
