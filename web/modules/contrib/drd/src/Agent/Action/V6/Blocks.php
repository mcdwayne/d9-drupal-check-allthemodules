<?php

namespace Drupal\drd\Agent\Action\V6;

/**
 * Provides a 'Blocks' code.
 */
class Blocks extends Base {

  private function listBlocks() {
    $block_list = array();
    foreach (module_implements('block_info') as $module) {
      foreach (module_invoke($module, 'block_info') as $delta => $block) {
        $block_list[$module][$delta] = $block['info'];
      }
    }
    return $block_list;
  }

  private function renderBlock($module, $delta) {
    $block = (object) module_invoke($module, 'block', 'view', $delta);
    return theme('block', $block);
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    if(!module_exists('block')) {
      return array();
    }
    $args = $this->getArguments();
    if (!empty($args['module']) && !empty($args['delta'])) {
      return array(
        'data' => $this->renderBlock($args['module'], $args['delta']),
      );
    }
    return $this->listBlocks();
  }

}
