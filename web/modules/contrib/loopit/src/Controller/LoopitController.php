<?php

/**
 * @file
 * Contains \Drupal\loopit\Controller\LoopitController.
 */

namespace Drupal\loopit\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\loopit\Aggregate\AggregateEntity;
use Drupal\loopit\Aggregate\AggregatePlugin;
use Drupal\loopit\Aggregate\AggregateService;

/**
 * Returns responses for devel module routes.
 *
 * @see \Drupal\devel\Controller\DevelController
 */
class LoopitController extends ControllerBase {


  public function entityClasses($pattern) {

    if ($pattern == 'all') {
      $subset_array_parents = NULL;
    }
    else {
      // categories: __contrib__, __custom__,
      // Or using provider pattern: __block*__, __*content__,
      // Or on the full class name: *Type for (Drupal\block_content\Entity\BlockContentType, Drupal\Core\Config\Entity\ConfigEntityType, ...)
      $subset_array_parents = [
        '*/\*handlers' => $pattern,
        '*/\*class' => $pattern,
        '*/\*provider' => $pattern,
      ];

    }

    $aggreg = AggregateEntity::getClasses($subset_array_parents);
    $context = $aggreg->getContext();

    return [
      $this->exportAsRenderable($context['entity_id_centric'], 'Entity classes'),
      $this->exportAsRenderable($context['entity_handler_centric'], 'Entity classes by handlers'),
    ];
  }

  public function pluginClasses($pattern) {

    if ($pattern == 'all') {
      $leaf_match = ['*class' => '*', '*provider' => '*', '*deriver' => '*'];
    }
    else {
      $leaf_match = [
        '*class' => $pattern,
        '*provider' => $pattern,
        '*deriver' => $pattern,
      ];
    }

    $aggreg = AggregatePlugin::getClasses($leaf_match);
    $context = $aggreg->getContext();

    return [
      $this->exportAsRenderable($context['plugin_id_centric'], 'Plugin classes'),
      $this->exportAsRenderable($context['plugin_handler_shared'], 'Shared plugin classes'),
      $this->exportAsRenderable($context['plugin_handler_unique'], 'Unique plugin classes'),
    ];
  }

  public function serviceClasses($pattern) {

    if ($pattern == 'all') {
      $leaf_match = ['*class' => '*', '*Class' => '*'];
    }
    else {
      $leaf_match = ['*class' => $pattern, '*Class' => $pattern];
    }

    $aggreg = AggregateService::getClasses($leaf_match);
    $context = $aggreg->getContext();

    return [
      $this->exportAsRenderable($context['service_id_references'], 'Service classes and references'),
      $this->exportAsRenderable($context['service_only_references'], 'Service only references (because filtered classes)'),
    ];
  }

  /**
   * Export as renderable without array cast.
   *
   * @see \Drupal\loopit_krumo\Plugin\Devel\Dumper\KrumoDebug::exportAsRenderable()
   */
  public function exportAsRenderable($input, $name = NULL) {
    $output['container'] = [
      '#type' => 'details',
      '#title' => $name ? : $this->t('Variable'),
      '#attached' => [
        'library' => ['devel/devel']
      ],
      '#attributes' => [
        'class' => ['container-inline', 'devel-dumper', 'devel-selectable'],
      ],
      'export' => [
        '#markup' => \Drupal::service('devel.dumper')->export($input),
      ],
    ];

    return $output;
  }

  public function viewsData() {
    /** @var $views_data_service \Drupal\views\ViewsData */
    $views_data_service = \Drupal::service('views.views_data');
    $views_data = $views_data_service->getAll();
    ksort($views_data);
    return \Drupal::service('devel.dumper')->exportAsRenderable($views_data);
  }
}
