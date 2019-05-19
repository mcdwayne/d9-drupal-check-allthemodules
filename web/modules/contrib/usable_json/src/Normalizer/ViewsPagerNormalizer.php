<?php

namespace Drupal\usable_json\Normalizer;

use Drupal\serialization\Normalizer\ComplexDataNormalizer;
use Drupal\views\Plugin\views\pager\PagerPluginBase;

/**
 * Defines a class for normalizing PagerNormalizer.
 */
class ViewsPagerNormalizer extends ComplexDataNormalizer {

  /**
   * The formats that the Normalizer can handle.
   *
   * @var array
   */
  protected $format = ['usable_json'];

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = PagerPluginBase::class;

  /**
   * {@inheritdoc}
   */
  public function normalize($pager, $format = NULL, array $context = []) {
    /* @var \Drupal\views\Plugin\views\pager\PagerPluginBase $pager */
    $values = [
      'identifier' => 'page',
      'current_page' => $pager->getCurrentPage(),
      'items_per_page' => $pager->getItemsPerPage(),
      'total_items' => $pager->getTotalItems(),
      'total_pages' => ceil($pager->getTotalItems() / $pager->getItemsPerPage()),
    ];

    return $values;
  }

}
