<?php

namespace Drush\Commands;

use Consolidation\OutputFormatters\StructuredData\PropertyList;
use Drupal\acsf\AcsfConfigDefault;
use Drupal\acsf\AcsfException;
use Drupal\acsf\AcsfMessageRest;
use Drupal\Core\Extension\InfoParser;

/**
 * Provides drush commands for site related operations.
 *
 * This class' namespace is off but it is necessary. All the commands in this
 * file are executed with the --include flag, and without reusing this
 * namespace the commands would not be found.
 */
class AcsfExtraCommands extends DrushCommands {

  /**
   * Print credentials retrieved from the factory.
   *
   * @command acsf-get-factory-creds
   *
   * @bootstrap root
   *
   * @return \Consolidation\OutputFormatters\StructuredData\PropertyList
   *   Gives back the creds in json format.
   *
   * @throws \Drupal\acsf\AcsfException
   *   If the function couldn't retrieve the necessary creds.
   */
  public function getFactoryCreds() {
    if (!class_exists('\Drupal\acsf\AcsfConfigDefault')) {
      // Since there might not be a bootstrap, we need to find our config
      // objects.
      $include_path = realpath(dirname(__FILE__));
      require_once $include_path . '/src/AcsfConfig.php';
      require_once $include_path . '/src/AcsfConfigDefault.php';
      require_once $include_path . '/src/AcsfConfigIncompleteException.php';
      require_once $include_path . '/src/AcsfConfigMissingCredsException.php';
    }

    try {
      $config = new AcsfConfigDefault();
    }
    catch (\Exception $e) {
      throw new AcsfException('Failed to get config: ' . $e->getMessage());
    }
    $creds = [
      'url' => $config->getUrl(),
      'username' => $config->getUsername(),
      'password' => $config->getPassword(),
      'url_suffix' => $config->getUrlSuffix(),
    ];

    return new PropertyList($creds);
  }

  /**
   * Set a site offline.
   *
   * @command go-offline
   *
   * @aliases go-off
   *
   * @bootstrap full
   */
  public function offline() {
    $lock = \Drupal::lock();
    $acsf_settings = \Drupal::configFactory()->getEditable('acsf.settings');

    // Track if a site admin purposely put their site into maintenance.
    $maintenance_mode = \Drupal::state()->get('system.maintenance_mode');
    if ($maintenance_mode) {
      // Site is in maintenance mode already. We need to keep that in mind.
      $acsf_settings->set('site_owner_maintenance_mode', TRUE)->save();
    }

    // For now hard-code a 10 minute expected offline time.
    $expected = time() + 10 * 60;

    \Drupal::state()->set('system.maintenance_mode', TRUE);
    $acsf_settings->set('maintenance_time', $expected)->save();

    // Get the cron lock to prevent cron from running during an update.
    // Use a large lock timeout because an update can take a long time.
    // All cron processes are stopped before update begins, so the lock will
    // be available.
    $lock->acquire('cron', 1200.0);
  }

  /**
   * Runs after a go-offline command executes. Verifies maintenance mode.
   *
   * @hook post-command go-offline
   */
  public function postOffline() {
    $offline = \Drupal::state()->get('system.maintenance_mode');
    if ($offline) {
      $this->logger()->success(dt('Site has been placed offline.'));
    }
    else {
      $this->logger()->error(dt('Site has not been placed offline.'));
    }
  }

  /**
   * Set a site online.
   *
   * @command go-online
   *
   * @aliases go-on
   *
   * @bootstrap full
   */
  public function online() {
    $lock = \Drupal::lock();

    // Determine whether the user intended the site to be in maintenance mode.
    $content = \Drupal::config('acsf.settings')->get('site_owner_maintenance_mode');

    // Clearing maintenance mode.
    \Drupal::state()->set('system.maintenance_mode', FALSE);
    \Drupal::configFactory()->getEditable('acsf.settings')->set('maintenance_time', 0)->save();

    if (!empty($content)) {
      \Drupal::state()->set('system.maintenance_mode', TRUE);
    }

    // Release cron lock.
    $lock->release('cron');
  }

  /**
   * Runs after a go-online command executes. Verifies maintenance mode.
   *
   * @hook post-command go-online
   */
  public function postOnline() {
    $content = \Drupal::state()->get('system.maintenance_mode');
    if (empty($content)) {
      $this->logger()->success(dt('Site has been placed online.'));
    }
    else {
      $content = \Drupal::config('acsf.settings')->get('site_owner_maintenance_mode');
      if (empty($content)) {
        $this->logger()->error(dt('Site has not been placed online.'));
      }
      else {
        $this->logger()->success(dt('Site has been left offline as set by the site owner.'));
        // Unset our maintenance mode setting.
        \Drupal::configFactory()->getEditable('acsf.settings')->set('site_owner_maintenance_mode', FALSE)->save();
      }
    }
  }

  /**
   * Fetches the version of the acsf moduleset.
   *
   * @command acsf-version-get
   *
   * @bootstrap root
   *
   * @param string $path
   *   The path to the acsf moduleset.
   */
  public function versionGet($path) {
    if (empty($path)) {
      $path = __DIR__;
    }
    $version = '0.0';
    $acsf_file_path = rtrim($path, '/') . '/acsf.info.yml';
    if (file_exists($acsf_file_path)) {
      $info_parser = new InfoParser();
      $info = $info_parser->parse($acsf_file_path);
      $version = isset($info['acsf_version']) ? $info['acsf_version'] : '0.1';
    }
    $this->output()->writeln($version);
  }

  /**
   * Reports process completion back to the factory.
   *
   * @command report-complete-async-process
   *
   * @bootstrap root
   *
   * @option data Serialized PHP data regarding the caller.
   *
   * @param array $options
   *   The command options supplied to the executed command.
   *
   * @throws \Drupal\acsf\AcsfException;
   *   If the data argument is invalid.
   */
  public function completeAsyncProcess(array $options = ['data' => NULL]) {
    $data = unserialize($options['data']);

    if (empty($data->callback) || empty($data->object_id) || empty($data->acsf_path)) {
      throw new AcsfException(dt('Requires serialized object in --data argument with $data->callback and $data->object_id populated.'));
    }

    // Since this does not bootstrap drupal fully, we need to manually require
    // the classes necessary to send a message to the Factory.
    require_once $data->acsf_path . '/src/AcsfConfig.php';
    require_once $data->acsf_path . '/src/AcsfConfigDefault.php';
    require_once $data->acsf_path . '/src/AcsfConfigIncompleteException.php';
    require_once $data->acsf_path . '/src/AcsfConfigMissingCredsException.php';
    require_once $data->acsf_path . '/src/AcsfMessage.php';
    require_once $data->acsf_path . '/src/AcsfMessageEmptyResponseException.php';
    require_once $data->acsf_path . '/src/AcsfMessageFailedResponseException.php';
    require_once $data->acsf_path . '/src/AcsfMessageFailureException.php';
    require_once $data->acsf_path . '/src/AcsfMessageMalformedResponseException.php';
    require_once $data->acsf_path . '/src/AcsfMessageRest.php';
    require_once $data->acsf_path . '/src/AcsfMessageResponse.php';
    require_once $data->acsf_path . '/src/AcsfMessageResponseRest.php';

    $arguments = [
      'wid' => $data->object_id,
      'signal' => 1,
      'state' => isset($data->state) ? $data->state : NULL,
      'data' => $data,
    ];

    try {
      // We do not have a Drupal bootstrap at this point, so we need to use
      // AcsfConfigDefault to obtain the shared credentials.
      $config = new AcsfConfigDefault();
      $message = new AcsfMessageRest('POST', $data->callback, $arguments, $config);
      $message->send();
    }
    catch (\Exception $e) {
      syslog(LOG_ERR, dt('Unable to contact the factory via AcsfMessage.'));
    }
  }

}
