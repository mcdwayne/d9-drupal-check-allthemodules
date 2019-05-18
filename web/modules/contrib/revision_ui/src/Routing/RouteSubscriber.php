<?php

/**
 * @file
 * Contains \Drupal\revision_ui\Routing\RouteSubscriber.
 */

namespace Drupal\revision_ui\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('node.revision_revert_confirm')) {
      $route->setDefault('_form', '\Drupal\revision_ui\Form\NodeRevisionRevertForm');
    }
    if ($route = $collection->get('node.revision_revert_translation_confirm')) {
      $route->setDefault('_form', '\Drupal\revision_ui\Form\NodeRevisionRevertTranslationForm');
    }
  }

}
