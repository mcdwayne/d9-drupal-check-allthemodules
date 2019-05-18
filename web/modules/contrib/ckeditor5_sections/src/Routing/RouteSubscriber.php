<?php

namespace Drupal\ckeditor5_sections\Routing;

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
    // Use altered form for media uploading.
    if ($route = $collection->get('media_library.upload')) {
      $route->setDefaults(['_form' => '\Drupal\ckeditor5_sections\Form\SectionsMediaLibraryUploadForm']);
    }
  }

}
