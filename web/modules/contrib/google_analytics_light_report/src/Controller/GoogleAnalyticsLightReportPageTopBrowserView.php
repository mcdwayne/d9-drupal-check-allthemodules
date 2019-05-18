<?php

namespace Drupal\google_analytics_light_report\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class GoogleAnalyticsLightReportPageTopBrowserView.
 */
class GoogleAnalyticsLightReportPageTopBrowserView extends ControllerBase {

  /**
   * It will return json data.
   *
   * @return json
   *   Return json output.
   */
  public function content() {
    $library_exist = google_analytics_light_report_library_exists();
    $profileid = '';
    if (!empty($library_exist)) {
      $analytics = google_analytics_light_report_initialize_analytics();
      $profileid = google_analytics_light_report_get_profile_id($analytics);
    }
    $data = [];

    if (!empty($profileid)) {
      $results = $analytics->data_ga->get('ga:' . $profileid,
            '30daysAgo',
            'today',
            'ga:pageviews',
             [
               'dimensions'  => 'ga:browser',
               'sort'        => '-ga:pageviews',
             ]
           );
      $rows = $results->getRows();
      $data = [];
      foreach ($rows as $row) {
        $data[] = [
          'name'   => $row[0],
          'value'  => $row[1],
        ];
      }
    }
    return new JsonResponse($data);
  }

}
