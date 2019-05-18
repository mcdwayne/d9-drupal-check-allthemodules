<?php

namespace Drupal\partial\TwigExtension;

class PartialTwigExtension extends \Twig_Extension {

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'partial';
  }

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    return array(
      new \Twig_SimpleFunction('partial', array($this, 'partialFunction'), array(
        'name' => NULL,
        'context' => [],
      )),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFilters() {
    return array(
      new \Twig_SimpleFilter('partial', array($this, 'partialfilter'), array(
        'name' => NULL,
      )),
    );
  }

  /**
   * Replaces all instances of "animal" in a string with "plant".
   */
  public function partialFunction($name, $context = array()) {
    $build['partial'] = [
      '#theme' => 'partial',
      '#name' => $name,
      '#context' => $context,
    ];
    return render($build);
  }

  public function partialFilter($context = array(), $name) {
    $build['partial'] = [
      '#theme' => 'partial',
      '#name' => $name,
      '#context' => $context,
    ];
    return render($build);
  }

}
