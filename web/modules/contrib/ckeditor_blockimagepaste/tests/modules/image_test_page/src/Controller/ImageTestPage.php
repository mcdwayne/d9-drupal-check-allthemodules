<?php

namespace Drupal\image_test_page\Controller;

/**
 * Controller routines for image_test_page routes.
 */
class ImageTestPage {

  /**
   * Returns a test page with an image.
   */
  public function testPage() {

    $module_handler = \Drupal::service('module_handler');
    $module_path = $module_handler->getModule('image_test_page')->getPath();

    return [
      '#theme' => 'image',
      '#uri' => $module_path . '/images/doggo.jpg',
      '#alt' => 'Photo by @charlesdeluvio on Unsplash',
      '#width' => 500,
      '#attributes' => ['id' => 'test-image'],
    ];
  }

}
