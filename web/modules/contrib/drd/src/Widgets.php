<?php

namespace Drupal\drd;

/**
 * Easy access to the DRD widgets.
 */
class Widgets {

  /**
   * Get a list of all available widgets for the dashboard.
   *
   * @param bool $render
   *   Whether to render the widget's content.
   *
   * @return \Drupal\drd\Plugin\Block\Base[]
   *   List of widgets.
   */
  public function findWidgets($render) {
    $widgets = [];
    /** @var \Drupal\Core\Block\BlockManager $block_manager */
    $block_manager = \Drupal::service('plugin.manager.block');
    foreach ($block_manager->getDefinitions() as $definition) {
      if (isset($definition['tags']) && in_array('drd_widget', $definition['tags'])) {
        if (!isset($definition['weight'])) {
          $definition['weight'] = 0;
        }
        if ($render) {
          /** @var \Drupal\drd\Plugin\Block\Base $block */
          $block = $block_manager->createInstance($definition['id'], []);
          if ($block->access(\Drupal::currentUser())) {
            $widgets[$definition['weight']] = [
              '#type' => 'container',
              '#attributes' => [
                'class' => ['drd-widget'],
              ],
              '#weight' => $definition['weight'],
            ] + $block->build();
          }
        }
        else {
          $widgets[$definition['weight']] = $definition;
        }
      }
    }
    ksort($widgets);
    return $widgets;
  }

  /**
   * Get a list of all rendered widgets for the dashboard.
   *
   * @return \Drupal\drd\Plugin\Block\Base[]
   *   List of rendered widgets.
   */
  public function getWidgets() {
    return $this->findWidgets(TRUE);
  }

}
