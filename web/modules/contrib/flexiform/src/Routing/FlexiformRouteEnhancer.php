<?php

namespace Drupal\flexiform\Routing;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\EnhancerInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Enhances Flexiform routes.
 *
 * To get the form display object from request attributes.
 */
class FlexiformRouteEnhancer implements EnhancerInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a FlexiformRouteEnhancer object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function enhance(array $defaults, Request $request) {
    /* @var \Symfony\Component\Routing\Route $route */
    $route = $defaults[RouteObjectInterface::ROUTE_OBJECT];
    if (!$route->hasOption('_flexiform_form_entity')) {
      return $defaults;
    }

    $defaults['form_display'] = $this->entityTypeManager->getStorage('entity_form_display')->load($defaults['entity_type_id'] . '.' . $defaults['bundle'] . '.' . $defaults['form_mode_name']);

    return $defaults;
  }

}
