<?php

namespace Drupal\reactify_dashboard_stats\Plugin\rest\Resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;

/**
 * Provides Dashboard statistics endpoint.
 *
 * @RestResource (
 *   id = "reactify_dashboard_stats",
 *   label = @Translation("Dashboard statistics"),
 *   uri_paths = {
 *     "canonical" = "/api/dashboard_stats"
 *   }
 * )
 */
class DashboardStatsResource extends ResourceBase {

  /**
   * Entity count.
   *
   * @param string $entity_type
   *   Entity type id, for example, 'user', 'node' etc.
   *
   * @return array|int
   *   Returns an integer for count queries or an array of ids.
   */
  protected function countTotal($entity_type) {
    $resultQuery = \Drupal::entityQuery($entity_type);
    $resultTotal = $resultQuery->count()->execute();
    return $resultTotal;
  }

  /**
   * {@inheritdoc}
   */
  public function get() {
    // Stats array.
    $stats = [];
    $usersTotal = $this->countTotal('user');
    $contentCount = $this->countTotal('node');
    $commentCount = $this->countTotal('comment');
    try {
      $clientsTotal = $this->countTotal('client');
    }
    catch (PluginNotFoundException $e) {
      $clientsTotal = NULL;
    };

    if (!empty($usersTotal)) {
      $stats['usersTotal'] = $usersTotal;
    }

    if (!empty($contentCount)) {
      $stats['contentTotal'] = $contentCount;
    }

    if (!empty($commentCount)) {
      $stats['commentTotal'] = $commentCount;
    }

    if (!empty($clientsTotal)) {
      $stats['clientsTotal'] = $clientsTotal;
    }

    $response = new ResourceResponse($stats);
    $response->addCacheableDependency($stats);
    return $response;
  }

}
