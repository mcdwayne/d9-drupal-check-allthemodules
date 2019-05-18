<?php

namespace Drupal\entity_ui\EntityHandler;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\PreloadableRouteProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Proxy handler for entity types with a field UI, but no bundle entities.
 *
 * This handles the two cases that can't distinguish between during entity type
 * build, as we either need to inspect the field UI base route or get bundle
 * info, neither of which can be done during entity type build because it would
 * cause circularity.
 *
 * Cheat and don't implement EntityUIAdminInterface so we can use __call().
 */
class FieldUIWithoutBundleEntityProxy implements EntityHandlerInterface {

  /**
   * The entity type this handler is for.
   */
  protected $entityType;

  /**
   * The ID of the entity type this handler is for.
   */
  protected $entityTypeId;

  /**
   * The route provider service.
   *
   * @var \Drupal\Core\Routing\PreloadableRouteProviderInterface
   */
  protected $routeProvider;

  /**
   * Constructs a new EntityUIAdminBase.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Routing\PreloadableRouteProviderInterface $route_provider
   *   The route provider service.
   */
  public function __construct(
    EntityTypeInterface $entity_type,
    EntityTypeManagerInterface $entity_type_manager,
    PreloadableRouteProviderInterface $route_provider
    ) {
    $this->entityTypeId = $entity_type->id();
    $this->entityType = $entity_type;
    $this->entityTypeManager = $entity_type_manager;
    $this->routeProvider = $route_provider;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager'),
      $container->get('router.route_provider')
    );
  }

  /**
   * The real handler that this wraps.
   *
   * @var \Drupal\entity_ui\EntityHandler\EntityUIAdminInterface
   */
  protected $realHandler;

  /**
   * Override this so we instantiate the real handler.
   *
   * Routes are built before the link entities, so this is the first call to
   * this handler.
   */
  public function getRoutes(RouteCollection $route_collection) {
    $field_ui_base_route_name = $this->entityType->get('field_ui_base_route');

    // Figure out the real handler to use and instantiate it.
    // Get the field UI base route from the collection we're given.
    $field_ui_base_route = $route_collection->get($field_ui_base_route_name);
    $this->setUpRealHandlerFromFieldUIBaseRoute($field_ui_base_route);

    return $this->realHandler->getRoutes($route_collection);
  }

  /**
   * Call through to the real handler.
   */
  function __call($name, $arguments) {
    if (empty($this->realHandler)) {
      $field_ui_base_route_name = $this->entityType->get('field_ui_base_route');

      // Figure out the real handler to use and instantiate it.
      // In any call to this handler other than getRoutes(), the router has
      // been built, so we can get the route from the route provider.
      $field_ui_base_route = $this->routeProvider->getRouteByName($field_ui_base_route_name);

      $this->setUpRealHandlerFromFieldUIBaseRoute($field_ui_base_route);
    }

    return $this->realHandler->{$name}(...$arguments);
  }

  /**
   * Instantiates the real handler this class wraps and sets it on the class.
   *
   * This determines the right handler to use for the entity type, by examining
   * the field UI base route. It's up to the caller to pass this route in, as
   * it's not obtained in the same way depending on whether the router is being
   * rebuilt or not.
   *
   * @param \Symfony\Component\Routing\Route $field_ui_base_route
   *   The base route for the entity type this handler is for.
   */
  protected function setUpRealHandlerFromFieldUIBaseRoute(Route $field_ui_base_route) {
    $field_ui_base_route_path = $field_ui_base_route->getPath();

    if (!empty($field_ui_base_route_path) && substr($field_ui_base_route_path, -(strlen('/{bundle}'))) == '/{bundle}') {
      // The entity type doesn't have a bundle entity type, but has multiple
      // bundles. These might be derived from plugins (using the Entity API
      // contrib module's functionality), or simnply hardcoded in
      // hook_entity_bundle_info(). We detect this by the presence of a 'bundle'
      // parameter at the end of the field UI route path, which Field UI module
      // expects when the bundles are not config entities.
      $handler_class = \Drupal\entity_ui\EntityHandler\PlainBundlesEntityUIAdmin::class;
    }
    else {
      // The entity type has only a single bundle.
      $handler_class = \Drupal\entity_ui\EntityHandler\BasicFieldUI::class;
    }

    $this->realHandler = $this->entityTypeManager->createHandlerInstance($handler_class, $this->entityType);
  }

}
