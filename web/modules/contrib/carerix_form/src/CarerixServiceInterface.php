<?php

namespace Drupal\carerix_form;

/**
 * Interface CarerixInterface.
 */
interface CarerixServiceInterface {

  /**
   * Helper function report logging to Drupal log.
   *
   * @param string $message
   *   The error message.
   * @param string $status
   *   Error status.
   * @param string $severity
   *   Error severity.
   */
  public static function report($message, $status = 'error', $severity = 'error');

  /**
   * Fetch a carerix entity by id.
   *
   * @param string $carerixEntity
   *   Carerix entity type.
   * @param int $id
   *   Carerix entity id.
   * @param array $show
   *   Fields to show.
   * @param string $language
   *   Language parameter.
   *
   * @return \Carerix_Api_Rest_Entity|null
   *
   * @throws \Exception
   */
  public function getEntityById($carerixEntity, $id, array $show = [], $language = 'English');

  /**
   * Fetch all carerix entities for given type.
   *
   * @param string $carerixEntity
   *   Carerix entity type.
   * @param array $params
   *   Array of parameters.
   *
   * @return \Carerix_Api_Rest_Entity_Collection|null
   */
  public function getAllEntities($carerixEntity, array $params = []);

  /**
   * @param array $values
   * @param array $files
   * @param array $urls
   */
  public function createEmployee(array $values, array $files = [], array $urls = []);

}
