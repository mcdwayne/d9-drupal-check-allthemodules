<?php

namespace Drupal\twig_link_attributes\Twig\Extension;

/**
 * Class DefaultService.
 * @package Drupal\MyTwigModule
 */
class TwigLinkAttributes extends \Twig_Extension {

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'link_attributes';
  }


  /**
   * {@inheritdoc}
   */
  public function getFilters() {
    return array(
      new \Twig_SimpleFilter('link_attributes', [$this, 'setLinkAttributes']),
    );
  }

  /**
   * setOption attributes on the item url.
   * @param $item_url
   * @param $values
   * @return
   */
  public function setLinkAttributes($item_url, $values) {
    $current = $item_url->getOption('attributes') ?: [];
    if (isset($current) && !empty($current)) {
      $attributes = array_merge_recursive($values, $current);
    }
    else {
      $attributes = $values;
    }
    return $item_url->setOption('attributes', $attributes);
  }

}
