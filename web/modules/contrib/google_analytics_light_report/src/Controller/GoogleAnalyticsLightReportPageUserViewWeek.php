<?php

namespace Drupal\google_analytics_light_report\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class GoogleAnalyticsLightReportPageUserViewWeek.
 */
class GoogleAnalyticsLightReportPageUserViewWeek extends ControllerBase {

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
            '7daysAgo',
            'today',
            'ga:users',
            [
              'dimensions'  => 'ga:day',
              'sort'        => '-ga:day',
            ]
          );
      $rows = $results->getRows();
      foreach ($rows as $row) {
        $data[] = [
          'user'   => $row[1],
          'day'  => $row[0],
        ];
      }
    }
    return new JsonResponse($data);
  }

}
