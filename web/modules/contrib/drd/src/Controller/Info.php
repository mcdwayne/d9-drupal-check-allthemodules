<?php

namespace Drupal\drd\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;

/**
 * Returns responses for DRD Info routes.
 */
class Info implements ContainerInjectionInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static();
  }

  /**
   * Displays DRD status report.
   *
   * @return array
   *   A render array.
   */
  public function status() {
    return [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['drd-dashboard'],
      ],
    ] + \Drupal::service('drd.widgets')->getWidgets();
  }

}
