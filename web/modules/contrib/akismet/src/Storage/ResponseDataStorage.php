<?php
/**
 * ResponseDataStorage is responsible for storing and managing response
 * data retrieved from the Akismet system for any protected entities.
 */

namespace Drupal\akismet\Storage;

use Drupal\Core\Database\Query\Merge;


class ResponseDataStorage {

  /**
   * Save Akismet validation data to the database.
   *
   * Based on the specified entity type and id, this function stores the
   * validation results returned by Akismet in the database.
   *
   * @param $data
   *   An object containing Akismet session data for the entity, containing at
   *   least the following properties:
   *   - entity: The entity type of the data to save.
   *   - id: The entity ID the data belongs to.
   *   - form_id: The form ID the session data belongs to.
   *   - request: An associative array of keys and values originally sent to
   *   Akismet for analysis.
   *
   * @return mixed
   * @throws \Exception
   */
  public static function save($data) {
    $data->changed = REQUEST_TIME;

    $defaults = array(
      'entity' => '',
      'id' => 0,
      'guid' => '',
      'form_id' => '',
      'changed' => 0,
      'moderate' => 0,
      'classification' => '',
      'request' => [],
    );
    $data = array_filter((array) $data, function($value) {
      return $value !== NULL;
    });

    $fields = array_intersect_key($data + $defaults, $defaults);
    $fields['request'] = serialize($fields['request']);

    $result = \Drupal::database()->merge('akismet')
      ->keys(['id' => $data['id'], 'entity' => $data['entity']])
      ->fields($fields)
      ->execute();

    if ($result === Merge::STATUS_INSERT) {
      \Drupal::moduleHandler()->invokeAll('akismet_data_insert', $data);
    }
    else {
      \Drupal::moduleHandler()->invokeAll('akismet_data_update', $data);
    }
    return $data;
  }

  /**
   * Deletes an Akismet session data record from the database.
   *
   * @param $entity
   *   The entity type to delete data for.
   * @param $id
   *   The entity id to delete data for.
   */
  public static function delete($entity, $id) {
    return self::deleteMultiple($entity, array($id));
  }

  /**
   * Deletes multiple Akismet session data records from the database.
   *
   * @param $entity
   *   The entity type to delete data for.
   * @param $ids
   *   An array of entity ids to delete data for.
   */
  public static function deleteMultiple($entity, array $ids) {
    foreach ($ids as $id) {
      $data = self::loadByEntity($entity, $id);
      if ($data) {
        \Drupal::moduleHandler()->invokeAll('akismet_data_delete', array($data));
      }
    }
    return \Drupal::database()->delete('akismet')
      ->condition('entity', $entity)
      ->condition('id', $ids, 'IN')
      ->execute();
  }

  /**
   * Load an Akismet data record by contentId.
   *
   * @param $content_id
   *   The content_id to retrieve data for.
   */
  public static function loadByContent($content_id) {
    $data = \Drupal::database()->select('akismet', 'm')
      ->fields('m')
      ->condition('m.content_id', $content_id)
      ->range(0, 1)
      ->execute()
      ->fetchObject();
    $data->request = unserialize($data->request);
    return $data;
  }

  /**
   * Load an Akismet data record from the database.
   *
   * @param $entity
   *   The entity type to retrieve data for.
   * @param $id
   *   The entity id to retrieve data for.
   */
  public static function loadByEntity($entity, $id) {
    $data = \Drupal::database()->select('akismet', 'm')
      ->fields('m')
      ->condition('m.entity', $entity)
      ->condition('m.id', $id)
      ->range(0, 1)
      ->execute()
      ->fetchObject();
    if (!empty($data->request)) {
      $data->request = unserialize($data->request);
    }
    return $data;
  }

  /**
   * Loads the Akismet data records from the database for a specific entity type.
   *
   * @param $entity
   *   The entity type to retrieve data for.
   *
   * @return array
   *   The matching Akismet data as an array keyed by entity id.
   */
  public static function loadByEntityType($type) {
    $data = \Drupal::database()->select('akismet', 'm')
      ->fields('m')
      ->condition('m.entity', $type)
      ->execute()
      ->fetchAllAssoc('id');
    foreach ($data as $key => $array) {
      $array['request'] = unserialize($array['request']);
      $data[$key] = $array;
    }
    return $data;
  }
} 
