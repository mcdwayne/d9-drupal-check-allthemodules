<?php

/**
 * @file
 * Contains Drupal\ricochet_maintenance_helper\UpdateHelper.
 */

namespace Drupal\ricochet_maintenance_helper;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Component\Utility\Xss;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Http;
use Drupal\Core\Site\Settings;

/**
 * Class UpdateHelper.
 *
 * @package Drupal\ricochet_maintenance_helper
 */
class UpdateHelper implements UpdateHelperInterface {

  /**
   * @var ConfigFactory
   */
  protected $config_factory;

  /**
   * Constructor.
   */
  public function __construct(ConfigFactory $config_factory) {
    $this->config_factory = $config_factory;
  }


  /**
   * Send updates to the Maintenance server.
   *
   * @param $force
   * Force Drupal's update collector to refresh available updates.
   *
   * @param $key
   * Use another key than what is saved
   *
   * @param $out
   * Display error messages as screen messages
   *
   * @return mixed
   */
  public function sendUpdates($force = TRUE, $key = NULL, $out = FALSE) {
    $config = \Drupal::config('ricochet_maintenance_helper.admin_config');
    if (!$key) {
      $key = $this->getKeyFromSettings();
    }
    $valid = $this->keyCheck($key);
    if (!$valid) {
      $this->writeStatus(RMH_STATUS_WARNING, 'RMH Update check not run. The key is not a vaild key. It should be a 32-digit string with only letters and numbers.', $out);
      return FALSE;
    }
    if ($available = update_get_available(TRUE)) {
      module_load_include('inc', 'update', 'update.compare');
      $data = update_calculate_project_data($available);
    }
    global $base_url;

    // Module version
    $version = system_get_info('module', 'ricochet_maintenance_helper');

    $sender_data = [
      'send_url' => $config->get('target_address'),
      'project_name' => $base_url,
      'key' => $key,
      'module_version' => $version['version'],
      'api_version' => 1,
      'updates' => [],
    ];
    $status_list = [
      UPDATE_NOT_SECURE,
      UPDATE_REVOKED,
      UPDATE_NOT_SUPPORTED,
      UPDATE_CURRENT,
      UPDATE_NOT_CHECKED,
      UPDATE_NOT_CURRENT
    ];

    foreach ($data as $module => $module_info) {
      if (in_array($module_info['status'], $status_list)) {
        $sender_data['updates'][$module] = $data[$module];
      }
    }

    // Expose hook to add anything else.
    \Drupal::moduleHandler()->alter('ricochet_maintenance_helper_update_data', $sender_data);

    // Send the updates to the server.
    $path = $sender_data['send_url'] . RMH_URL;

    // Set up a request
    /** @var \Drupal::httpClient $client */

    $response = \Drupal::httpClient()
      ->request('POST',$path, [
        'form_params' => array('data' => json_encode($sender_data))
      ]);
    $code = $response->getStatusCode();
    $body = (string) $response->getBody();
    if (!$response->getStatusCode() == 200) {
      $this->writeStatus(RMH_STATUS_ERROR, 'Error code ' . $code . ' when trying to post to ' . $path, $out);
      return FALSE;
    }
    else {
      // Check the response data, was it successful?
      $response_data = Json::decode($body);
      if ($response_data) {
        $saved = $response_data['saved'];
        if (!$saved) {
          $this->writeStatus(RMH_STATUS_ERROR, $response_data['message'], $out);
          return FALSE;
        }
        else {
          \Drupal::state()->set('ricochet_maintenance_helper_last_run',time());
          $this->writeStatus(RMH_STATUS_OK, $response_data['message'], $out);
          // If successful, we want to reassure that listening mode is off.
          $this->disableListening();
          return TRUE;
        }
      }
    }
    return FALSE;
  }

  /**
   * @param $key
   * @return bool
   */
  public function keyCheck($key) {
    return is_string($key) && preg_match(RMH_MD5_MATCH, $key);
  }

  /**
   * Retrieve a key from settings.php, or from variable.
   */
  public function getKeyFromSettings() {
    $config = \Drupal::config('ricochet_maintenance_helper.admin_config');
    $override = $config->get('override');
    // Key from regular configuration
    $config_key = $config->get('key');
    // Key from settings.php
    $settings_key = Settings::get('ricochet_maintenance_helper_environment_token', NULL);
    // If
    return ($settings_key && !$override) ? $settings_key : $config_key;
  }

  /**
   * Disable listening mode.
   */
  public function disableListening() {
    $config = $this->config_factory->getEditable('ricochet_maintenance_helper.admin_config');
    $config->set('listen', FALSE);
    $config->save();
  }

  /**
   * Saves a status message for the status page.
   *
   * @param $severity
   * @param $message
   * @param $output
   */
  public function writeStatus($severity, $message, $output = FALSE) {
    $config = $this->config_factory->getEditable('ricochet_maintenance_helper.admin_config');
    $message = Xss::filter($message);
    $state = \Drupal::state();
    $state->set('ricochet_maintenance_helper_status_message', $message);
    $state->set('ricochet_maintenance_helper_status', $severity);
    $config->save();

    // If error, also log to watchdog.
    if ($severity == RMH_STATUS_ERROR) {
      \Drupal::logger('ricochet_maintenance_helper')->error($message);
    }
    // Output this to message.
    if ($output) {
      $alert_type = ($severity == RMH_STATUS_OK) ? 'status' : 'error';
      drupal_set_message($message, $alert_type);
    }
  }

  /**
   * Check a key and set it if valid.
   *
   * @param $key
   * @return bool
   */
  public function setKey($key) {
    $config = $this->config_factory->getEditable('ricochet_maintenance_helper.admin_config');
    if ($this->keyCheck($key)) {
      $config->set('key', $key);
      return TRUE;
    }
    $this->writeStatus(RMH_STATUS_ERROR, 'Failed to set RMH key. Key should be a 32-character string.');
    return FALSE;
  }

  /**
   * Test sending an update to the server.
   *
   * @param $key
   * @return bool
   */
  public function testUpdate($key) {
    if (!$this->keyCheck($key)) {
      $this->writeStatus(RMH_STATUS_ERROR, 'Failed to set RMH key. Key should be a 32-character string.');
      return FALSE;
    }
    $config = $this->config_factory->getEditable('ricochet_maintenance_helper.admin_config');
    $config->set('listen', FALSE);
    $config->set('key', $key);
    $this->writeStatus(RMH_STATUS_OK, 'Key was successfully received.');
    $this->sendUpdates(TRUE);
    return TRUE;
  }

  /**
   * Get interval since last try.
   *
   * @return string
   */
  function lastRun() {
    $last = \Drupal::state()->get('ricochet_maintenance_helper_last_run') ?: 0;
    if ($last > 0) {
      $last_time = \Drupal::service('date.formatter')->formatInterval(time() - $last);
    }
    else {
      $last_time = t('Never.');
    }
    return t('%last_time',
      ['%last_time' => $last_time]);
  }
}
