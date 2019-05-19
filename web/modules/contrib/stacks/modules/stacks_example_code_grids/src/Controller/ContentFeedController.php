<?php
/**
 * @file
 * Contains \Drupal\stacks_example_code_grids\Controller\ContentFeedController
 */

namespace Drupal\stacks_example_code_grids\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class ContentFeedController.
 * @package Drupal\stacks_example_code_grids\Controller
 */
class ContentFeedController extends ControllerBase {

  public function displayGrid() {
    $widget_type_manager = \Drupal::service('plugin.manager.stacks_widget_type');

    // We need to set a unique grid id int. The key is that all grids displayed
    // on the page need to have a unique id.
    $content_feed = $widget_type_manager->createInstance('content_feed_code', ['unique_id' => 1500]);

    // Put together the render array.
    $render_array = [];
    $content_feed->modifyRenderArray($render_array);

    return $render_array;
  }

}