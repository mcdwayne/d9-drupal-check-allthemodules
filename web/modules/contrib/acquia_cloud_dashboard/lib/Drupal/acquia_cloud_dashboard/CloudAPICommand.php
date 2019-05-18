<?php

/**
 * @file
 * Contains \Drupal\acquia_cloud_dashboard\CloudAPICommand
 */

namespace Drupal\acquia_cloud_dashboard;

use Drupal\acquia_cloud_dashboard\CloudAPIHelper;

class CloudAPICommand extends CloudAPIHelper {

  public $config;

  public function __construct() {
    parent::__construct();
    $this->config = \Drupal::config('acquia_cloud_dashboard.settings');
  }

  public function refreshDomains($site, $env) {
    $domains = parent::callMethod('sites/' . $site . '/envs/' . $env . '/domains');
    $current = $this->config->get('report');

    $domain_names = array();
    foreach ($domains as $domain) {
      $domain_names[] = $domain['name'];
    }
    $current['sites'][$site]['environments'][$env]['domains'] = $domain_names;

    $this->config->set('report', $current)->save();
  }

  public function refreshKeys($site) {
    $keys = parent::callMethod('sites/' . $site . '/sshkeys');
    $current = $this->config->get('report');

    $site_keys = array();
    foreach ($keys as $ssh_key) {
      $site_keys[] = array(
        'id' => $ssh_key['id'],
        'nickname' => $ssh_key['nickname'],
        'public' => $ssh_key['ssh_pub_key'],
        'short' => drupal_substr($ssh_key['ssh_pub_key'], 0, 10) . "...",
      );
    }
    $current['sites'][$site]['keys'] = $site_keys;

    $this->config->set('report', $current)->save();
  }
  
}