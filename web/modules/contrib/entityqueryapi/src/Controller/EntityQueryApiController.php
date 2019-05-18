<?php

namespace Drupal\entityqueryapi\Controller;

use Drupal\entityqueryapi\QueryBuilder\Parser;
use Drupal\entityqueryapi\QueryBuilder\QueryBuilderInterface;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Class EntityQueryApiController.
 *
 * @package Drupal\entityqueryapi\Controller
 */
class EntityQueryApiController extends ControllerBase {

  protected $entityTypeManager;
  protected $entityType;
  protected $normalizer;
  protected $queryBuilder;

  public function __construct(EntityTypeManagerInterface $entity_type_manager, NormalizerInterface $normalizer, SerializerInterface $serializer, QueryBuilderInterface $query_builder) {
    $normalizer->setSerializer($serializer);

    $this->entityTypeManager = $entity_type_manager;
    $this->normalizer = $normalizer;
    $this->queryBuilder = $query_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('serializer.normalizer.list'),
      $container->get('serializer'),
      $container->get('entityqueryapi.query_builder')
    );
  }

  /**
   * List activity_actions.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Return a JSON Response
   */
  public function content(Request $request, EntityTypeInterface $entity_type) {
    $this->entityType = $entity_type;

    $options = Parser::getQueryOptions($request);

    $query = $this->queryBuilder->newQuery($this->entityType, $options);

    $entities = $this->getEntities($query);

    $normalized = $this->normalizer->normalize($entities, 'json');

    $response = new JsonResponse();
    $response->setData($normalized);
    return $response;
  }

  /**
   * Custom access checker.
   */
  public function access(AccountInterface $account, EntityTypeInterface $entity_type) {
    $this->entityType = $entity_type;
    $entity_type_id = $this->entityType->id();
    $enabled = $this->config('entityqueryapi.config')->get("{$entity_type_id}.enabled");
    return AccessResult::allowedIf($enabled);
  }

  protected function getEntities($query) {
    $entity_type_id = $this->getEntityTypeId();

    // Execute and get the entity IDs from the result.
    $entity_ids = array_values($query->execute());

    // Get a storage connection for the appropriate entity type.
    $storage = $this->entityTypeManager()->getStorage($entity_type_id);

    // Load all the entities from their ids.
    $entities = $storage->loadMultiple($entity_ids);

    return array_filter($entities, function ($entity) {
      return $entity->access('view');
    });
  }

  protected function getEntityTypeId() {
    return $this->entityType->id();
  }

}
