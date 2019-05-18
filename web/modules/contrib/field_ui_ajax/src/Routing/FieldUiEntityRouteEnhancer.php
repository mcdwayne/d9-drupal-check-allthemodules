<?php

/**
 * @file
 * Contains \Drupal\field_ui_ajax\Routing\FieldUiEntityRouteEnhancer.
 */

namespace Drupal\field_ui_ajax\Routing;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Routing\Enhancer\RouteEnhancerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;

/**
 * Enhances an entity form route with the appropriate controller.
 */
class FieldUiEntityRouteEnhancer implements RouteEnhancerInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a FieldUiRouteEnhancer object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function enhance(array $defaults, Request $request) {
    //if (empty($defaults['_controller'])) {
      if (!empty($defaults['_entity_form'])) {
        $defaults = $this->enhanceEntityForm($defaults, $request);
      }
    //}
    return $defaults;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(Route $route) {
    return $route->hasOption('_field_ui_ajax') && $route->hasDefault('_entity_form');
  }

  /**
   * Update defaults for entity forms.
   *
   * @param array $defaults
   *   The defaults to modify.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The Request instance.
   *
   * @return array
   *   The modified defaults.
   */
  protected function enhanceEntityForm(array $defaults, Request $request) {
    $defaults['_controller_class'] = isset($defaults['_controller_class']) ? $defaults['_controller_class'] : 'controller.entity_form';
    $defaults['_controller_method'] = isset($defaults['_controller_method']) ? $defaults['_controller_method'] : 'getContentResult';
    $defaults['_controller'] = $defaults['_controller_class'] . ':' . $defaults['_controller_method'];
    return $defaults;
  }

}
