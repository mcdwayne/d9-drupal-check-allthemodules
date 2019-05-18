<?php

namespace Drupal\token_custom_plus\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // If we wish to support uppercase and special characters for token names,
    // as optionally defined in token_custom_plus_form_token_custom_form_alter()
    // we must also relax the permissable character range for token edit and
    // delete URLs.
    if (token_custom_plus_get_setting('relaxed_machine_names')) {
      if ($route = $collection->get('entity.token_custom.edit_form')) {
        $reqs = $route->getRequirements();
        $reqs['token_custom'] = '.+';
        $route->setRequirements($reqs);
      }
      if ($route = $collection->get('entity.token_custom.delete_form')) {
        $reqs = $route->getRequirements();
        $reqs['token_custom'] = '.+';
        $route->setRequirements($reqs);
      }
    }
  }

}
