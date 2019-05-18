<?php

/**
 * @file
 * Displays the GovReady Dashboard.
 */

namespace Drupal\govready\Controller;

class GovreadyDashboard {

  /**
   * Construct function.
   */
  function __construct() {
    $this->path = drupal_get_path('module', 'govready');
    $this->config = govready_config();
  }

  /**
   * Display the GovReady dashboard.
   */
  public function dashboardPage() {
    $options = \Drupal::config('govready.settings')->get('govready_options');
    $token_generator = \Drupal::csrfToken();

    $path = $this->path . '/includes/js/';
    $settings = array(
      // @todo: change
      //'api_endpoint' => \Drupal::url('/govready/api'),
      //'token_endpoint' => \Drupal::url('/govready/refresh-token'),
      //'trigger_endpoint' => \Drupal::url('/govready/trigger'),
      'api_endpoint' => '/govready/api',
      'token_endpoint' => '/govready/refresh-token',
      'trigger_endpoint' => '/govready/trigger',
    );

    // First time using app, need to set everything up.
    if (empty($options['refresh_token'])) {

      // Call GovReady /initialize to set the allowed CORS endpoint.
      // @todo: error handling: redirect user to GovReady API dedicated login page
      global $base_url;
      if (empty($options['siteId'])) {
        $data = array(
          'url' => $base_url,
          'application' => 'drupal',
        );
        $response = \Drupal\govready\Controller\GovreadyPage::govready_api('/initialize', 'POST', $data, TRUE);
        $options['siteId'] = $response['_id'];
        \Drupal::configFactory()->getEditable('govready.settings')
          ->set('govready_options', $options)
          ->save();
      }

      // Save some JS variables (available at govready.siteId, etc)
      //drupal_add_js($path . 'govready-connect.js');
      $settings = array_merge(array(
        'govready_nonce' => $token_generator->get(),
        'auth0' => $this->config['auth0'],
        'siteId' => $options['siteId'],
      ), $settings);
      //drupal_add_js(array('govready_connect' => $settings), 'setting');

      //return theme('govready_connect');
      $build = array();
      $build['#theme'] = 'govready_connect';
      $build['#attached']['library'][] = 'govready/govready-connect';
      $build['#attached']['drupalSettings']['govready_connect'] = $settings;

      dpm($build);

      return $build;

    }

    // Show me the dashboard!
    else {

      $config = govready_config();

      // Save some JS variables (available at govready.siteId, etc)
      $settings = array_merge(array(
        'govready_nonce' => $token_generator->get(),
        'siteId' => !is_null($options['siteId']) ? $options['siteId'] : NULL,
        'mode' => !empty($options['mode']) ? $options['mode'] : 'remote',
        // @todo: 'nonce' => wp_create_nonce( $this->key )
        'connectUrl' => $config['govready_url'],
      ), $settings);
      //drupal_add_js(array('govready' => $settings), 'setting');

      // Enqueue react.
      /*drupal_add_js($path . 'client/dist/vendor.dist.js', array(
        'scope' => 'footer',
        'group' => 'GovReady',
        'weight' => 1,
      ));
      drupal_add_js($path . 'client/dist/app.dist.js', array(
        'scope' => 'footer',
        'group' => 'GovReady',
        'weight' => 2,
      ));
      drupal_add_css($path . 'client/dist/app.dist.css');*/

      // Assemble the markup.
      $build = array();
      $build['#theme'] = 'govready_dashboard';
      $build['#attached']['library'][] = 'govready/govready-dashboard';
      
      // @todo: We are using a hacky D7-esque Drupal.settings injection instead of what we should be using:
      //$build['#attached']['drupalSettings']['govready'] = $settings;
      $build['#js_settings'] = 'var d8GovreadySettings = ' . json_encode($settings);   

      return $build;

      

      //return theme('govready_dashboard');

    } // if()

  }

}
