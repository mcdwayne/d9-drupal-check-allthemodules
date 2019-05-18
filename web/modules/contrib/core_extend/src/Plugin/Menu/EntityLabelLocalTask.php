<?php

namespace Drupal\core_extend\Plugin\Menu;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Menu\LocalTaskDefault;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides title based on the entity's status action.
 */
class EntityLabelLocalTask extends LocalTaskDefault implements ContainerFactoryPluginInterface {

  /**
   * The entity from the current route match.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Construct the UnapprovedComments object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\RouteMatchInterface $current_route_match
   *   The current route match.
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider.
   *
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, RouteMatchInterface $current_route_match, $route_provider) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->routeMatch = $current_route_match;
    $this->routeProvider = $route_provider;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
      $container->get('router.route_provider')
    );
  }

  /**
   * Gets the task route definition.
   *
   * @return \Symfony\Component\Routing\Route
   *   The task route definition.
   */
  protected function getRouteDefinition() {
    return $this->routeProvider()->getRouteByName($this->getRouteName());
  }

  /**
   * Gets the task entity's entity type.
   *
   * @return string
   *   The task entity's entity type.
   */
  protected function getEntityTypeId() {
    return $this->getRouteDefinition()->getDefault('_entity_type_id')?:'';
  }

  /**
   * Gets the entity from current route match.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   The entity from current route match.
   */
  protected function getEntity() {
    if (is_null($this->entity)) {
      $entity_type_id = $this->getEntityTypeId();
      $parameter = $this->routeMatch->getParameter($entity_type_id);

      if (!is_null($parameter) && !$parameter instanceof EntityInterface) {
        $parameter = \Drupal::entityTypeManager()->getStorage($entity_type_id)->load($parameter);
      }

      $this->entity = $parameter;
    }

    return $this->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getActive() {
    if (is_null($this->getEntity())) {
      return FALSE;
    }
    return parent::getActive();
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle(Request $request = NULL) {
    if (!is_null($this->getEntity())) {
      return (string) $this->getEntity()->label();
    }
    return parent::getTitle($request);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    if (!is_null($this->getEntity())) {
      return Cache::mergeContexts(parent::getCacheContexts(), $this->entity->getCacheContexts());
    }
    return parent::getCacheContexts();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    if (!is_null($this->getEntity())) {
      return Cache::mergeTags(parent::getCacheTags(), $this->entity->getCacheTags());
    }
    return parent::getCacheTags();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    if (!is_null($this->getEntity())) {
      return Cache::mergeMaxAges(parent::getCacheMaxAge(), $this->entity->getCacheMaxAge());
    }
    return parent::getCacheMaxAge();
  }

}
