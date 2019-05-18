<?php

namespace Drupal\drd\Agent\Action\V8;

use Drupal\block\BlockListBuilder;

/**
 * Provides a 'Blocks' code.
 */
class Blocks extends Base {

  /**
   * Collect all available blocks and return them as a list.
   *
   * @return array
   *   List of block indexed by provider and ID, showing their label as value.
   */
  private function listBlocks() {
    $block_list = [];

    $blb = BlockListBuilder::createInstance(\Drupal::getContainer(), \Drupal::entityTypeManager()->getDefinition('block'));
    /** @var \Drupal\block\BlockInterface[] $blocks */
    $blocks = $blb->load();
    foreach ($blocks as $id => $block) {
      $definition = $block->getPlugin()->getPluginDefinition();
      $block_list[$definition['provider']][$id] = $block->label();
    }

    return $block_list;
  }

  /**
   * Load and return the rendered block.
   *
   * @param string $module
   *   Provider module of the block.
   * @param string $delta
   *   ID of the block from the given provider.
   *
   * @return \Drupal\Component\Render\MarkupInterface|array
   *   Rendered result of the block or an empty array.
   */
  private function renderBlock($module, $delta) {
    $blb = BlockListBuilder::createInstance(\Drupal::getContainer(), \Drupal::entityTypeManager()->getDefinition('block'));
    /** @var \Drupal\block\BlockInterface[] $blocks */
    $blocks = $blb->load();
    if (isset($blocks[$delta])) {
      $block = $blocks[$delta];
      $build = $block->getPlugin()->build();
      return \Drupal::service('renderer')->renderPlain($build);
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    if (!\Drupal::moduleHandler()->moduleExists('block')) {
      return [];
    }
    $args = $this->getArguments();
    if (!empty($args['module']) && !empty($args['delta'])) {
      return [
        'data' => $this->renderBlock($args['module'], $args['delta']),
      ];
    }
    return $this->listBlocks();
  }

}
