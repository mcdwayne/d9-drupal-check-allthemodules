<?php

namespace Drupal\web_accessibility;

use Drupal\Core\Database\Connection;

/**
 * Web Service manager.
 */
class WebServiceManager implements WebServiceInterface {

  /**
   * Node URL placeholder.
   */
  const URL_TOKEN = '<URL>';

  /**
   * Default Web Accessibility Services.
   *
   * @var array
   */
  protected $defaultServices = [
    [
      'name' => 'validator.w3.org (checklink)',
      'url' => 'https://validator.w3.org/checklink?uri=' . self::URL_TOKEN,
    ],
    [
      'name' => 'validator.w3.org (check)',
      'url' => 'https://validator.w3.org/check?uri=' . self::URL_TOKEN,
    ],
    [
      'name' => 'wave.webaim.org',
      'url' => 'http://wave.webaim.org/report#/' . self::URL_TOKEN,
    ],
  ];

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Construct the WebServiceManager.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   Connection.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   *
   * @return array
   *   The form structure.
   */
  public function getDefaultServices() {
    return $this->defaultServices;
  }

  /**
   * {@inheritdoc}
   */
  public function findAll() {
    return $this->connection->query('SELECT * FROM {web_accessibility_services}');
  }

  /**
   * {@inheritdoc}
   */
  public function addService($url, $name) {
    $this->connection->insert('web_accessibility_services')
      ->fields(['url' => $url, 'name' => $name])
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function deleteService($id) {
    $this->connection->delete('web_accessibility_services')
      ->condition('id', $id)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function findById($service_id) {
    return $this->connection->query("SELECT * FROM {web_accessibility_services} WHERE id = :id", [':id' => $service_id])
      ->fetchAssoc();
  }

}
