<?php

namespace Drupal\colossal_menu\Plugin\rest\resource;

use Drupal\rest_menu_tree\Plugin\rest\resource\MenuTreeResource as BaseMenuTreeResource;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "colossal_menu_tree",
 *   label = @Translation("Colossal Menu Tree"),
 *   uri_paths = {
 *     "canonical" = "/entity/colossal_menu/{colossal_menu}/tree"
 *   }
 * )
 */
class MenuTreeResource extends BaseMenuTreeResource {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
      $container->get('colossal_menu.link_tree'),
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getBaseRoute($canonical_path, $method) {
    $route = parent::getBaseRoute($canonical_path, $method);

    $parameters = $route->getOption('parameters') ?: [];
    $parameters['colossal_menu']['type'] = 'entity:colossal_menu';
    $route->setOption('parameters', $parameters);

    return $route;
  }

}
