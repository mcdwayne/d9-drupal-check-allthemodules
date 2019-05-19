<?php

namespace Drupal\toolbar_region\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides block plugin definitions for Toolbar region pseudo blocks.
 *
 * @see \Drupal\navbar)region\Plugin\Block\ToolbarRegionPseudoBlock
 */
class ToolbarRegionPseudoBlock extends DeriverBase implements ContainerDeriverInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    // Get toolbar items from all modules that implement hook_toolbar().
    $items = \Drupal::moduleHandler()->invokeAll('toolbar');
    // Allow for altering of hook_toolbar().
    $modules = \Drupal::moduleHandler()->getImplementations('toolbar_alter');
    foreach ($modules as $module) {
      if ($module != 'toolbar_region') {
        \Drupal::moduleHandler()->invoke($module, 'toolbar_alter', $items);
      }
    }

    foreach ($items as $delta => $item) {
      $this->derivatives[$delta] = $base_plugin_definition;
      $this->derivatives[$delta]['admin_label'] = isset($item['tab']['#title']) ? $item['tab']['#title'] : $item['tab']['#value'];
    }

    return $this->derivatives;
  }

}
