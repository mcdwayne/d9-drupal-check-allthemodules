<?php

namespace Drupal\stacks\TwigExtension;

use Drupal;

/**
 * Class Pagination.
 * @package Drupal\stacks\TwigExtension
 */
class Pagination extends \Twig_Extension {

  /**
   * Generates a list of all Twig filters that this extension defines.
   */
  public function getFilters() {
    return [
      new \Twig_SimpleFilter('pagination', [$this, 'getPagination']),
    ];
  }

  /**
   * Gets a unique identifier for this Twig extension.
   */
  public function getName() {
    return 'stacks_pagination.twig_extension';
  }

  /**
   * Returns html for pagination.
   */
  public static function getPagination($pager_type = 'default', $entity) {

    $entity_id = $entity['entity_id'];
    $render_array = [
      '#type' => 'pager',
      '#which_pager' => $pager_type,
      '#element' => $entity_id,
      '#module' => 'stacks'
    ];

    $session = \Drupal::service('session');

    if (!$session->isStarted()) {
      $session->start();
    }

    $pager = $session->get('pager_elements');

    if (isset($pager[$entity_id])) {
      $render_array['#quantity'] = $pager[$entity_id];
    }

    return Drupal::service('renderer')->render($render_array);
  }

}
