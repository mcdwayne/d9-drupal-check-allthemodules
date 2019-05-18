<?php

namespace Drupal\loopit\Controller;

use Drupal\devel\Controller\ContainerInfoController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\loopit\Aggregate\AggregateObject;

class LoopitServiceController extends ContainerInfoController {

  public function serviceDetail($service_id) {

    // Special handling of plugins
    if (strpos($service_id, 'plugin.manager.') === 0) {

      $output = parent::serviceDetail($service_id);

      $instance = $this->container->get($service_id, ContainerInterface::NULL_ON_INVALID_REFERENCE);
      if ($instance === NULL) {
        throw new NotFoundHttpException();
      }
      $instance_casted = AggregateObject::castFast($instance);

      if (empty($instance_casted['*definitions'])) {
        $defs = $instance->getDefinitions();
        $instance_casted['definitions'] =  AggregateObject::castFast($defs);
      }

      $output['instance'] = $this->exportAsRenderable($instance_casted, $this->t('Instance'));
    }
    else {
      $output = parent::serviceDetail($service_id);
    }

    return $output;
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
}