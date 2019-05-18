<?php

/**
 * @file
 * DropShark drush integration.
 */

use Drupal\Component\Utility\UrlHelper;
use GuzzleHttp\Exception\ClientException;

/**
 * Implements hook_drush_command().
 */
function dropshark_drush_command() {
  $commands['dropshark-collect'] = [
    'description' => 'Collect and send data to the DropShark service.',
    'arguments' => [
      'url' => 'The base URL of the site.',
    ],
    'required-arguments' => TRUE,
    'options' => [
      'debug' => 'Provide debug messaging.',
    ],
    'aliases' => ['drpshrk'],
  ];

  return $commands;
}

/**
 * Drush worker callback for collecting and sending data.
 *
 * @param string $url
 *   The base URL of the site.
 *
 * @return bool
 *   Indicates if the command successfully completed.
 */
function drush_dropshark_collect($url) {
  if (!UrlHelper::isValid($url, TRUE)) {
    return drush_set_error(dt('Invalid site URL.'));
  }

  $debug = drush_get_option('debug', FALSE);
  if ($debug) {
    drush_log(dt('Attempting HTTP request to initiate data collection.'), 'ok');
  }

  $state = \Drupal::state();
  $key = $state->get('dropshark.site_id');
  $token = $state->get('dropshark.site_token');

  // Need site_id and site_token.
  if (!$key || !$token) {
    return drush_set_error(dt('DropShark not properly registered.'));
  }

  /** @var \GuzzleHttp\ClientInterface $httpClient */
  $httpClient = \Drupal::service('http_client');
  $url = rtrim($url, '/') . '/dropshark/collect';
  $options['query']['key'] = $key;
  $options['query']['t'] = time();

  $e = $response = NULL;
  try {
    $response = $httpClient->request('GET', $url, $options);
  }
  catch (ClientException $e) {
    if ($e->hasResponse()) {
      $response = $e->getResponse();
    }
  }
  catch (\Exception $e) {
    // No op.
  }

  // Provide the response if debugging is set.
  if ($debug && $response) {
    drush_log(print_r($response, TRUE), 'ok');
  }

  // If there was an exception, error-out.
  if ($e) {
    if ($response) {
      $params = ['!code' => $response->getStatusCode()];
      return drush_set_error(dt('HTTP request failed !code.', $params));
    }
    return drush_set_error(dt('Error while performing HTTP request.'));
  }

  if ($debug) {
    drush_log(dt('Sending data to DropShark.'), 'ok');
  }
  /** @var \Drupal\dropshark\Queue\QueueInterface $queue */
  $queue = \Drupal::service('dropshark.queue');
  sleep(2);
  $queue->transmit();

  drush_log(dt('Collection complete.'), 'ok');
}
