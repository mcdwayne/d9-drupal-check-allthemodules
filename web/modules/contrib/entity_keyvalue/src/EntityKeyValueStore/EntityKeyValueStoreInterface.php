<?php


namespace Drupal\entity_keyvalue\EntityKeyValueStore;


use Drupal\Core\Entity\EntityInterface;

interface EntityKeyValueStoreInterface {

  /**
   * Gets data keys and its default values.
   *
   * @param EntityInterface $entity
   *   Entity to operate with its key:value data.
   *
   * @return array
   *   Structure: [key => key options].
   */
  public function getDataStructure(EntityInterface $entity);

  /**
   * Gets key for key-value system.
   *
   * @param EntityInterface $entity
   *   Entity to operate with its key:value data.
   * @param string $property
   *   Property name.
   *
   * @return string.
   *   Key for key-value system.
   */
  public function getKey(EntityInterface $entity, $property);

  /**
   * Gets all additional data keys for key-value system.
   *
   * @param EntityInterface $entity
   *   Entity to operate with its key:value data.
   *
   * @return string[]
   *   List of keys.
   */
  public function getKeys(EntityInterface $entity);

  /**
   * Gets additional data for entity.
   *
   * @param EntityInterface $entity
   *   Entity to operate with its key:value data.
   * @param string[] $keys
   *   List of keys to load or null to load all keys.
   * @param bool $reload
   *   TRUE to reload storage from database.
   *
   * @return array
   *   Additional data: [property => value].
   *
   * @throws \InvalidArgumentException
   *   Unknown key exception.
   * @throws \RuntimeException
   *   Incorrect value type exception.
   */
  public function loadValues(EntityInterface $entity, array $keys = NULL, $reload = FALSE);

  /**
   * Gets single value for entity.
   *
   * @param EntityInterface $entity
   *   Entity to operate with its key:value data.
   * @param string $key
   *   Key to get its value.
   * @param bool $reload
   *   TRUE to reload storage from database.
   *
   * @return mixed.
   *   Loaded value.
   *
   * @throws \InvalidArgumentException
   *   Unknown key exception.
   * @throws \RuntimeException
   *   Incorrect value type exception.
   */
  public function loadValue(EntityInterface $entity, $key, $reload = FALSE);

  /**
   * Sets additional data for entity (multiple values).
   *
   * @param EntityInterface $entity
   *   Entity to operate with its key:value data.
   * @param array $data
   *   Additional data to be set (property => value pairs).
   *
   * @throws \RuntimeException
   *   Incorrect property type exception.
   * @throws \InvalidArgumentException
   *   Unknown key exception.
   */
  public function setValues(EntityInterface $entity, array $data);

  /**
   * Sets additional data for entity (single value).
   *
   * @param EntityInterface $entity
   *   Entity to operate with its key:value data.
   * @param string $key
   *   Additional data key.
   * @param mixed $value
   *   Additional data value.
   *
   * @throws \RuntimeException
   *   Incorrect property type exception.
   * @throws \InvalidArgumentException
   *   Unknown key exception.
   */
  public function setValue(EntityInterface $entity, $key, $value);

  /**
   * Delete additional data.
   *
   * @param EntityInterface $entity
   *   Entity to operate with its key:value data.
   * @param array|null $keys
   *   Keys to be deleted or null to clear all keys.
   *
   * @throws \InvalidArgumentException
   *   Unknown key exception.
   */
  public function deleteValues(EntityInterface $entity, array $keys = NULL);

  /**
   * Delete additional data.
   *
   * @param EntityInterface $entity
   *   Entity to operate with its key:value data.
   * @param string $key
   *   Key to delete.
   *
   * @throws \InvalidArgumentException
   *   Unknown key exception.
   */
  public function deleteValue(EntityInterface $entity, $key);

  /**
   * Clears stored data to avoid outdated data containing.
   */
  public function clearLoadedData();

}
