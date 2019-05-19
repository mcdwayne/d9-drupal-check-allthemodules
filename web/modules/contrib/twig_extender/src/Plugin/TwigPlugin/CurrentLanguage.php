<?php

namespace Drupal\twig_extender\Plugin\TwigPlugin;

use Drupal\twig_extender\Plugin\Twig\TwigPluginBase;

/**
 * The plugin for check authenticated user.
 *
 * @TwigPlugin(
 *   id = "twig_extender_language",
 *   label = @Translation("Get a block"),
 *   type = "function",
 *   name = "language",
 *   function = "getLanguage"
 * )
 */
class CurrentLanguage extends TwigPluginBase {

  /**
   * Returns current language.
   */
  public function getLanguage() {
    return \Drupal::languageManager()->getCurrentLanguage();
  }

}
