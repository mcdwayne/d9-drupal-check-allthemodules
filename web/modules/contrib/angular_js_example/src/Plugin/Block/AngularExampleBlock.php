<?php

namespace Drupal\angular_js_example\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'AngularExample' block.
 *
 * @Block(
 *  id = "angularexample",
 *  admin_label = @Translation("AngularJs Example"),
 * )
 */
class AngularExampleBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    global $base_url;
    $build['#attached']['library'][] = 'angular_js_example/angular.angularjs';
    $build['#attached']['library'][] = 'angular_js_example/angular_js.example';
    $build['#attached']['drupalSettings']['angular_js_example']['url_base'] = $base_url;
    $build['#theme'] = 'angularjs_form';
    return $build;
  }

}
