<?php

namespace Drupal\user_request\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\entity\Routing\DefaultHtmlRouteProvider;

/**
 * Provides routes for the Response entity.
 */
class ResponseHtmlRouteProvider extends DefaultHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  protected function getAddFormRoute(EntityTypeInterface $entity_type) {
    if ($route = parent::getAddFormRoute($entity_type)) {
      // Adds the request parameter to the URL.
      $route->setOption('parameters', [
        'user_request' => [
          'type' => 'entity:user_request',
        ],
      ]);

      // Adds custom access checking and checks permission to update request.
      $route->setRequirements([
        '_entity_access' => 'user_request.update',
        '_user_request_response_form_access' => 'TRUE',
      ]);
    }
    return $route;
  }

}
