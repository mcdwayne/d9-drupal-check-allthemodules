<?php


namespace Drupal\drupalmonitor\Controller;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\custom_activities\ActivityService;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Zend\Diactoros\Response\JsonResponse;
use Drupal\Component\Utility\Timer;


class DrupalmonitorController extends ControllerBase {


  /**
   * Callback method for
   *
   * @return array
   */
  public function drupalmonitor() {

    Timer::start('drupalmonitor');
    $hash_request = '';

    if (!empty($_GET['hash'])) {
      $hash_request = $_GET['hash'];
    }
    else {
      $info = array('note'=>'no hash set');
      return new JsonResponse($info);
    }

    $hash_site = \Drupal::config('drupalmonitor.settings')->get('drupalmonitor_hash');
    if (empty($hash_site)) {
      $info = array('note'=>'You have not set a drupalmonitor hash yet on your Drupal site.');
      return new JsonResponse($info);
    }
    elseif ($hash_site == $hash_request) {

      // Output versions.
      $info['drupalmonitor_version'] = DRUPALMONITOR_VERSION;
      $info['drupalversion'] = \Drupal::VERSION;

      // Output allowed data
      $allowed = '';
      if (\Drupal::config('drupalmonitor.settings')->get('drupalmonitor_files_monitoring')) {
        $allowed[] = 'files';
      }
      if (\Drupal::config('drupalmonitor.settings')->get('drupalmonitor_server_monitoring')) {
        $allowed[] = 'server';
      }
      if (\Drupal::config('drupalmonitor.settings')->get('drupalmonitor_user_monitoring')) {
        $allowed[] = 'user';
      }
      //if (\Drupal::config('drupalmonitor.settings')->get('drupalmonitor_performance_monitoring')) {
      //  $allowed[] = 'performance';
      //}
      if (\Drupal::config('drupalmonitor.settings')->get('drupalmonitor_node_monitoring')) {
        $allowed[] = 'node';
      }
      if (\Drupal::config('drupalmonitor.settings')->get('drupalmonitor_modules_monitoring')) {
        $allowed[] = 'modules';
      }
      if (\Drupal::config('drupalmonitor.settings')->get('drupalmonitor_variables_monitoring')) {
        $allowed[] = 'variables';
      }
      //if (\Drupal::config('drupalmonitor.settings')->get('drupalmonitor_custom_monitoring')) {
      //  $allowed[] = 'custom';
      //}
      $info['drupalmonitor_allowed'] = 'data='.implode(',', $allowed);



      // Server metrics.
      if (preg_match('/server/',$_GET['data']) && \Drupal::config('drupalmonitor.settings')->get('drupalmonitor_server_monitoring') == 1) {
        $info['server'] = drupalmonitor_connector_get_serverdata();
      }

      // User user count data.
      if (preg_match('/user/',$_GET['data']) && \Drupal::config('drupalmonitor.settings')->get('drupalmonitor_user_monitoring') == 1) {
        $info['user']['user_usercount'] = drupalmonitor_connector_get_user_usercount();
        $info['user']['user_activesessioncount_300s'] = drupalmonitor_connector_get_user_activesessioncount_300s();
        $info['user']['user_loggedinsessioncount_300s'] = drupalmonitor_connector_get_user_loggedinsessioncount_300s();
      }

      // Files files count data.
      if (preg_match('/files/',$_GET['data']) && \Drupal::config('drupalmonitor.settings')->get('drupalmonitor_files_monitoring') == 1) {
        $info['files']['files_filescount'] = drupalmonitor_connector_get_files_filescount();
        $info['files']['files_filesfoldersize'] = drupalmonitor_connector_get_files_filesfoldersize();
      }

      // Load request data.
      //if (preg_match('/performance/',$_GET['data']) && \Drupal::config('drupalmonitor.settings')->get('drupalmonitor_performance_monitoring') == 1) {
      //  $info['performance'] = drupalmonitor_connector_get_loaddata();
      //}

      // Node content types data.
      if (preg_match('/node/',$_GET['data']) && \Drupal::config('drupalmonitor.settings')->get('drupalmonitor_node_monitoring') == 1) {
        $info['nodes']['drupalmonitor_node_contenttypes'] = drupalmonitor_connector_node_contenttypes();
      }

      // Drupal status.
      if (preg_match('/modules/',$_GET['data']) && \Drupal::config('drupalmonitor.settings')->get('drupalmonitor_modules_monitoring') == 1) {
        $info['tbl_system'] = drupalmonitor_connector_status_listmodules();
      }

      if (preg_match('/variables/',$_GET['data']) && \Drupal::config('drupalmonitor.settings')->get('drupalmonitor_variables_monitoring') == 1) {
        $info['config'] = drupalmonitor_connector_status_listvars();
      }

      // Call hook_drupalmonitor().
      //if (preg_match('/custom/',$_GET['data']) && \Drupal::config('drupalmonitor.settings')->get('drupalmonitor_custom_monitoring') == 1) {
      //  $info['custom'] = module_invoke_all('drupalmonitor');
      //}

      // Drupalmonitor infos.
      $info['drupalmonitor_executiontime'] = Timer::read('drupalmonitor');
      $info['drupalmonitor_status'] = "OK";

      return new JsonResponse($info);
      drupal_exit();
    }
    else {
      $info = array('note'=>'Wrong hash!');
      return new JsonResponse($info);
    }

    // Set correct headers.
    //drupal_page_header('Pragma: no-cache');
    //drupal_page_header('Expires: 0');


  }
  
}