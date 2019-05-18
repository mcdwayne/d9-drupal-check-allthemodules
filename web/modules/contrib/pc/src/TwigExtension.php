<?php

namespace Drupal\pc;

/**
 * Class TwigExtension.
 */
class TwigExtension extends \Twig_Extension {

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'pc';
  }

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    return ['pc' => new \Twig_SimpleFunction('pc', 'pc')];
  }

}
