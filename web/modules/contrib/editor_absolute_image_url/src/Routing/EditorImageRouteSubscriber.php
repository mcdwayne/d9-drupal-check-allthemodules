<?php

namespace Drupal\editor_absolute_image_url\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class EditorImageRouteSubscriber.
 *
 * Listens to the dynamic route events.
 */
class EditorImageRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('editor.image_dialog')) {
      $route->setDefault('_form', 'Drupal\editor_absolute_image_url\Form\EditorImageDialogWithAbsolute');
    }
  }

}
