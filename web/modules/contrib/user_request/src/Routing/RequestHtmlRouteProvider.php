<?php

namespace Drupal\user_request\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\entity\Routing\DefaultHtmlRouteProvider;

/**
 * Provides routes for the Request entity.
 */
class RequestHtmlRouteProvider extends DefaultHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  protected function getEditFormRoute(EntityTypeInterface $entity_type) {
    if ($route = parent::getEditFormRoute($entity_type)) {
      // Changes the requirements to use custom access check only.
      $route->setRequirements([
        '_user_request_edit_form_access' => 'TRUE',
      ]);
    }
    return $route;
  }

}
