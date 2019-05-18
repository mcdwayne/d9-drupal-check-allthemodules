<?php

/**
 * @file
 * Contains \Drupal\content_callback_examples\Plugin\ContentCallback\Basic.
 */

namespace Drupal\content_callback_examples\Plugin\ContentCallback;

use Drupal\Core\Annotation\Translation;
use Drupal\content_callback\Annotation\ContentCallback;
use Drupal\content_callback\Plugin\ContentCallback\PluginBase;

/**
 * A test callback
 *
 * @ContentCallback(
 *   id = "example_basic",
 *   title = @Translation("Basic example")
 * )
 */
class Basic extends PluginBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    $build = array(
      '#markup' => 'basic example',
    );

    return $build;
  }
}
