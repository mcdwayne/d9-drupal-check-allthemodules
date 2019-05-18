<?php

namespace Drupal\custom_configuration\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\custom_configuration\Helper\ConfigurationHelper;

/**
 * Custom configuration controller.
 *
 * @package Drupal\custom_configuration\Controller
 */
class CustomConfigController extends ControllerBase {

  /**
   * Custom conguration helper object.
   *
   * @var Drupal\custom_configuration\Helper
   */
  public $helper;
  /**
   * Database connection class.
   *
   * @var Drupal\Core\Database\Database
   */
  private $database;

  /**
   * Controller construct.
   */
  public function __construct() {
    $this->database = Database::getConnection();
    $this->helper = new ConfigurationHelper($this->database);
  }

  /**
   * Check machine name exists or not.
   *
   * @param string $key_name
   *   Key name.
   */
  public function isMachineNameExists($key_name) {
    $flag = FALSE;
    $machine_name = $this->helper->createMachineName($key_name);
    if (!empty($machine_name)) {
      $result = $this->database->select('custom_configuration', 'cc')
        ->fields('cc', ['custom_config_id'])
        ->condition('custom_config_machine_name', $machine_name)
        ->execute()->fetch();
      if (!empty($result->custom_config_id)) {
        $flag = TRUE;
      }
    }
    $output = ['status' => $flag, 'machine_name' => $machine_name];
    return new JsonResponse([
      'result' => $output,
    ], 200);
  }

}
