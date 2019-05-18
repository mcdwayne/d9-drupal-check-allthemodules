<?php

/**
 * @file
 * Contains \Drupal\paragraphs_react\Routing\ParagraphsReactRoutes.
 */

namespace Drupal\paragraphs_react\Routing;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Symfony\Component\Routing\Route;

/**
 * Dynamic routes for paragraphs react.
 */
class ParagraphsReactRoutes {

  public function routes() {
    $routes = [];
    $paragraphsReactMapping = \Drupal::service('paragraphs_react.manager')->loadAll();
    foreach ($paragraphsReactMapping as $paragraphsReactData) {
      $route_name = 'paragraphs_react_routes.'.$paragraphsReactData->entity_id.".".$paragraphsReactData->entity_type;
      try {
        $node = \Drupal::entityTypeManager()
          ->getStorage('node')
          ->load($paragraphsReactData->entity_id);
      } catch (InvalidPluginDefinitionException $e) {
        $node = NULL;
      }
      if(!is_null($node)) {
        $routes[$route_name] = new Route(
          $paragraphsReactData->page_url,
          [
            '_controller' => '\Drupal\paragraphs_react\Controller\ParagraphsReactMainController::loadReactPage',
            '_title' => $paragraphsReactData->page_title,
            'entity_id' => $paragraphsReactData->entity_id,
            'entity_type' => $paragraphsReactData->entity_type,
            'paragraph_field_name' => $paragraphsReactData->paragraph_field_name
          ],
          [
            '_permission' => 'access content',
          ]
        );
      }
    }
    return $routes;
  }
}