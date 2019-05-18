<?php

namespace Drupal\pwa\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Default controller for the pwa module.
 */
class PWAController extends ControllerBase {

  public function pwa_serviceworker_file_data() {
    $query_string = \Drupal::state()->get('system.css_js_query_string') ?: 0;
    $path = drupal_get_path('module', 'pwa');
    $data =  'importScripts("/' . $path . '/js/serviceworker.js?' . $query_string . '");';

    return new Response($data, 200, [
      'Content-Type' => 'application/javascript',
      'Service-Worker-Allowed' => '/',
    ]);
  }

  public function pwa_offline_page() {
    return [
      '#theme' => 'offline',
    ];
  }

}
