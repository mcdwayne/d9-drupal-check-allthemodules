<?php

namespace Drupal\tfl\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class TflDependencyInjection.
 *
 * @package Drupal\tfl\Controller\TflDependencyInjection
 */
class TflDependencyInjection extends ControllerBase {

  /**
   * Get database connection.
   *
   * @method tflDbConnection
   */
  public static function tflDbConnection() {
    return \Drupal::database();
  }

  /**
   * Get password service.
   *
   * @method tflPasswordService
   */
  public static function tflPasswordService() {
    return \Drupal::service('password');
  }

  /**
   * Get entity type manager.
   *
   * @method tflEntityManager
   */
  public static function tflEntityManager() {
    return \Drupal::entityTypeManager();
  }

  /**
   * Get configuration settings.
   *
   * @method tflConfigSettings
   */
  public static function tflConfigSettings() {
    return \Drupal::config('tfl.settings');
  }

}
