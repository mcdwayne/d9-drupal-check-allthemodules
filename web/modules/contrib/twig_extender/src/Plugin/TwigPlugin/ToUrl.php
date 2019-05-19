<?php

namespace Drupal\twig_extender\Plugin\TwigPlugin;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\twig_extender\Plugin\Twig\TwigPluginBase;

/**
 * The plugin for render a url string of url object or ContentEntityBase object.
 *
 * @TwigPlugin(
 *   id = "twig_extender_to_url",
 *   label = @Translation("Get path alias by path"),
 *   type = "filter",
 *   name = "to_url",
 *   function = "getUrl"
 * )
 */
class ToUrl extends TwigPluginBase {

  /**
   * Implementation for render block.
   */
  public function getUrl($url, $absolute = FALSE) {

    if (is_a($url, ContentEntityBase::class)) {
      $url = $url->toUrl();
    }
    if (is_a($url, 'Drupal\Core\Url')) {
      $url = $url->toString();
    }
    if (gettype($url) !== 'string') {
      throw new \Exception('Could not convert object to a path alias');
    }
    return \Drupal::service('path.alias_manager')->getAliasByPath($url);
  }

}
