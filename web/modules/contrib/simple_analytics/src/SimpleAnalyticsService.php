<?php

namespace Drupal\simple_analytics;

/**
 * Simple Analytics Service.
 */
class SimpleAnalyticsService {

  /**
   * Detecte bots.
   */
  public static function botDetecte() {

    // Special screen resolutions for mobile devices.
    $screens = ['1024x1024', '375x667'];

    if (isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/bot|crawl|slurp|spider/i', $_SERVER['HTTP_USER_AGENT'])) {
      return 1;
    }
    elseif (isset($_POST['SCREEN']) && in_array($_POST['SCREEN'], $screens)) {
      return 1;
    }
    return 0;
  }

  /**
   * Detect Mobimle or not from HTTP_USER_AGENT.
   */
  public static function mobileDetecte() {
    return preg_match("/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i", $_SERVER["HTTP_USER_AGENT"]);
  }

  /**
   * Generate Table Vertical.
   */
  public static function generateTableVertical(array $datas, array $settings) {

    $title = (isset($settings['title'])) ? $settings['title'] : "";
    $fields = (isset($settings['fields'])) ? $settings['fields'] : NULL;

    if (is_array($fields)) {
      $header = ['#'];
      foreach ($fields as $field) {
        $header[] = $field;
      }
    }
    else {
      $header = [];
    }

    $rows = [];
    $index = 0;
    foreach ($datas as $label => $value) {
      $index++;
      $rows[$index] = [$index, $label, $value];
    }

    return [
      '#theme' => 'table',
      '#caption' => $title,
      '#rows' => $rows,
      '#header' => $header,
      '#empty' => '',
    ];
  }

  /**
   * Generate Graph.
   */
  public static function generateGraph(array $labels, array $datas, array $settings, &$output) {
    $label_str = NULL;
    $datas_str = NULL;

    $data_str_arr = [];

    foreach ($labels as $key => $label) {
      if ($label_str === NULL) {
        $label_str .= "[";
      }
      else {
        $label_str .= ",";
      }

      $label_str .= '"' . $label . '"';

      foreach ($datas as $key_dat => $data_set) {
        if (isset($data_str_arr[$key_dat])) {
          $data_str_arr[$key_dat] .= ",";
        }
        else {
          $data_str_arr[$key_dat] = "";
        }
        $data_str_arr[$key_dat] .= $data_set[$key];
      }
    }
    $label_str .= "]";

    foreach ($data_str_arr as $key_dat => $data) {
      if ($datas_str === NULL) {
        $datas_str .= "";
      }
      else {
        $datas_str .= ",";
      }
      $datas_str .= "[$data]";
    }
    if (count($data_str_arr) > 0) {
      $datas_str = "[$datas_str]";
    }

    $settings_type = isset($settings['type']) ? $settings['type'] : "Line";
    $chart_id = "Chartist-" . rand(100, 10000000);

    $data = [];
    $data['chart_type'] = $settings_type;
    $data['chart_id'] = $chart_id;
    $data['chart_labels'] = $label_str;
    $data['chart_series'] = $datas_str;

    return $data;
  }

}
