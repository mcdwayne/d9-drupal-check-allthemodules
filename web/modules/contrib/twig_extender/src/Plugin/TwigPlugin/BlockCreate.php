<?php

namespace Drupal\twig_extender\Plugin\TwigPlugin;

use Drupal\twig_extender\Plugin\Twig\TwigPluginBase;

/**
 * The plugin for check authenticated user.
 *
 * @TwigPlugin(
 *   id = "twig_extender_get_block_instance",
 *   label = @Translation("Get a block instance"),
 *   type = "function",
 *   name = "block_create",
 *   function = "getBlock"
 * )
 */
class BlockCreate extends TwigPluginBase {

  /**
   * Implementation for render block.
   */
  public function getBlock($pluginId, $conf = []) {
    $block_plugin_manager = \Drupal::service('plugin.manager.block');
    /** @var \Drupal\language\Plugin\Block\LanguageBlock $language_block */
    $block = $block_plugin_manager->createInstance($pluginId, $conf);
    if (!$block) {
      return;
    }
    $build = $block->build();
    return \Drupal::service('renderer')->render($build);
  }

}
