<?php

/**
 * @file
 * Contains \Drupal\govreday\Controller\GovReadyController.
 */

namespace Drupal\govready\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Provides route responses for the Example module.
 */
class GovreadyPage extends ControllerBase {

  /**
   * Page callback for the GovReady Dashboard.
   */
  public function govready_dashboard() {

    //module_load_include('class.php', 'govready', 'lib/govready-dashboard');
    $dashboard = new \Drupal\govready\Controller\GovreadyDashboard();
    return $dashboard->dashboardPage();

  }

  /**
   * Call the GovReady Agent trigger.
   */
  public function govready_trigger_callback() {

    //module_load_include('class.php', 'govready', 'lib/govready-agent');
    $agent = new \Drupal\govready\Controller\GovreadyAgent();
    return $agent->ping();

  }

  /**
   * Refresh the access token.
   */
  public function govready_refresh_token($return = FALSE) {

    // @todo: nonce this call
    $options = \Drupal::config('govready.settings')->get('govready_options');
    if (!empty($_REQUEST['refresh_token']) && $_REQUEST['refresh_token']) {
      $token = $_REQUEST['refresh_token'];
      $options['refresh_token'] = $token;
      \Drupal::configFactory()->getEditable('govready.settings')
        ->set('govready_options', $options)
        ->save();
    }
    else {
      $token = !empty($options['refresh_token']) ? $options['refresh_token'] : '';
    }

    $response = govready_api('/refresh-token', 'POST', array('refresh_token' => $token), TRUE);
    $response['endoflife'] = time() + (int) $response['expires_in'];
    \Drupal::configFactory()->getEditable('govready.settings')
        ->set('govready_token', $response)
        ->save();

    if ($return) {
      return $response;
    }
    else {
      return new JsonResponse($response);
    }

  }

  /**
   * Call the GovReady API.
   */
  public function govready_api_proxy() {

    $method = !empty($_REQUEST['method']) ? $_REQUEST['method'] : $_SERVER['REQUEST_METHOD'];
    $response = govready_api($_REQUEST['endpoint'], $method, $_REQUEST);
    return new JsonResponse($response);

  }

}