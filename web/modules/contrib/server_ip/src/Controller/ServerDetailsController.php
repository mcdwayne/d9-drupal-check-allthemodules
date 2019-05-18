<?php

namespace Drupal\server_ip\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;

/**
 * Controller routines for server_ip routes.
 */
class ServerDetailsController extends ControllerBase {

  public function get_server_details() {  
    $server_addr = $_SERVER['SERVER_ADDR'];
	$db = Database::getConnectionInfo();
	$hostname = $db['default']['host'];
	$database = $db['default']['database'];
	$current_theme = \Drupal::service('theme.manager')->getActiveTheme()->getName();
	$arr_details = [
	                 'server_addr' => $server_addr,
					 'hostname' => $hostname,
					 'database' => $database,
					 'theme' => $current_theme,
		           ];
    return [
      '#theme' => 'server_details',
      '#result' => $arr_details,
    ];	
  }
}