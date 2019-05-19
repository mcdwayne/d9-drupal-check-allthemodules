<?php

namespace Drupal\trusted_redirect_entity_edit\Service;

use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Route;

/**
 * Service to resolve entity edit url.
 */
class EntityEditUrlResolver {

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The route provider.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, EntityTypeManagerInterface $entity_type_manager, RouteProviderInterface $route_provider) {
    $this->entityRepository = $entity_repository;
    $this->entityTypeManager = $entity_type_manager;
    $this->routeProvider = $route_provider;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.manager'),
      $container->get('router.route_provider')
    );
  }

  /**
   * Get route by name.
   *
   * @param string $route_name
   *   Route name.
   *
   * @return bool|\Symfony\Component\Routing\Route
   *   Route object or false if route cannot be loaded.
   */
  protected function getRouteByName($route_name) {
    try {
      $route = $this->routeProvider->getRouteByName($route_name);
    }
    catch (\Exception $e) {
      $route = FALSE;
    }
    return $route instanceof Route ? $route : FALSE;
  }

  /**
   * Get entity edit route name.
   *
   * Drupal content entities use to follow the pattern for their edit
   * routes which is usually entity.{entity_type}.edit_form.
   *
   * @param string $entity_type
   *   Entity type to build route name for.
   *
   * @return string
   *   Entity edit route name.
   */
  protected function getEntityEditRouteName($entity_type) {
    return 'entity.' . $entity_type . '.edit_form';
  }

  /**
   * Get entity edit route object.
   *
   * @param string $entity_type
   *   Entity type to get route for.
   *
   * @return bool|\Symfony\Component\Routing\Route
   *   Entity edit route.
   */
  protected function getEntityEditRoute($entity_type) {
    $entity_edit_route_name = $this->getEntityEditRouteName($entity_type);
    if (!$entity_edit_route_name) {
      return FALSE;
    }
    $route = $this->getRouteByName($entity_edit_route_name);
    if (!$route) {
      return FALSE;
    }
    // Entity edit url has one single route parameter, which matches the entity
    // type of that entity. Check if number of route parameters equals one.
    $route_parameters = $route->getOption('parameters');
    if (count($route_parameters) !== 1) {
      return FALSE;
    }
    // Check if route parameter name matches.
    $route_parameter = key($route_parameters);
    if ($route_parameter != $entity_type) {
      return FALSE;
    }
    return $route;
  }

  /**
   * Check if valid entity edit route exists.
   *
   * @param string $entity_type
   *   Entity type.
   *
   * @return bool
   *   True if exists, false otherwise.
   */
  protected function entityEditRouteExists($entity_type) {
    return $this->getEntityEditRoute($entity_type) ? TRUE : FALSE;
  }

  /**
   * Load entity by entity type and uuid.
   *
   * @param string $entity_type
   *   Entity type.
   * @param string $uuid
   *   Uuid of entity.
   *
   * @return bool|\Drupal\Core\Entity\EntityInterface
   */
  protected function loadEntityByUuid($entity_type, $uuid) {
    try {
      $entity = $this->entityRepository->loadEntityByUuid($entity_type, $uuid);
    }
    catch (\Exception $e) {
      $entity = FALSE;
    }
    return $entity instanceof EntityInterface ? $entity : FALSE;
  }

  /**
   * Resolve entity edit url by uuid.
   *
   * @param string $uuid
   *   Uuid of entity.
   *
   * @return \Drupal\Core\Url|bool
   *   Entity edit url or false if not possible to obtain.
   */
  public function resolveEditUrlByUuid($uuid) {
    /* @var \Drupal\Core\Entity\EntityTypeInterface[] $entity_type_definitions */
    $entity_type_definitions = $this->entityTypeManager->getDefinitions();
    // Loop through entity type definitions.
    foreach ($entity_type_definitions as $definition) {
      // Look for content entity type definitions only.
      if ($definition instanceof ContentEntityType) {
        // Definition id matches entity type.
        $entity_type = $definition->id();
        // Process if entity edit url exists and entity can be loaded as well.
        if ($this->entityEditRouteExists($entity_type) && $entity = $this->loadEntityByUuid($entity_type, $uuid)) {
          $route_name = $this->getEntityEditRouteName($entity_type);
          return Url::fromRoute($route_name, [$entity_type => $entity->id()]);
        }
      }
    }
    throw new NotFoundHttpException();
  }

}
