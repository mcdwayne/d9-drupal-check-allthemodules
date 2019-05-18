<?php

namespace Drupal\die_in_twig;

/**
 * Class implements the die function by extentading Twig Extension.
 */
class DieInTwigExtension extends \Twig_Extension {

  /**
   * {@inheritdoc}
   *
   * Unique name of the extension.
   */
  public function getName() {
    return 'die_in_twig.dieintwigextension';
  }

  /**
   * {@inheritdoc}
   *
   * Return die twig function to Drupal.
   */
  public function getFunctions() {
    return [
      new \Twig_SimpleFunction('die_in_twig', [$this, 'dieInTwig']),
    ];
  }

  /**
   * Callable function for twig extension die_in_twig.
   *
   * @See getFunctions.
   */
  public function dieInTwig() {
    return die("End of script");
  }

}
