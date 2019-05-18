<?php

namespace Drupal\lgp;

/**
 * Twig extension with some useful functions and filters.
 */
class TwigExtension extends \Twig_Extension {

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    return [
      new \Twig_SimpleFunction('lp', 'lp'),
      new \Twig_SimpleFunction('lx', 'lx'),
      new \Twig_SimpleFunction('ld', 'ld'),
      new \Twig_SimpleFunction('lbt', 'lbt'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'lgp';
  }

}
