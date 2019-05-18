<?php

namespace Drupal\piwik_actions;

use Drupal\Core\Url;

/**
 * Information about site visits.
 */
class Visits {

  /**
   * {@inheritdoc}
   */
  public function __construct($endpoint_base) {
    $this->endpoint_base = $endpoint_base;
  }

  /**
   * Return a bunch of action rows, with custom variables.
   */
  public function getActions($filters) {
    $visits = $this->getLastVisitsDetails($filters['start_date'], $filters['end_date']);
    if (!$visits) {
      return FALSE;
    }
    $action_type = isset($filters['action']) ? $filters['action'] : NULL;
    $rows = [];
    $base_url = Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString();

    foreach ($visits as $visit) {
      $visit_details = [];
      if (isset($visit['customVariables']) && is_array($visit['customVariables'])) {
        foreach ($visit['customVariables'] as $i => $var) {
          $key = 'customVariableName' . $i;
          $val = 'customVariableValue' . $i;
          $visit_details[$var[$key]] = $var[$val];
        }
      }
      if (isset($visit['actionDetails']) && is_array($visit['actionDetails'])) {
        foreach ($visit['actionDetails'] as $action) {
          // Filter action.
          if ($action_type && $action['type'] != $action_type) {
            continue;
          }
          $row_data = [
            'url' => $this->removeBaseUrl($action['url'], $base_url),
            'time' => date('Y-m-d g:ia', $action['timestamp']),
          ] + $visit_details;
          $rows[] = $row_data;
        }
      }
    }

    \Drupal::service('module_handler')->alter('piwik_actions_data', $rows);

    return $rows;
  }

  /**
   * Get a piwik endpoint.
   */
  private function getEndpoint($method, $start_date, $end_date) {
    if (empty($end_date)) {
      $end_date = $start_date;
    }
    $params = [
      'method' => $method,
      'date' => $start_date . ',' . $end_date,
    ];
    return $this->endpoint_base . '&' . http_build_query($params);
  }

  /**
   * Return last visits from endpoint.
   */
  private function getLastVisitsDetails($start_date, $end_date) {
    $url = $this->getEndpoint('Live.getLastVisitsDetails', $start_date, $end_date);
    return $this->getJson($url);
  }

  /**
   * Pull JSON from endpoint.
   */
  private function getJson($url) {
    $client = \Drupal::httpClient();
    try {
      $response = $client->get($url);
    }
    catch (\GuzzleHttp\Exception\RequestException $e) {
      drupal_set_message($e->getMessage(), 'error');
      return FALSE;
    }
    return json_decode($response->getBody(), TRUE);
  }

  /**
   * Remove the base URL if it's there.
   */
  private function removeBaseUrl($url, $base) {
    $base = rtrim($base, '/');
    if (strpos($url, $base) === 0) {
      $url = str_replace($base, "", $url);
    }
    return $url;
  }

}
