<?php

namespace Drupal\media_expire\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    $canonical = $collection->get('entity.media.canonical');
    $edit = $collection->get('entity.media.edit_form');
    if ($canonical && $edit && $canonical->getPath() !== $edit->getPath()) {
      $canonical->setDefault('_controller', 'Drupal\media_expire\Controller\MediaViewController::view');
    }
  }

}
