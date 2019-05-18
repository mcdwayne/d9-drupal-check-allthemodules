<?php

/**
 * @file
 * Contains \Drupal\md_fontello\TwigExtension\MDIcon.
 */

namespace Drupal\md_fontello\TwigExtension;

class MDIcon extends \Twig_Extension {

  /**
   * Gets a unique identifier for this Twig extension.
   *
   * @return string
   *   A unique identifier for this Twig extension.
   */
  public function getName() {
    return 'twig.md_icon';
  }


  public function getFunctions() {
    return array(
      new \Twig_SimpleFunction('md_icon', array($this, 'renderIcon')),
    );
  }


  public static function renderIcon($name, $icon) {
    $build = [
      '#theme' => 'md_icon',
      '#name' => $name,
      '#icon' => $icon
    ];
    return $build;
  }

}