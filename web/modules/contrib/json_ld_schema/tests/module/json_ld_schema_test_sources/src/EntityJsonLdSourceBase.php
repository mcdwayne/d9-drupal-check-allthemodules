<?php

namespace Drupal\json_ld_schema_test_sources;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\json_ld_schema\Source\JsonLdSourceBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for JSON LD sources that should appear on entity pages.
 *
 * @deprecated
 *   Use the JsonLdEntity plugin type instead of this base class. This used to
 *   be a thing until JsonLdEntity, but now it only exists as a good test case
 *   for the render cache integration of JsonLdSource.
 */
abstract class EntityJsonLdSourceBase extends JsonLdSourceBase implements ContainerFactoryPluginInterface {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $currentRouteMatch;

  /**
   * The current entity or NULL if none exists.
   *
   * @var \Drupal\Core\Entity\EntityInterface|null
   */
  protected $entity = NULL;

  /**
   * EntityJsonLdSourceBase constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $currentRouteMatch) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentRouteMatch = $currentRouteMatch;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match')
    );
  }

  /**
   * Get the raw non-access controlled entity from the current route.
   *
   * @return \Drupal\Core\Entity\EntityInterface|false
   *   An entity for FALSE if none was found.
   */
  private function getRawEntityFromRoute() {
    list($route_type, $entity_type_id, $entity_route_type) = array_pad(explode('.', $this->currentRouteMatch->getRouteName()), 3, NULL);
    if ($route_type !== 'entity' || $entity_route_type !== 'canonical') {
      return FALSE;
    }
    $this->currentRouteMatch->getRouteName();
    foreach ($this->currentRouteMatch->getParameters() as $parameter) {
      if ($parameter instanceof EntityInterface && $parameter->getEntityTypeId() === $entity_type_id) {
        return $parameter;
      }
    }
    return FALSE;
  }

  /**
   * Get the current entity, access controlled for the "view" operation.
   *
   * @return \Drupal\Core\Entity\EntityInterface|false
   *   The entity or FALSE if none was found.
   */
  protected function getEntity() {
    $entity = $this->getRawEntityFromRoute();
    if ($entity && $entity->access('view')) {
      return $entity;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata(): CacheableMetadata {
    $metadata = parent::getCacheableMetadata();
    // Add the cacheability of the access check and the entity, if it exists
    // for the current page.
    if ($entity = $this->getEntity()) {
      $access = $entity->access('view', NULL, TRUE);
      $metadata->addCacheableDependency($access);
      $metadata->addCacheableDependency($entity);
    }
    // Add the route cache context, as we're laoding a different entity per
    // route.
    $metadata->addCacheContexts(['route']);
    return $metadata;
  }

  /**
   * Check if the current entity matches the given entity type.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return bool
   *   If the current entity matches the given type.
   */
  protected function currentEntityIsOfType($entity_type_id) {
    $entity = $this->getEntity();
    return $entity && $entity->getEntityTypeId() === $entity_type_id;
  }

  /**
   * Check if the current entity matches the given entity type and bundle.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The entity bundle.
   *
   * @return bool
   *   If the current entity matches the given types.
   */
  protected function currentEntityIsOfTypeAndBundle($entity_type_id, $bundle) {
    $entity = $this->getEntity();
    return $entity && $entity->getEntityTypeId() === $entity_type_id && $entity->bundle() === $bundle;
  }

}
