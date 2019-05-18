<?php


namespace Drupal\data_api;


use AKlump\Data\DataInterface;

interface DrupalDataInterface extends DataInterface {

  /**
   * Get a date object from $subject.
   *
   * @param object|array $subject
   * @param string $path
   * @param null $defaultValue
   * @param null $valueCallback
   *
   * @return mixed|null
   */
  public function getDate($subject, $path, $defaultValue = NULL, $valueCallback = NULL);

  /**
   * Set a date item from an object
   *
   * @param object|array $subject
   * @param string $path
   * @param mixed $value
   *
   * @return $this|\AKlump\Data\Data
   *
   * // TODO Support for chaining in.
   */
  public function setDate(&$subject, $path = NULL, $value = NULL);

  /**
   * Return the entity type registered for this instance.
   *
   * @return string|null
   *   The registered entity type.
   */
  public function getEntityType();

  /**
   * Register an entity type for this instance.
   *
   * @param string $entity_type
   *   The entity type.  This is used for localization and field item values.
   *
   * @return $this
   */
  public function setEntityType($entity_type);

}
