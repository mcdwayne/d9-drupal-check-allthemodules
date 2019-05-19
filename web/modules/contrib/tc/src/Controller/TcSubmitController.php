<?php

namespace Drupal\tc\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Utility\Unicode;
use GuzzleHttp\Exception\RequestException;

class TcSubmitController extends ControllerBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * Constructs a TcSubmitController object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \GuzzleHttp\Client $httpClient
   *   The HTTP client.
   */
  public function __construct(Connection $connection, Client $httpClient) {
    $this->connection = $connection;
    $this->httpClient = $httpClient;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var Connection $connection */
    $connection = $container->get('database');
    /** @var Client $httpClient */
    $httpClient = $container->get('http_client');
    return new static(
      $connection,
      $httpClient
    );
  }

  /**
   * Stores the submitted data for the given write key.
   */
  public function submit(Request $request) {
    $request_query = $request->query;
    $write_key = $request_query->get('w');
    $settings_raw = $this->connection->select('tc_user', 'tu')
      ->fields('tu', ['uid', 'settings'])
      ->condition('write_key', $write_key)
      ->execute()
      ->fetch();
    if (!$settings_raw) {
      // @FIXME: Error handling: Write key not found.
      return [
        '#markup' => $this->t('Write key not found.'),
      ];
    }
    $settings = unserialize($settings_raw->settings);
    if ($relay = &$settings['thingspeak_relay']) {
      $relay_fields = [];
    }
    $uid = $settings_raw->uid;
    $store = [];
    foreach (_tc_get_fields() as $field) {
      $field_value = $request_query->get($field);
      if ($field_value && $settings['field_enabled'][$field] && (!$settings['field_skip_na'][$field] || ($field_value != 'N/A' && $field_value != '-60.0'))) {
        $field_value = Unicode::substr($field_value, 0, 7);
        $store[$field] = $field_value;
        if ($relay && count($relay_fields) < 8) {
          $relay_fields[] = 'field' . (count($relay_fields) + 1) . '=' . $field_value;
        }
      }
    }
    if (!$store) {
      // @FIXME: Error handling: No data for any of the enabled fields.
      return [
        '#markup' => $this->t('No data for any of the enabled fields.'),
      ];
    }
    $timestamp = REQUEST_TIME;
    foreach ($store as $field_id => $field_value) {
      $this->connection->merge('tc_data')
        ->keys([
          'uid' => $uid,
          'timestamp' => $timestamp,
          'field_id' => $field_id,
        ])
        ->fields([
          'field_value' => $field_value,
        ])
        ->execute();
    }
    if ($settings['thingspeak_relay']) {
      try {
        $this->httpClient->get('http://api.thingspeak.com/update', [
          'query' => 'key=' . $settings['thingspeak_write_key'] . '&' . implode('&', $relay_fields),
        ]);
      }
      catch (RequestException $e) {
        watchdog_exception('tc', $e);
      }
    }
    return [
      '#type' => 'markup',
      '#markup' => $this->t('Successfully stored @number values.', ['@number' => count($store)]),
    ];
  }
}
