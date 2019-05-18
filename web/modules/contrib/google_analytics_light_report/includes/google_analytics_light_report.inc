<?php

/**
 * @file
 * Provides the Google Analytics Reports API and associated methods.
 */

/**
 * Implement google_analytics_light_report_initialize_analytics().
 */
function google_analytics_light_report_initialize_analytics() {
  // Creates and returns the Analytics Reporting service object.
  // Use the developers console and download your service account
  // credentials in JSON format. Place them in this directory or
  // change the key file location if necessary.
  $config = \Drupal::config('google_analytics_light_report_settings_form.settings');
  $fid = $config->get('google_analytics_light_json_file');
  $analytics = '';
  if (!empty($fid)) {
    $file_obj = file_load($fid[0]);
    if (gettype($file_obj) == 'object') {
      $key_file_location = drupal_realpath($file_obj->getFileuri());
      // Create and configure a new client object.
      $client = new Google_Client();
      $client->setApplicationName("Analytics Reporting");
      $client->setAuthConfig($key_file_location);
      $client->setScopes(['https://www.googleapis.com/auth/analytics.readonly']);
      $analytics = new Google_Service_Analytics($client);
      return $analytics;
    }
  }
  drupal_set_message(t('Please upload an json file which you had downloaded from google console.'), 'error');
  return $analytics;
}

/**
 * Implement google_analytics_light_report_get_profile_id().
 */
function google_analytics_light_report_get_profile_id($analytics) {
  // Get selected google analytics (profile) ID.
  $config = \Drupal::config('google_analytics_light_report_settings_form.settings');
  $g_analytics_report_account = $config->get('g_analytics_report_account', '');
  $g_analytics_report_property = $config->get('g_analytics_report_property', '');
  $g_analytics_report_view = $config->get('g_analytics_report_view', '');

  if (!empty($g_analytics_report_account)) {
    if (!empty($g_analytics_report_property)) {
      if (!empty($g_analytics_report_view)) {
        return $g_analytics_report_view;
      }
      else {
        drupal_set_message(t('Please configure the seeting on setting page.'));
      }
    }
    else {
      drupal_set_message(t('Please configure the seeting on setting page.'));
    }
  }
  else {
    drupal_set_message(t('Please configure the seeting on setting page.'));
  }
}
