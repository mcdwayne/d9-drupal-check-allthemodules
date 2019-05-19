<?php

namespace Drupal\web_accessibility;

/**
 * Provides an interface defining a Web Service Manager.
 */
interface WebServiceInterface {

  /**
   * Finds all web accessibility services.
   *
   * @return \Drupal\Core\Database\StatementInterface
   *   The result of the database query.
   */
  public function findAll();

  /**
   * Add web accessibility service.
   *
   * @param string $url
   *   The web accessibility service URL.
   * @param string $name
   *   The web accessibility service name.
   */
  public function addService($url, $name);

  /**
   * Delete web accessibility service.
   *
   * @param string $id
   *   The web accessibility service to delete.
   */
  public function deleteService($id);

  /**
   * Finds a web accessibility service by its ID.
   *
   * @param int $service_id
   *   The ID for a web accessibility service.
   *
   * @return string|false
   *   Either the web accessibility service or FALSE if none exist with that ID.
   */
  public function findById($service_id);

}
