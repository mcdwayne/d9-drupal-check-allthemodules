<?php

/**
 * @file
 * Provides the Google Analytics Reports in Block.
 */

/**
 * Define google analytics report count content.
 *
 * Function to call  google analytics report API to show User,
 * Seession, Bounce Rate
 * and Pageviews.
 */
function google_analytics_light_report_count_content() {
  $library_exist = google_analytics_light_report_library_exists();
  if ($library_exist) {
    // $selected_date = variable_get('g_analytics_report_count_days',
    // '7daysAgo');!
    $selected_date = '7daysAgo';
    $analytics = google_analytics_light_report_initialize_analytics();
    $profileid = google_analytics_light_report_get_profile_id($analytics);
    $data = [];

    if (!empty($profileid)) {
      $results = $analytics->data_ga->get('ga:' . $profileid,
          $selected_date,
          'today',
          'ga:users,ga:sessions,ga:bounceRate,ga:pageviews',
          [
            // 'dimensions'  => 'ga:day',
            // 'sort'        => '-ga:day',
            // 'max-results' => 10,!
          ]
        );
      $rows = $results->getRows();
      foreach ($rows as $row) {
        $data = [
          'User'   => $row[0],
          'Seession'  => $row[1],
          'Bounce Rate'  => number_format((float) $row[2], 2, '.', ''),
          'Pageviews' => $row[3],
        ];
      }
      $output = '';
      $output .= '<table width="100%">';
      $output .= '<tr>';

      foreach ($data as $key => $value) {
        $output .= '<td><div class="total_visit"><h3 class="users_total_title">' . $key . '</h3><div class="total_count">' . $value . '</div></div></td>';
      }
      $output .= '</tr>';
      $output .= '</table>';
      return [
        'pageview_count' => [
          '#markup' => $output,
          '#prefix' => '<div class="pageview_count">',
          '#suffix' => '</div>',
        ],
      ];
    }
  }
}

/**
 * Define google analytics report list for PageViews content.
 *
 * Function to call  google analytics report API to show list of
 * Pageviews.
 */
function google_analytics_light_report_page_views_content() {
  $library_exist = google_analytics_light_report_library_exists();
  if ($library_exist) {
    // $selected_date = variable_get('g_analytics_report_page_views_days',
    // '7daysAgo');!
    $selected_date = '7daysAgo';
    $analytics = google_analytics_light_report_initialize_analytics();
    $profileid = google_analytics_light_report_get_profile_id($analytics);

    if (!empty($profileid)) {
      $row_data = [];
      $results = $analytics->data_ga->get('ga:' . $profileid,
          $selected_date,
          'today',
          'ga:pageviews',
          [
            'dimensions'  => 'ga:pagePath,ga:pageTitle',
            'filters' => 'ga:pagePath!@login;ga:pagePath!@dashboard',
            // 'sort'        => '-ga:day',
            // 'max-results' => 10!
          ]
        );
      $rows = $results->getRows();
      foreach ($rows as $row) {
        $row_data[] = [$row[0], $row[1], $row[2]];
      }
      $build['table'] = [
        '#theme' => 'table',
        '#header' => [
          'Page Path',
          'Page Title',
          'Pageviews',
        ],
        '#rows' => $row_data,
      ];
      return [
        'pageview_list' => [
          '#markup' => drupal_render($build),
          '#prefix' => '<div class="pageview_list">',
          '#suffix' => '</div>',
        ],
      ];
    }
  }
}
