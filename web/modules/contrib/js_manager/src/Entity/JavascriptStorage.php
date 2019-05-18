<?php

namespace Drupal\js_manager\Entity;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Cache\MemoryCache\MemoryCacheInterface;
use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Routing\CurrentRouteMatch;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Loads Javascript records.
 */
class JavascriptStorage extends ConfigEntityStorage {

  /**
   * Constructs a JavascriptStorage object.
   *
   * Trying to inject the storage manager throws an exception.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid_service
   *   The UUID service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Cache\MemoryCache\MemoryCacheInterface|null $memory_cache
   *   The memory cache.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $route_match
   *   The current route match.
   * @param \Drupal\Core\Routing\AdminContext $router_admin_context
   *   The router admin context.
   */
  public function __construct(
    EntityTypeInterface $entity_type,
    ConfigFactoryInterface $config_factory,
    UuidInterface $uuid_service,
    LanguageManagerInterface $language_manager,
    MemoryCacheInterface $memory_cache = NULL,
    EntityTypeManagerInterface $entity_type_manager,
    CurrentRouteMatch $route_match,
    AdminContext $router_admin_context
  ) {
    parent::__construct(
      $entity_type,
      $config_factory,
      $uuid_service,
      $language_manager,
      $memory_cache
    );
    $this->entityTypeManager = $entity_type_manager;
    $this->routeMatch = $route_match;
    $this->routerAdminContext = $router_admin_context;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(
    ContainerInterface $container,
    EntityTypeInterface $entity_type
  ) {
    return new static(
      $entity_type,
      $container->get('config.factory'),
      $container->get('uuid'),
      $container->get('language_manager'),
      $container->get('entity.memory_cache'),
      $container->get('entity_type.manager'),
      $container->get('current_route_match'),
      $container->get('router.admin_context')
    );
  }

  /**
   * Get array of renderable scripts.
   *
   * @return array
   *   Renderable JS.
   */
  public function getScripts($scope) {
    $scripts = [];

    foreach ($this->loadMultiple() as $javascript) {
      // Add only scripts with scope footer.
      if ($javascript->getScope() == $scope) {
        // Don't add the script if it's an admin path and the script is
        // excluded from admin pages.
        if ($javascript->excludeAdmin() && $this->isAdminPath()) {
          continue;
        }
        $scripts['js_manager_' . $javascript->id()] = $javascript->toRenderArray();
      }
    }

    return $scripts;
  }

  /**
   * Check if current path is admin.
   *
   * @return bool
   *   Path is admin.
   */
  private function isAdminPath() {
    $route = $this->routeMatch->getRouteObject();
    return $this->routerAdminContext->isAdminRoute($route);
  }

}
