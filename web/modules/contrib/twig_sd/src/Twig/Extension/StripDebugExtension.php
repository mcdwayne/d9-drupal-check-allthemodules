<?php

namespace Drupal\twig_strip_debug\Twig\Extension;

/**
 * Provides a filter to strip debugging from #Markup
 */
class StripDebugExtension extends \Twig_Extension {

  /**
   * {@inheritdoc}
   */
  public function getFilters() {
    return array(
      new \Twig_SimpleFilter('strip_debug', [$this, 'stripComments']),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'twig_strip_debug';
  }


  public function stripComments($content) {
    $content = array('#markup' => preg_replace('/<!--(.|\s)*?-->/', '', $content)) ;
    return $content;
  }

}
