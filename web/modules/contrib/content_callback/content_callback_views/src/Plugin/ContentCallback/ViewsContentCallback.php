<?php

/**
 * @file
 * Contains \Drupal\content_callback_views\Plugin\ContentCallback\ViewsContentCallback.
 */

namespace Drupal\content_callback_views\Plugin\ContentCallback;

use Drupal\content_callback\Annotation\ContentCallback;
use Drupal\content_callback\Plugin\ContentCallback\PluginBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Annotation\Translation;

/**
 * Defines a generic dynamic code field.
 *
 * @ContentCallback(
 *   id = "views_content_callback",
 *   deriver = "Drupal\content_callback_views\Plugin\Derivative\ViewsContentCallback"
 * )
 */
class ViewsContentCallback extends PluginBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Fetch the definition
    $definition = $this->getPluginDefinition();
    $view_name = $definition['view_name'];
    $view_display = $definition['view_display'];

    $build['view'] = array(
      '#type' => 'view',
      '#name' => $view_name,
      '#display_id' => $view_display,
    );

    return $build;
  }


}
