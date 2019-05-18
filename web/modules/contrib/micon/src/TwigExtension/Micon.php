<?php

namespace Drupal\micon\TwigExtension;

/**
 * A class providing Micon Twig extensions.
 *
 * This provides a Twig extension that registers the {{ micon() }} extension
 * to Twig.
 */
class Micon extends \Twig_Extension {

  /**
   * Gets a unique identifier for this Twig extension.
   *
   * @return string
   *   A unique identifier for this Twig extension.
   */
  public function getName() {
    return 'twig.micon';
  }

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    return array(
      new \Twig_SimpleFunction('micon', array($this, 'renderIcon')),
    );
  }

  /**
   * Render the icon.
   *
   * @param string $icon
   *   The icon_id of the icon to render.
   *
   * @return mixed[]
   *   A render array.
   */
  public static function renderIcon($icon) {
    $build = [
      '#theme' => 'micon_icon',
      '#icon' => $icon,
    ];
    return $build;
  }

}
