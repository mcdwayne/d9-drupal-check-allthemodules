<?php

/**
 * @file
 * Contains \Drupal\acquia_cloud_dashboard\Controller\CloudDashboardController
 */

namespace Drupal\acquia_cloud_dashboard\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Utility\Settings;

class CloudDashboardController extends ControllerBase {
  public $config;

  public function __construct() {
    $this->config = \Drupal::config('acquia_cloud_dashboard.settings');
  }

  public function reportPage() {
    // Check and proceed only if configured.
    if ($this->config->get('username')) {
      if ($this->config->get('invalid_credentials')) {
        // Stop now if Cloud API credentials are invalid.
        drupal_set_message(t('Your cloud credentials look incorrect. Corrrect them on the  <a href="@config">settings page</a>.', array('@config' => url('/admin/config/cloud-api/configure'))), 'error');
        return;
      }

      $last_generated = $this->config->get('last_generated');
      $refresh_interval = $this->config->get('refresh_interval') ?: 3600;

      // Refresh the report if we've reached the threshold defined in settings.
      $time_elapsed_more_than_threshold = time() - $last_generated > $refresh_interval;
      if ($time_elapsed_more_than_threshold) {
        $this->refreshPage();
      }

      return $build = array(
        '#theme' => 'acquia_dashboard_report',
        '#updated' => format_interval(REQUEST_TIME - $last_generated),
        '#report' => $this->config->get('report'),
      );
    }
    else {
      drupal_set_message(t('Please configure your Cloud API credentials <a href="@url">here</a>.', array('@url' => '/admin/config/cloud-api/configure')), 'warning');
    }
  }

  /**
   * Helper function to manually refresh api report page.
   */
  public function refreshPage() {
    // Initialise the batch operation and define the functions that would be
    // called in the operation.
    $batch = array(
      'title' => t('Refreshing Cloud API Dashboard...'),
      'operations' => array(
        array('acquia_cloud_dashboard_update_sites', array()),
        array('acquia_cloud_dashboard_update_ssh_keys', array()),
        array('acquia_cloud_dashboard_update_dbs', array()),
        array('acquia_cloud_dashboard_update_environments', array()),
        array('acquia_cloud_dashboard_update_tasks', array()),
      ),
      'init_message' => t('Refreshing'),
      'progress_message' => t('Updated @current out of @total.'),
      'error_message' => t('An error occurred while updating'),
      'finished' => 'acquia_cloud_dashboard_update_finished',
    );

    batch_set($batch);
    return batch_process('admin/config/cloud-api/view');
  }

  /**
   * Generates a domain overview page.
   * @todo: This could use some cleanup.
   */
  public function domainsPage() {
    $report = $this->config->get('report');

    $tableData = array();
    $domains = array();
    foreach ($report['sites'] as $site) {
      foreach ($site['environments'] as $environment) {
        foreach ($environment['domains'] as $domain) {
          $domains[] = $domain;
          $operations = '<a href="/admin/config/cloud-api/purge/domain/' . $site['name'] . '/' . $environment['name'] . '/' . $domain . '" class="button">Purge</a>';
          $operations .= '<a href="/admin/config/cloud-api/delete/domain/' . $site['name'] . '/' . $environment['name'] . '/' . $domain . '" class="button">Delete</a>';
          $tableData['domain'][] = $domain;
          $tableData['site'][] = $site['name'];
          $tableData['environment'][] = $environment['name'];
          $tableData['operations'][] = $operations;
        }
      }
    }

    $headers = array(
        array('data' => t('Domain'), 'field' => 'domain'),
        array('data' => t('Site'), 'field' => 'site'),
        array('data' => t('Environment'), 'field' => 'environment', 'sort' => 'desc'),
        array('data' => t('Operations')),
    );

    $order = tablesort_get_order($headers);
    $sort = (tablesort_get_sort($headers) == 'desc') ? SORT_DESC : SORT_ASC;

    array_multisort($tableData[$order['sql']], $sort, $domains);

    $rows = array();
    foreach ($domains as $key => $item) {
      $rows[] = array(
        array('data' => $tableData['domain'][$key]),
        array('data' => $tableData['site'][$key]),
        array('data' => $tableData['environment'][$key]),
        array('data' => $tableData['operations'][$key]),
      );
    }

    $table = array('header' => $headers, 'sticky' => TRUE, 'rows' => $rows);

    $html = '<ul class="action-links"><li><a href="/admin/config/cloud-api/domains/add" class="button button-action">Add domain</a></li></ul>';
    return $html .= theme('table', $table);
  }
}