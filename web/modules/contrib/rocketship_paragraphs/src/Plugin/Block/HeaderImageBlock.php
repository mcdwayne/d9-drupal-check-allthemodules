<?php

namespace Drupal\rocketship_paragraphs\Plugin\Block;

use Drupal\node\NodeInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;

/**
 * Provides a header image block.
 *
 * @Block(
 *   id = "header_image_block",
 *   admin_label = @Translation("Header image block"),
 * )
 */
class HeaderImageBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Check on which node we are.
    $node = \Drupal::routeMatch()->getParameter('node');
    if ($node instanceof NodeInterface) {
      // Check if field_header_image exists and is not empty.
      if (isset($node->field_header_image) && !$node->field_header_image->isEmpty()) {
        // Return view mode header image.
        $view_builder = \Drupal::entityTypeManager()->getViewBuilder('node');
        $build = $view_builder->view($node, 'header_image');
        return $build;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    // With this when your node change your block will rebuild.
    if ($node = \Drupal::routeMatch()->getParameter('node')) {
      // If there is node add its cachetag.
      return Cache::mergeTags(parent::getCacheTags(), ['node:' . $node->id()]);
    }
    else {
      // Return default tags instead.
      return parent::getCacheTags();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    // If you depends on \Drupal::routeMatch()
    // you must set context of this block with 'route' context tag.
    // Every new route this block will rebuild.
    return Cache::mergeContexts(parent::getCacheContexts(), ['route']);
  }

}
