<?php

namespace Drupal\twig_drupal_children_extension\Twig\Extension;

/**
 * Class DrupalChildren.
 */
class DrupalChildren extends \Twig_Extension
{
  /**
   * Generates a list of all Twig filters that this extension provides.
   */
  public function getFilters()
  {
    return [
      new \Twig_SimpleFilter('children', array($this, 'children')),
    ];
  }

  /**
   * Get a unique name.
   */
  public function getName()
  {
    return 'drupol.twig_drupal_children_extension';
  }

  /**
   * Get the children of a field (FieldItemList)
   */
  public static function children($variables)
  {
    $return = NULL;

    if (is_array($variables['#items']) &&
      !empty($variables['#items']) &&
      $variables['#items']->count() > 0) {
      $return = $variables['#items']->getIterator();
    }

    return $return;
  }
}
