<?php

namespace Drupal\simple_analytics\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\simple_analytics\SimpleAnalyticsHelper;
use Drupal\Core\Database\Database;
use Drupal\simple_analytics\SimpleAnalyticsService;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Url;

/**
 * Simple Analytics Functions.
 */
class SimpleAnalytics extends ControllerBase {

  /**
   * Display today visitors details.
   */
  public function viewToday() {
    $output = [];
    $con = Database::getConnection();
    $output[] = ['#markup' => "<h2> Today statistic at : " . date("H:i:s") . "</h2>"];

    $fields_disp = [
      'id' => "#",
      'timestamp' => "First",
      'timeup' => "Last visit",
      'counter' => "Visits",
      'REFERER' => "Referer address",
      'SCREEN' => "Screen",
      'REMOTE_ADDR' => "IP Address",
      // Multiple value field (Virtual).
      'extra' => 'Extra',
      // Virtual field.
      'LINK' => 'Link',
    ];
    $html = "";
    $limit = 10000;
    $query = $con->select('simple_analytics_visit', 'v');
    $query->fields('v');
    $query->orderBy("id", 'DESC');
    $result = $query->execute()->fetchAll();
    $visits = count($result);
    $page_view = 0;
    $mobile = 0;
    $entry_direct = 0;
    $bot = 0;

    // Prepare Table.
    foreach ($result as $row) {
      $page_view++;
      $mobile += $row->MOBILE;
      $bot += $row->BOT;
      $entry_direct += ($row->REFERER ? 0 : 1);
    }

    $html .= "Total visits : $visits (Mobile:$mobile, Bots:$bot)<br>Total Page view : $page_view<br>";

    $lables = [];
    $datas = [];

    foreach ($fields_disp as $field) {
      $lables[] = $field;
    }

    // Re calculate.
    foreach ($result as $index => $row) {
      $signature = $row->SIGNATURE;

      if (isset($datas[$signature])) {
        $data_row = $datas[$signature];
      }

      foreach ($fields_disp as $field => $field_label) {

        $value = $row->$field;
        if ($field == 'timestamp') {
          $value = isset($datas[$signature][$field]) ? $datas[$signature][$field] : date("H:i:s", $value);
        }
        elseif ($field == 'REFERER' && $value) {
          $value = parse_url($value, PHP_URL_HOST);
        }
        elseif ($field == 'counter') {
          $value = $row->counter;
        }
        elseif ($field == 'timeup') {
          $value = date("H:i:s", $row->timeup);
        }
        elseif ($field == 'extra') {
          $value = "";
          $value .= "⚑" . $row->LANGUAGE;
          if (!empty($row->MOBILE)) {
            $value .= ", ☎";
          }
          if (!empty($row->BOT)) {
            $value .= ", ☢";
          }
        }
        elseif ($field == 'LINK') {
          $url = Url::fromRoute('simple_analytics.view.visitor', ['id' => $signature]);
          $value = new FormattableMarkup('<a href=":link">More</a>', [':link' => $url->toString()]);
        }

        $data_row[$field] = $value;
      }
      if ($index > $limit) {
        break;
      }

      $datas[$signature] = $data_row;
    }

    $output[] = ['#markup' => $html];

    $output['#attached']['library'][] = 'simple_analytics/simple_analytics_main';
    $output[] = [
      '#theme' => 'table',
      '#rows' => $datas,
      '#header' => $lables,
      '#empty' => '',
    ];

    $output[] = ['#markup' => "<p>☢ : Bots, ☎ : Mobiles, ⚑ : Languages</p>"];
    return $output;
  }

  /**
   * Display a visitor's details.
   */
  public function viewVisiter($id = NULL) {

    if (empty($id)) {
      $output[] = ['#markup' => "<div>Data id not found</div>"];
      return $output;
    }

    $output = [];
    $con = Database::getConnection();
    $output[] = ['#markup' => "<h2>Visitor's details : " . date("H:i:s") . "</h2>"];

    // Show details of visit.
    $query = $con->select('simple_analytics_visit', 'v');
    $query->fields('v');
    $query->condition('SIGNATURE', $id);
    $query->orderBy("id", 'DESC');
    $result = $query->execute()->fetchAssoc();
    if (empty($result)) {
      $output[] = ['#markup' => "<div>Data not found</div>"];
      return $output;
    }
    $output[] = [
      '#theme' => 'table',
      '#rows' => [
        ['Referer', $result['REFERER']],
        ['IP', $result['REMOTE_ADDR']],
        ['Page view', $result['counter']],
        ['Is Mobile', $result['MOBILE'] ? 'Yes' : 'No'],
        ['Is Bot', $result['BOT'] ? 'Yes' : 'No'],
        ['Screen size', $result['SCREEN']],
        ['User agent', $result['HTTP_USER_AGENT']],
        ['Host', $result['HTTP_HOST']],
      ],
      '#caption' => '',
    ];

    // Show page view.
    $fields_disp = [
      'timestamp' => "Time",
      // Calculated virtual field.
      'Duration' => "Duration",
      'LANGUAGE' => "⚑",
      'REFERER' => "Referer address",
      'REQUEST_URI' => 'REQUEST_URI',
    ];

    $limit = 10000;
    $query = $con->select('simple_analytics_data', 'd');
    $query->fields('d');
    $query->condition('SIGNATURE', $id);
    $query->orderBy("id", 'ASC');

    $result = $query->execute()->fetchAll();
    $page_view = count($result);
    $output[] = ['#markup' => "<h2>Details</h2>Total Page view : $page_view<br>"];

    $lables = [];
    $datas = [];

    foreach ($fields_disp as $field) {
      $lables[] = $field;
    }

    foreach ($result as $index => $row) {

      foreach ($fields_disp as $field => $field_label) {

        $value = $row->$field;
        if ($field == 'timestamp') {
          $value = isset($datas[$field]) ? $datas[$field] : date("H:i:s", $value);
        }
        elseif ($field == 'REFERER' && $value) {
          $value = parse_url($value, PHP_URL_HOST);
        }
        elseif ($field == 'counter') {
          $value = $datas[$field] ? $datas[$field] + 1 : 1;
        }
        elseif ($field == 'Duration') {
          $value = '';
          if (!empty($result[$index + 1]->timestamp)) {
            $value = ($result[$index + 1]->timestamp - $row->timestamp);
          }
          elseif ($close = $row->CLOSE) {
            $value = ($close - $row->timestamp);
          }

          if ($value) {
            if ($value > 60) {
              $value = floor($value / 60) . " min, " . floor($value % 60) . " sec";
            }
            else {
              $value .= " sec";
            }
          }
        }
        $data_row[$field] = $value;
      }
      if ($index > $limit) {
        break;
      }
      $datas[] = $data_row;
    }

    $output['#attached']['library'][] = 'simple_analytics/simple_analytics_main';
    $output[] = [
      '#theme' => 'table',
      '#rows' => $datas,
      '#header' => $lables,
      '#empty' => '',
    ];

    $output[] = ['#markup' => "<p>☢ : Bots, ☎ : Mobiles, ⚑ : Languages</p>"];
    return $output;
  }

  /**
   * Display visitors settings.
   */
  public function viewSettings() {
    $output = [];

    $con = Database::getConnection();
    $config = SimpleAnalyticsHelper::getConfig();

    // Days.
    $limit = $config->get('displaystat');

    $output['#attached']['library'][] = 'simple_analytics/simple_analytics_main';
    $output[] = ['#markup' => "<h2>Visiteurs Settings (last $limit Days)</h2>"];

    $query = $con->select('simple_analytics_archive', 'd');
    $query->fields('d');
    $query->orderBy("date", 'ASC');
    // LIMIT.
    $query->range(0, $limit);
    $result = $query->execute()->fetchAll();
    $visits = 0;
    $page_view = 0;

    $fields = [
      'screens' => 'Screen resolutions',
      'languages' => 'Languages',
      'platform' => 'Operating System',
      'browser' => 'Web Browser',
    ];

    $stat_data = [];

    // Collect data and calculate.
    foreach ($result as $row) {
      $page_view += $row->page_view;
      $visits += $row->visits;

      // Get settings data array.
      $settings_data = json_decode($row->settings_data, TRUE);
      foreach ($fields as $field => $field_label) {
        $sett_values = $settings_data[$field];
        if (is_array($sett_values)) {
          foreach ($sett_values as $sett_key => $count) {
            if (!$sett_key) {
              $sett_key = "Unknown";
            }
            if (isset($stat_data[$field][$sett_key])) {
              $stat_data[$field][$sett_key] += $count;
            }
            else {
              $stat_data[$field][$sett_key] = $count;
            }
          }
        }
      }
    }

    // Show summary.
    $html = "";
    $html .= "Total visits : $visits<br>Total Page view : $page_view<br>";
    $output[] = [
      '#markup' => $html,
    ];

    // Build stat renderer arrays.
    $output['tables'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['row'],
      ],
    ];

    foreach ($stat_data as $stat_type => $stat_values) {
      $temp = SimpleAnalyticsService::generateTableVertical($stat_values, [
        'title' => $fields[$stat_type],
        'fields' => [$fields[$stat_type], 'Count'],
      ]);
      $temp['#prefix'] = "<div class='col col-lg-3'>";
      $temp['#suffix'] = "</div>";
      $output['tables'][] = $temp;
    }

    return $output;
  }

  /**
   * Display live visitors details.
   */
  public function viewLive() {

    $output = [];
    $output['#cache']['max-age'] = 3600;
    $output['#attached']['library'][] = 'simple_analytics/simple_analytics_live';
    $output[] = ['#markup' => "<h2>Live Visiteurs</h2>"];
    $html = "";
    $html .= "<div class='sa-live page'>";
    $html .= "<div class='sa-live-value-visits' title='Number of visits'></div>";
    $html .= "<div class='sa-live-text-visits'>Currently online</div>";
    $html .= "<div><span>Number of visitors</span>:<span class='sa-live-value-visitors'></span></div>";
    $html .= "<div><span>Number of mobiles</span>:<span class='sa-live-value-mobiles'></span></div>";
    $html .= "</div>";
    $output[] = ['#markup' => $html];

    return $output;
  }

  /**
   * Display visitors history.
   */
  public function viewHistory($filter = NULL) {

    $output = [];
    // No cache.
    $output[]['#cache']['max-age'] = 0;
    $con = Database::getConnection();
    $config = SimpleAnalyticsHelper::getConfig();

    // Days.
    $limit = $config->get('displaystat');

    $fields = [
      'id',
      'date',
      'visits',
      'page_view',
      'mobile',
      'entry_direct',
      'bots',
      'hits',
      'bots_hits',
      'settings_data',
    ];

    // Authorized fields and defaults.
    $fields_auth = [
      'visits' => 1,
      'page_view' => 1,
      'mobile' => 1,
      'entry_direct' => 0,
      'bots' => 1,
      'hits' => 1,
      'bots_hits' => 0,
    ];
    $fields_legends = [
      'visits' => 'Visits',
      'page_view' => 'View',
      'bots' => 'Bots',
      'mobile' => 'Mobiles',
      'hits' => 'Total Pages',
      'entry_direct' => 'Direct entry',
      'bots_hits' => 'Bots Hits',
    ];
    $filter_str = $filter ? (" | " . $fields_legends[$filter]) : '';
    $chart_title = "History of last $limit Days $filter_str";

    // Remove unwanted filters.
    if ($filter && !isset($fields_auth[$filter])) {
      $filter = NULL;
    }
    $fields_display = [];
    $chart_legends = [];

    if ($filter) {
      // Filtered single view.
      $fields_display[] = $filter;
      $chart_legends[] = $fields_legends[$filter];
    }
    else {
      // Default View.
      foreach ($fields_auth as $field => $is_default) {
        if ($is_default) {
          $fields_display[] = $field;
          $chart_legends[] = $fields_legends[$field];
        }
      }
    }

    $query = $con->select('simple_analytics_archive', 'd');
    $query->fields('d', $fields);
    $query->orderBy("date", 'DESC');
    // LIMIT.
    $query->range(0, $limit);
    $result = $query->execute()->fetchAll();
    $result = array_reverse($result);

    // Display chart.
    if ($config->get('lib_chartist')) {
      $lables = [];
      $datas = [];

      $cpt = 0;

      foreach ($result as $row) {
        $i = 0;
        $lables[] = date("M-d", $row->date);
        foreach ($fields_display as $field) {
          $datas[$i++][] = $row->$field;
        }
        $cpt++;
      }
      $data = SimpleAnalyticsService::generateGraph($lables, $datas, [], $output);
      $chart_id = $data['chart_id'];
      $chart_type = $data['chart_type'];
      $chart_labels = $data['chart_labels'];
      $chart_series = $data['chart_series'];

      $output[] = [
        '#theme' => 'simple_analytics_chart',
        '#chart_title' => $chart_title,
        '#chart_id' => $chart_id,
        '#chart_legends' => $chart_legends,
        // Get Librery location by configuration.
        '#library' => $config->get('lib_chartist_mode'),
      ];

      // Generated chart data.
      $chart_data_script = <<<SCRIPT
window.addEventListener("load", function(){
    data = {
        labels: $chart_labels,
        series: $chart_series
    }
    showchart('#$chart_id', data, '$chart_type');
});
SCRIPT;
      // Attach chart script with data.
      $output['#attached']['html_head'][] = [
        ['#tag' => 'script', '#value' => $chart_data_script],
        'sa_chart_script',
      ];
    }

    // Build table.
    $lables = ['date' => 'Date'];
    $data = [];

    $fields = $fields_legends;
    if ($filter) {
      // Filtered single view.
      $fields = [$filter => $fields_legends[$filter]];
      $lables[$filter] = $fields_legends[$filter];
    }
    else {
      $lables = array_merge($lables, $fields_legends);
    }

    foreach ($result as $key => $row) {
      $data[$key] = [
        'date' => date("Y-m-d", $row->date),
      ];

      foreach ($fields as $field => $field_Name) {
        $data[$key][$field] = $row->$field;
      }
    }

    // Revert data order.
    rsort($data);
    $output[] = [
      '#theme' => 'table',
      '#rows' => $data,
      '#header' => $lables,
      '#empty' => '',
    ];

    return $output;
  }

}
