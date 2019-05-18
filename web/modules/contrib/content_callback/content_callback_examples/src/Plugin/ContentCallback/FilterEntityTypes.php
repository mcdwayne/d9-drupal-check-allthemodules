<?php

/**
 * @file
 * Contains \Drupal\content_callback_examples\Plugin\ContentCallback\FilterEntityTypes.
 */

namespace Drupal\content_callback_examples\Plugin\ContentCallback;

use Drupal\Core\Annotation\Translation;
use Drupal\content_callback\Annotation\ContentCallback;
use Drupal\content_callback\Plugin\ContentCallback\PluginBase;

/**
 * A test callback
 *
 * @ContentCallback(
 *   id = "example_filter_entity_types",
 *   title = @Translation("Filter entity types"),
 *   entity_types =  {
 *     "node"
 *   }
 * )
 */
class FilterEntityTypes extends PluginBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    $build = array(
      '#markup' => 'basic example, will only show up on nodes',
    );

    return $build;
  }
}
