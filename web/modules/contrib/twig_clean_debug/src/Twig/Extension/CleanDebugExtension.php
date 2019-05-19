<?php

namespace Drupal\twig_clean_debug\Twig\Extension;

/**
 * Provides field value filters for Twig templates.
 */
class CleanDebugExtension extends \Twig_Extension {

  /**
   * {@inheritdoc}
   */
  public function getFilters() {
    return array(
      new \Twig_SimpleFilter('clean_debug', [$this, 'getCleanDebug']),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'twig_clean_debug';
  }

  /**
   * @param $variable
   * @return mixed
   * @throws \Exception
   */
  public function getCleanDebug($variable) {
    $twig_debug = \Drupal::service('twig')->isDebug();
    if ($twig_debug == TRUE) {
      $rendered_var = \Drupal::service('renderer')->render($variable);
      $output = preg_replace("#<!--[^-]*(?:-(?!->)[^-]*)*-->#", '', preg_replace(array('/\r/', '/\n/'), '', $rendered_var));
      return preg_replace('/\s+(?![^<>]*>)/x', '', $output);
    }
    else {
      throw new \Exception('Calling twig filter clean_debug not allowed when the site in not in twig debug mode, remove the filter from your templates.');
    }
  }
}
