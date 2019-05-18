<?php

namespace Drupal\context_region_embed;

use Drupal\context\ContextManager;

/**
 * Renders blocks in a certain region using context.
 *
 * Note: This might be a performance impact so better know what you do.
 */
class ContextRegionRenderer {

  /**
   * The context manager.
   *
   * @var \Drupal\context\ContextManager
   */
  protected $contextManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(ContextManager $contextManager) {
    $this->contextManager = $contextManager;
  }

  /**
   * Executes the plugin.
   *
   * @param array $regions
   *   The regions to render.
   *
   * @return array
   *   The render array.
   */
  public function render(array $regions) {
    $build = [];
    foreach ($this->contextManager->getActiveReactions('blocks') as $reaction) {
      /** @var \Drupal\context\Plugin\ContextReaction\Blocks $reaction */
      $build = $reaction->execute($build, NULL, []);
    }
    return array_intersect_key($build, array_flip($regions));
  }

}
