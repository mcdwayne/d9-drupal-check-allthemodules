<?php

/**
 * @file
 * Contains Drupal\evercurrent\UpdateHelper.
 */

namespace Drupal\evercurrent;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Component\Utility\Xss;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Http;
use Drupal\Core\Site\Settings;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Class UpdateHelper.
 *
 * @package Drupal\evercurrent
 */
class UpdateHelper implements UpdateHelperInterface {

  /**
   * @var ConfigFactory
   */
  protected $config_factory;

  /**
   * @var $module_handler
   */
  protected $module_handler;

  /**
   * @var ThemeHandlerInterface
   */
  protected $theme_handler;

  /**
   * Constructor.
   */
  public function __construct(ConfigFactory $config_factory, ModuleHandlerInterface $module_handler, ThemeHandlerInterface $theme_handler) {
    $this->config_factory = $config_factory;
    $this->module_handler = $module_handler;
    $this->theme_handler = $theme_handler;
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
    $config = \Drupal::config('evercurrent.admin_config');
    if (!$key) {
      $key = $this->getKeyFromSettings();
    }
    $valid = $this->keyCheck($key);
    if (!$valid) {
      $this->writeStatus(RMH_STATUS_WARNING, 'RMH Update check not run. The key is not a vaild key. It should be a 32-digit string with only letters and numbers.', $out);
      return FALSE;
    }
    $data = array();
    if ($available = update_get_available(TRUE)) {
      module_load_include('inc', 'update', 'update.compare');
      $data = update_calculate_project_data($available);
    }

    // Module version
    $version = system_get_info('module', 'evercurrent');

    $sender_data = [
      'send_url' => $config->get('target_address'),
      'project_name' => $this->get_environment_url(),
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
      UPDATE_NOT_CURRENT,
    ];

    foreach ($data as $module => $module_info) {
      if (in_array($module_info['status'], $status_list)) {
        $sender_data['updates'][$module] = $data[$module];
        // In some cases (like multisite installations),
        // modules on certain paths are considered unimportant.
        $sender_data['updates'][$module]['module_path'] = str_replace('/' . $module, '', drupal_get_path('module', $module));
      }
    }

    // Send active module data, to allow us to act on uninstalled modules
    $enabled_modules = $this->module_handler->getModuleList();
    $sender_data['enabled'] = array();
    foreach($enabled_modules AS $enabled_key => $enabled_module) {
      $sender_data['enabled'][$enabled_key] = $enabled_key;
    }
    $enabled_themes = $this->theme_handler->listInfo();
    foreach($enabled_themes AS $enabled_key => $enabled_theme) {
      $sender_data['enabled'][$enabled_key] = $enabled_key;
    }
    // Retrieve active installation profile data.
    // We mark this as enabled send this if we are using an installation profile
    // that the Update Manager module also reports on. Otherwise, Evercurrent
    // will not tell us about updates for it.
    $install_profile = Settings::get('install_profile');
    if ($install_profile && in_array($install_profile, array_keys($sender_data['updates']))) {
      $sender_data['enabled'][$install_profile] = $install_profile;
    }

    // Expose hook to add anything else.
    $this->module_handler->alter('evercurrent_update_data', $sender_data);

    // Send the updates to the server.
    $path = $sender_data['send_url'] . RMH_URL;

    // Set up a request
    /** @var \Drupal::httpClient $client */
    try {
      $response = \Drupal::httpClient()
        ->request('POST', $path, [
          'form_params' => array('data' => json_encode($sender_data)),
        ]);
    } catch (\Exception $e) {
      $this->writeStatus(RMH_STATUS_ERROR, 'When trying to reach the server URL, Drupal reported the followng connection error: ' . $e->getMessage(), $out);
      return FALSE;
    }
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
          \Drupal::state()->set('evercurrent_last_run',time());
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
    $config = \Drupal::config('evercurrent.admin_config');
    $override = $config->get('override');
    // Key from regular configuration
    $config_key = $config->get('key');
    // Key from settings.php
    $settings_key = Settings::get('evercurrent_environment_token', NULL);
    // If
    return ($settings_key && !$override) ? $settings_key : $config_key;
  }

  /**
   * Disable listening mode.
   */
  public function disableListening() {
    $config = $this->config_factory->getEditable('evercurrent.admin_config');
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
    $config = $this->config_factory->getEditable('evercurrent.admin_config');
    $message = Xss::filter($message);
    $state = \Drupal::state();
    $state->set('evercurrent_status_message', $message);
    $state->set('evercurrent_status', $severity);
    $config->save();

    // If error, also log to watchdog.
    if ($severity == RMH_STATUS_ERROR) {
      \Drupal::logger('evercurrent')->error($message);
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
    $config = $this->config_factory->getEditable('evercurrent.admin_config');
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
    $config = $this->config_factory->getEditable('evercurrent.admin_config');
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
    $last = \Drupal::state()->get('evercurrent_last_run') ?: 0;
    if ($last > 0) {
      $last_time = \Drupal::service('date.formatter')->formatInterval(time() - $last);
    }
    else {
      $last_time = t('Never.');
    }
    return t('%last_time',
      ['%last_time' => $last_time]);
  }

  /**
   * Helper function.
   *
   * Get an environment URL and ship together with the results.
   * First we see if we have our own explicit variable set. This
   * is only used for this purpose, and it allows the module
   * to be flexible in terms of determining the correct environment.
   *
   */
  public function get_environment_url() {
    global $base_url;
    $settings = Settings::get('evercurrent_environment_url', NULL);
    return $settings ? $settings : $base_url;
  }
}
