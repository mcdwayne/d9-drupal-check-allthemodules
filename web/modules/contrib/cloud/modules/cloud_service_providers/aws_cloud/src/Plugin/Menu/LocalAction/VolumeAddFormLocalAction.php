<?php

namespace Drupal\aws_cloud\Plugin\Menu\LocalAction;

use Drupal\Core\Menu\LocalActionDefault;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * The local action for SnapshotAddForm.
 */
class VolumeAddFormLocalAction extends LocalActionDefault {

  /**
   * {@inheritdoc}
   */
  public function getRouteParameters(RouteMatchInterface $route_match) {
    $parameters = parent::getRouteParameters($route_match);

    // Add snapshot parameter.
    if ($route_match->getRouteName() == 'entity.aws_cloud_snapshot.edit_form'
        || $route_match->getRouteName() == 'entity.aws_cloud_snapshot.canonical') {

      $snapshot = $route_match->getParameter('aws_cloud_snapshot');
      if ($snapshot != NULL) {
        $parameters['snapshot_id'] = $snapshot->getSnapshotId();
      }
    }

    return $parameters;
  }

}
