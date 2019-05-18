<?php

namespace Drupal\new_relic_rpm\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Default controller for the new_relic_rpm module.
 */
class DefaultController extends ControllerBase {

  /**
   * Gets the health report overview data from New Relic.
   */
  public function newRelicRpmReporting() {

    // If no API key is set, break here and error out.
    $api_key = \Drupal::config('new_relic_rpm.settings')->get('api_key');
    if (empty($api_key)) {
      drupal_set_message($this->t(
        'You need to enter your New Relic API key from your New Relic account settings page before you are able to view reports within Drupal. Visit the <a href="@settings">New Relic RPM Drupal admin page</a> to enter your API key.',
        ['@settings' => Url::fromRoute('new_relic_rpm.settings')]
      ), 'error');
      return '<h2>' . t('No API key found.') . '</h2>';
    }

    // Get basic app health.
    // This is also our first check for a bad key/access denied.
    // Only hit the REST API every 60 seconds.
    if ($_SESSION['new_relic_rpm_health_time'] < $_SERVER['REQUEST_TIME'] - 60 || !$_SESSION['new_relic_rpm_health_xml']) {
      $app_health = new_relic_rpm_curl('https://rpm.newrelic.com/accounts.xml?include=application_health');
    }
    else {
      $app_health = $_SESSION['new_relic_rpm_health_xml'];
    }
    // Error out of the return is False, store data if it is good.
    if (!$app_health) {
      drupal_set_message($this->t(
        'The New Relic REST API has denied your key. Either the key you entered on the <a href="@settings">New Relic RPM Drupal admin page</a> is incorrect, or you have not enabled API access for this application within the New Relic RPM webiste.',
        ['@settings' => Url::fromRoute('new_relic_rpm.settings')]
      ), 'error');
      return '<h2>' . t('API access denied.') . '</h2>';
    }
    else {
      $_SESSION['new_relic_rpm_health_time'] = $_SERVER['REQUEST_TIME'];
      $_SESSION['new_relic_rpm_health_xml'] = $app_health;
    }

    return new_relic_rpm_render_health($app_health);
  }

  /**
   * Gets the health report data for a particular application from New Relic.
   *
   * @todo Add deployments to the data shown.
   */
  public function newRelicRpmReportingDetails($cust_id, $app_id) {

    $output = '';

    // If no API key is set, break here and error out.
    $api_key = \Drupal::config('new_relic_rpm.settings')->get('api_key');
    if (empty($api_key)) {
      drupal_set_message($this->t(
        'You need to enter your New Relic API key from your New Relic account settings page before you are able to view reports within Drupal. Visit the <a href="@settings">New Relic RPM Drupal admin page<a/> to enter your API key.',
        ['@settings' => Url::fromRoute('new_relic_rpm.settings')]
      ), 'error');
      return '<h2>' . t('No API key found.') . '</h2>';
    }

    // Only hit the REST API every 60 seconds.
    if ($_SESSION['new_relic_rpm_dash_time'] < $_SERVER['REQUEST_TIME'] - 60 || !$_SESSION['new_relic_rpm_dash_xml']) {
      $app_dashboard = new_relic_rpm_curl('https://rpm.newrelic.com/application_dashboard?application_id=' . $app_id);
    }
    else {
      $app_dashboard = $_SESSION['new_relic_rpm_dash_xml'];
    }

    // Error out if value is false, save cached copy of XML if it is good.
    if (!$app_dashboard) {
      drupal_set_message($this->t(
        'The New Relic REST API has denied your key. Either the key you entered on the <a href="@settings">New Relic RPM Drupal admin page</a> is incorrect, or you have not enabled API access for this application within the New Relic RPM webiste.',
        ['@settings' => Url::fromRoute('new_relic_rpm.settings')]
      ), 'error');
      return '<h2>' . t('API access denied.') . '</h2>';
    }
    else {
      $_SESSION['new_relic_rpm_dash_time'] = $_SERVER['REQUEST_TIME'];
      $_SESSION['new_relic_rpm_dash_xml'] = $app_dashboard;
    }

    $output .= $app_dashboard;

    $output .= new_relic_rpm_render_actions($cust_id, $app_id);

    return $output;
  }

}
