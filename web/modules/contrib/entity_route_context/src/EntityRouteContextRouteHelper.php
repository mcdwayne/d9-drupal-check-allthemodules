<?php

declare(strict_types = 1);

namespace Drupal\entity_route_context;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Symfony\Component\Routing\Route;

/**
 * Route helper.
 *
 * Designed to assist determining which routes are owned by a particular entity
 * type by way of link templates.
 */
class EntityRouteContextRouteHelper implements EntityRouteContextRouteHelperInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The route provider to be searched for routes.
   *
   * @var \Drupal\Core\Routing\RouteProvider
   */
  protected $routeProvider;

  /**
   * The cache bin.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Entity types keyed by route name. Or NULL if not yet built.
   *
   * @var array|null
   */
  protected $routes;

  /**
   * Route names keyed by entity type. Or NULL if not yet built.
   *
   * @var array|null
   */
  protected $routesByEntityType;

  /**
   * Cache bin CID to store route/link template map.
   */
  protected const ENTITY_ROUTE_CONTEXT_MAP = 'entity_route_context:link_template_map';

  /**
   * Constructs a new EntityRouteContextRouteHelper.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Routing\RouteProviderInterface $routeProvider
   *   The route provider to be searched for routes.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache bin.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, RouteProviderInterface $routeProvider, CacheBackendInterface $cache) {
    $this->entityTypeManager = $entityTypeManager;
    $this->routeProvider = $routeProvider;
    $this->cache = $cache;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllRouteNames(): array {
    if (!isset($this->routes)) {
      $this->primeCaches();
    }

    return $this->routes;
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteNames(string $entityTypeId): array {
    if (isset($this->routesByEntityType[$entityTypeId])) {
      return $this->routesByEntityType[$entityTypeId];
    }

    if (!isset($this->routes)) {
      $this->primeCaches();
    }

    $this->routesByEntityType[$entityTypeId] = array_keys($this->routes, $entityTypeId, TRUE);

    return $this->routesByEntityType[$entityTypeId];
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeId(string $routeName): ?string {
    if (!isset($this->routes)) {
      $this->primeCaches();
    }

    return $this->routes[$routeName] ?? NULL;
  }

  /**
   * Gets or computes entity route map.
   */
  protected function primeCaches(): void {
    /** @var array|false $item */
    $item = $this->cache->get(static::ENTITY_ROUTE_CONTEXT_MAP);
    if (FALSE !== $item) {
      $this->routes = $item->data ?? [];
      return;
    }

    $pathByRouteName = array_map(
      function (Route $route) {
        return $route->getPath();
      },
      iterator_to_array($this->routeProvider->getAllRoutes())
    );

    $routes = [];
    foreach ($this->entityTypeManager->getDefinitions() as $entityType) {
      foreach ($entityType->getLinkTemplates() as $linkTemplate) {
        $key = array_search($linkTemplate, $pathByRouteName);
        if ($key !== FALSE) {
          $routes[$key] = $entityType->id();
        }
      }
    }

    // Same tag used by entity type definitions in 'discovery' bin.
    $tags = ['entity_types', 'routes'];
    $this->cache->set(static::ENTITY_ROUTE_CONTEXT_MAP, $routes, Cache::PERMANENT, $tags);
    $this->routes = $routes;
  }

}
