<?php

namespace Drupal\fitbit_views\Plugin\FitbitBaseTableEndpoint;

use Drupal\fitbit_views\FitbitBaseTableEndpointBase;
use League\OAuth2\Client\Token\AccessToken;

/**
 * Fitbit Activity Time Series endpoint.
 *
 * @FitbitBaseTableEndpoint(
 *   id = "activity_time_series",
 *   name = @Translation("Fitbit activity time series"),
 *   description = @Translation("Retrieves activity data for a given time period.")
 * )
 */
class ActivityTimeSeries extends FitbitBaseTableEndpointBase {

  /**
   * {@inheritdoc}
   */
  public function getRowByAccessToken(AccessToken $access_token, $arguments = NULL) {
    // Defaults
    $resource_path = isset($arguments['resource_path']) ? $arguments['resource_path'] : 'activities/steps';
    $activity_date_range = isset($arguments['activity_date_range']) ? $arguments['activity_date_range'] : ['date' => 'today', 'period' => '7d'];
    if ($data = $this->fitbitClient->getActivityTimeSeries($access_token, $resource_path, $activity_date_range)) {
      if (isset($data[str_replace('/', '-', $resource_path)])) {
        $sum = 0;
        foreach ($data[str_replace('/', '-', $resource_path)] as $value) {
          if (isset($value['value'])) {
            $sum += $value['value'];
          }
        }
        return ['activity_sum' => $sum];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFields() {
    return [
      'activity_sum' => [
        'title' => $this->t('Activity sum'),
        'field' => [
          'id' => 'numeric',
        ],
      ],
      'resource_path' => [
        'title' => $this->t('Resource path'),
        'filter' => [
          'id' => 'fitbit_resource_path',
        ],
      ],
      'activity_date_range' => [
        'title' => $this->t('Activity date range'),
        'filter' => [
          'id' => 'fitbit_activity_date_range',
        ],
      ],
    ];
  }
}
