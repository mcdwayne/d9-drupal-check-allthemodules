<?php

namespace Drupal\entity_router\Plugin\EntityResponseHandler;

use Drupal\Core\Entity\EntityInterface;
use Drupal\entity_router\EntityResponseHandlerInterface;
use Drupal\jsonapi\Controller\RequestHandler;
use Drupal\jsonapi\Controller\EntityResource;
use Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface;
use Drupal\jsonapi\Routing\Routes;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * {@inheritdoc}
 *
 * @EntityResponseHandler(
 *   id = "jsonapi",
 *   dependencies = {"jsonapi"},
 * )
 */
class JsonApiEntityResponseHandler implements EntityResponseHandlerInterface {

  /**
   * An instance of the "jsonapi.resource_type.repository" service.
   *
   * @var \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface
   */
  protected $resourceTypeRepository;

  /**
   * An instance of the "jsonapi.request_handler" service.
   *
   * NOTE: available in "jsonapi:1.x" only.
   *
   * @var \Drupal\jsonapi\Controller\RequestHandler|null
   */
  protected $requestHandler;

  /**
   * An instance of the "jsonapi.entity_resource" service.
   *
   * NOTE: available in "jsonapi:2.x" only.
   *
   * @var \Drupal\jsonapi\Controller\EntityResource|null
   */
  protected $entityResource;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    ResourceTypeRepositoryInterface $resource_type_repository,
    ?RequestHandler $request_handler,
    ?EntityResource $entity_resource
  ) {
    $this->resourceTypeRepository = $resource_type_repository;
    $this->requestHandler = $request_handler;
    $this->entityResource = $entity_resource;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $container->get('jsonapi.resource_type.repository'),
      $container->get('jsonapi.request_handler', $container::NULL_ON_INVALID_REFERENCE),
      $container->get('jsonapi.entity_resource', $container::NULL_ON_INVALID_REFERENCE)
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getResponse(Request $request, ?EntityInterface $entity): Response {
    if ($entity === NULL) {
      return new JsonResponse($entity);
    }

    $entity_type = $entity->getEntityTypeId();
    $resource_type = $this->resourceTypeRepository->get($entity_type, $entity->bundle());

    // These properties are mandatory for the JSON API request handler.
    $request->attributes->set($entity_type, $entity);
    $request->attributes->set(Routes::RESOURCE_TYPE_KEY, $resource_type);
    // Mark the request in order to allow JSON:API to recognize requests.
    /* @see \Drupal\jsonapi\Routing\Routes::getResourceTypeNameFromParameters() */
    $request->attributes->set(Routes::JSON_API_ROUTE_FLAG_KEY, TRUE);

    if ($this->entityResource !== NULL) {
      return $this->entityResource->getIndividual($entity, $request);
    }

    if ($this->requestHandler !== NULL) {
      return $this->requestHandler->handle($request, $resource_type);
    }

    throw new \LogicException('Insufficient version of the JSON:API. Please report this incident.');
  }

}
