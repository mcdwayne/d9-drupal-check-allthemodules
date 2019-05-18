<?php


namespace Drupal\entity_keyvalue\EntityKeyValueStore;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\KeyValueStore\KeyValueFactory;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Drupal\entity_keyvalue\EntityKeyValueStoreProvider;
use Drupal\entity_keyvalue\Exception\EntityKeyValueTypeException;

class EntityKeyValueStoreDefault implements EntityKeyValueStoreInterface {

  /**
   * @var array
   */
  protected $data = [];

  /**
   * @var EntityKeyValueStoreProvider.
   */
  protected $entityKeyValueStoreProvider;

  /**
   * @var KeyValueStoreInterface.
   */
  protected $keyValueStore;

  /**
   * EntityKeyValueStoreDefault constructor.
   *
   * @param EntityKeyValueStoreProvider $entityKeyValueStoreProvider
   * @param KeyValueFactoryInterface $keyValue
   */
  public function __construct(EntityKeyValueStoreProvider $entityKeyValueStoreProvider, KeyValueFactoryInterface $keyValue) {
    $this->entityKeyValueStoreProvider = $entityKeyValueStoreProvider;
    $this->keyValueStore = $keyValue->get('entity_keyvalue');
  }

  /**
   * {@inheritdoc}
   */
  public function getDataStructure(EntityInterface $entity) {
    return $this->entityKeyValueStoreProvider->getEntityKeyValueConfig($entity->getEntityTypeId())['keys'];
  }

  /**
   * {@inheritdoc}
   */
  public function getKey(EntityInterface $entity, $property) {
    return implode(':',
      [
        $entity->getEntityTypeId(),
        $entity->id(),
        $property
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getKeys(EntityInterface $entity) {
    $keys = array_keys($this->getDataStructure($entity));

    $that = $this;
    return array_map(
      function ($property) use ($that, $entity) {
        return $that->getKey($entity, $property);
      },
      array_combine($keys, $keys)
    );
  }

  /**
   * {@inheritdoc}
   */
  public function loadValues(EntityInterface $entity, array $keys = NULL, $reload = FALSE) {
    $keys_mapping = $this->getKeys($entity);
    if ($keys === NULL) {
      $keys = array_keys($keys_mapping);
    }
    $unknown_keys = array_diff($keys, array_keys($keys_mapping));
    if ($unknown_keys) {
      throw new \InvalidArgumentException('Unknown entity keys: ' . implode(', ', $unknown_keys));
    }

    $return = [];
    $keys_to_load_mapping = [];
    foreach ($keys as $key) {
      // Fulfilling keys to make the request to database or taking values from storage.
      if ($reload || !isset($this->data[$entity->getEntityTypeId()][$entity->id()][$key])) {
        $keys_to_load_mapping[$key] = $keys_mapping[$key];
      }
      else {
        $return[$key] = $this->data[$entity->getEntityTypeId()][$entity->id()][$key];
      }
    }

    if ($keys_to_load_mapping) {
      $loaded_data = [];
      // Loading data.
      foreach ($this->keyValueStore->getMultiple($keys_to_load_mapping) as $storage_key => $loaded_value) {
        $entity_key = array_search($storage_key, $keys_to_load_mapping, TRUE);
        $loaded_data[$entity_key] = $loaded_value;
      }
      // Fulfilling loaded data with default values if there are no values in database.
      foreach ($keys_to_load_mapping as $entity_key => $item) {
        if (!isset($loaded_data[$entity_key])) {
          $loaded_data[$entity_key] = $this->getDefaultValue($entity, $entity_key);
        }
      }
      $loaded_and_processed_data = [];
      foreach ($loaded_data as $key => $value) {
        $this->data[$entity->getEntityTypeId()][$entity->id()][$key] = $this->processValue(
          $entity,
          $key,
          $value
        );
        $loaded_and_processed_data[$key] = $this->data[$entity->getEntityTypeId()][$entity->id()][$key];
      }
      $this->onLoad($entity, $loaded_and_processed_data);
      $return = array_merge($return, $loaded_and_processed_data);
    }

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function loadValue(EntityInterface $entity, $key, $reload = FALSE) {
    return $this->loadValues($entity, [$key], $reload)[$key];
  }

  /**
   * Gets default value for key.
   *
   * @param EntityInterface $entity
   *   Entity object.
   * @param string $key
   *   Key to get its default value.
   *
   * @return mixed
   *   Default value for the $key.
   *
   * @throws \InvalidArgumentException
   *   Unknown entity key.
   */
  protected function getDefaultValue(EntityInterface $entity, $key) {
    if (!isset($this->getDataStructure($entity)[$key])) {
      throw new \InvalidArgumentException('Unknown entity key: ' . $key);
    }
    $item = $this->getDataStructure($entity)[$key];

    return is_callable($item['default_value'])
      ? $item['default_value']($entity)
      : $item['default_value'];
  }

  /**
   * {@inheritdoc}
   */
  public function setValues(EntityInterface $entity, array $data) {
    $keys_mapping = $this->getKeys($entity);
    if ($keys_mapping) {
      $data_to_store = [];
      foreach ($data as $key => $value) {
        if (!isset($keys_mapping[$key])) {
          throw new \InvalidArgumentException('Unknown entity key: ' . $key);
        }
        $store_key = $keys_mapping[$key];
        $data_to_store[$store_key] = $this->processValue(
          $entity,
          $key,
          $data[$key]
        );
        $this->data[$entity->getEntityTypeId()][$entity->id()][$key] = $data_to_store[$store_key];
      }
      if ($data_to_store) {
        $this->keyValueStore->setMultiple($data_to_store);
        $this->onSave($entity, $data);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setValue(EntityInterface $entity, $key, $value) {
    $this->setValues($entity, [$key => $value]);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteValues(EntityInterface $entity, array $keys = NULL) {
    $keys_mapping = $this->getKeys($entity);
    if ($keys_mapping) {
      if ($keys === NULL) {
        $keys = array_keys($keys_mapping);
      }
      $keys_to_delete = [];
      foreach ($keys as $key) {
        if (!isset($keys_mapping[$key])) {
          throw new \InvalidArgumentException('Unknown entity key: ' . $key);
        }
        $keys_to_delete[] = $keys_mapping[$key];
        if (isset($this->data[$entity->getEntityTypeId()][$entity->id()][$key])) {
          unset($this->data[$entity->getEntityTypeId()][$entity->id()][$key]);
        }
      }
      if ($keys_to_delete) {
        $this->keyValueStore->deleteMultiple($keys_to_delete);
        $this->onDelete($entity, $keys);
      }
    }
  }

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
  public function deleteValue(EntityInterface $entity, $key) {
    $this->deleteValues($entity, [$key]);
  }

  /**
   * Process value to cast it to declared type.
   *
   * @param EntityInterface $entity
   *   Entity to operate with its key:value data.
   * @param string $key
   *   Property name.
   * @param mixed $value
   *   Value to be processed.
   *
   * @return mixed
   *   Processed value.
   *
   * @throws \RuntimeException
   *   Incorrect property type exception.
   */
  protected function processValue(EntityInterface $entity, $key, $value) {
    $return = NULL;
    $type = isset($this->getDataStructure($entity)[$key]['type'])
      ? $this->getDataStructure($entity)[$key]['type']
      : 'any';
    switch ($type) {
      case 'any':
        $return = $value;
        break;

      case 'int':
      case 'integer':
        $return = (int) $value;
        break;

      case 'float':
        $return = (float) $value;
        break;

      case 'string':
        $return = (string) $value;
        break;

      case 'array':
        if (!is_array($value)) {
          throw new EntityKeyValueTypeException('Value for key ' . $key . ' has incorrect type (should be array)');
        }
        $return = $value;
        break;

      case 'object':
      if (!is_object($value)) {
          throw new EntityKeyValueTypeException('Value for key ' . $key . ' has incorrect type (should be object)');
        }
        $return = $value;
        break;

      default:
        throw new \RuntimeException('There are no casting cases for type: ' . $type);
    }

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function clearLoadedData() {
    $this->data = [];
  }

  /**
   * Calls after every "save" action to operate with saved data.
   *
   * @param EntityInterface $entity
   *   Entity object.
   *
   * @param array $stored_data
   *   Stored data: key => value.
   */
  protected function onSave(EntityInterface $entity, array $stored_data) {
    // Can be implemented in custom logic.
  }

  /**
   * Calls after every "load" action to operate with loaded data.
   *
   * @param EntityInterface $entity
   *   Entity object.
   *
   * @param array $loaded_data
   *   Loaded data: key => value.
   */
  protected function onLoad(EntityInterface $entity, array $loaded_data) {
    // Can be implemented in custom logic.
  }

  /**
   * Calls after every "delete" action to operate with deleted data.
   *
   * @param EntityInterface $entity
   *   Entity object.
   *
   * @param string[] $deleted_keys
   *   Deleted data: array of deleted keys.
   */
  protected function onDelete(EntityInterface $entity, array $deleted_keys) {
    // Can be implemented in custom logic.
  }

}
