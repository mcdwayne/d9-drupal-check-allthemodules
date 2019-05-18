<?php
namespace Drupal\map_object_field\MapObject;

use Drupal\Core\Database\Connection;

/**
 * Saves and retrieves data from database.
 */
class MapObjectDataMapper {

  /**
   * DBConnections object.
   *
   * @var \Drupal\Core\Database\Connection $dbConnection
   */
  protected $dbConnection;

  /**
   * Constructor.
   */
  public function __construct(Connection $connection) {
    $this->dbConnection = $connection;
  }

  /**
   * Retrieves map objects from db.
   */
  public function getMapObjectsByFieldData($entity_type, $entity_id, $revision_id, $delta) {
    $result = [];
    $query_map_object = $this->dbConnection->select('map_object', 'mo')
      ->fields('mo')
      ->condition('mo.entity_type', $entity_type)
      ->condition('mo.entity_id', $entity_id)
      ->condition('mo.entity_field_delta', $delta);
    if (!is_null($revision_id)) {
      $query_map_object->condition('mo.entity_revision_id', $revision_id);
    }

    $result_map_objects = $query_map_object->execute()
      ->fetchAllAssoc('map_object_id');
    if (!empty($result_map_objects)) {
      $result_map_objects_keys = array_keys($result_map_objects);
      $coordinates = $this->getMapObjectCoordinates($result_map_objects_keys);
      $coordinates_per_object = [];
      foreach ($coordinates as $coordinate) {
        $coordinates_per_object[$coordinate->map_object_id][] = [
          'lat' => $coordinate->lat,
          'lng' => $coordinate->lng,
          'weight' => $coordinate->weight,
        ];
      }
      $extra_params = $this->getMapObjectExtraParams($result_map_objects_keys);
      $extra_params_per_object = [];
      foreach ($extra_params as $extra_param) {
        $extra_params_per_object[$extra_param->map_object_id][$extra_param->extra_key] = $extra_param->extra_value;
      }

      foreach ($result_map_objects as $map_object_id => $result_map_object) {
        $map_object = new MapObject($result_map_object);
        if (isset($coordinates_per_object[$map_object_id])) {
          $map_object->setObjectCoordinates($coordinates_per_object[$map_object_id]);
        }
        if (isset($extra_params_per_object[$map_object_id])) {
          $map_object->setExtraParams($extra_params_per_object[$map_object_id]);
        }
        $result[] = $map_object;
      }
    }
    return $result;
  }

  /**
   * Retrieves map object coordinates from DB.
   */
  public function getMapObjectCoordinates($map_object_id) {
    $query_coordinates = $this->dbConnection->select('map_object_coordinate', 'mc')
      ->fields('mc');
    if (is_array($map_object_id)) {
      $query_coordinates->condition('mc.map_object_id', $map_object_id, 'IN');
    }
    else {
      $query_coordinates->condition('mc.map_object_id', $map_object_id);
    }
    $result = $query_coordinates->execute();
    return $result->fetchAll();
  }

  /**
   * Retrieves map object extra params from DB.
   */
  public function getMapObjectExtraParams($map_object_id) {
    $query_coordinates = $this->dbConnection
      ->select('map_object_extra_params', 'me')
      ->fields('me');
    if (is_array($map_object_id)) {
      $query_coordinates->condition('me.map_object_id', $map_object_id, 'IN');
    }
    else {
      $query_coordinates->condition('me.map_object_id', $map_object_id);
    }
    $result = $query_coordinates->execute();
    return $result->fetchAllAssoc('map_object_extra_param_id');
  }

  /**
   * Saves map object to DB.
   */
  public function saveMapObject(MapObject $map_object) {
    try {
      /** @var \Drupal\Core\Database\Transaction $transaction */
      $transaction = $this->dbConnection->startTransaction();
      $result = $this->dbConnection->insert('map_object')
        ->fields([
          'type' => $map_object->getObjectType(),
          'entity_type' => $map_object->getEntityType(),
          'entity_id' => $map_object->getEntityId(),
          'entity_revision_id' => $map_object->getEntityRevisionId(),
          'entity_field_delta' => $map_object->getEntityFieldDelta(),
        ])->execute();
      if ($result) {
        $map_object->setId($result);
      }
      $this->saveObjectExtraParams($map_object->getId(), $map_object->getExtraParams());
      $this->saveObjectCoordinates($map_object->getId(), $map_object->getObjectCoordinates());
      return TRUE;
    }
    catch (\Exception $e) {
      $transaction->rollback();
      throw $e;
    }
  }

  /**
   * Saves object coordinates to DB.
   */
  protected function saveObjectCoordinates($map_object_id, array $coordinates) {
    $this->deleteObjectCoordinates($map_object_id);
    if (!empty($coordinates)) {
      $query = $this->dbConnection->insert('map_object_coordinate')
        ->fields(['map_object_id', 'weight', 'lat', 'lng']);
      foreach ($coordinates as $weight => $coordinate) {
        $query->values([
          'map_object_id' => $map_object_id,
          'weight' => $weight,
          'lat' => $coordinate['lat'],
          'lng' => $coordinate['lng'],
        ]);
      }
      $query->execute();
    }
  }

  /**
   * Saves object extraparams to DB.
   */
  protected function saveObjectExtraParams($map_object_id, $extra_params) {
    $this->deleteObjectExtraParams($map_object_id);
    if (!empty($extra_params)) {
      $query = $this->dbConnection->insert('map_object_extra_params')
        ->fields(['map_object_id', 'extra_key', 'extra_value']);
      $execute = FALSE;
      foreach ($extra_params as $extra_param_key => $extra_param_value) {
        if (!empty($extra_param_value)) {
          $query->values([
            'map_object_id' => $map_object_id,
            'extra_key' => $extra_param_key,
            'extra_value' => $extra_param_value,
          ]);
          $execute = TRUE;
        }
      }
      if ($execute) {
        $query->execute();
      }
    }
  }

  /**
   * Deletes map object from DB.
   */
  public function deleteMapObject($entity_type, $entity_id, $revision_id, $delta = NULL) {
    $query = $this->dbConnection->select('map_object', 'mo')
      ->fields('mo', ['map_object_id'])
      ->condition('entity_type', $entity_type)
      ->condition('entity_id', $entity_id);
    if (!empty($revision_id)) {
      $query->condition('entity_revision_id', $revision_id);
    }
    if ($delta !== NULL) {
      $query->condition('entity_field_delta', $delta);
    }

    $ids = $query->execute()->fetchCol();
    if (!empty($ids)) {
      $this->dbConnection->delete('map_object')
        ->condition('map_object_id', $ids, 'IN')
        ->execute();
      $this->deleteObjectCoordinates($ids);
      $this->deleteObjectExtraParams($ids);
    }
  }

  /**
   * Deletes map object coordinates from DB.
   */
  public function deleteObjectCoordinates($id) {
    $query = $this->dbConnection->delete('map_object_coordinate');
    if (is_array($id)) {
      $query->condition('map_object_id', $id, 'IN');
    }
    elseif (is_numeric($id) || is_string($id)) {
      $query->condition('map_object_id', $id);
    }
    $query->execute();
  }

  /**
   * Deletes map object extra params from db.
   */
  public function deleteObjectExtraParams($id) {
    $query = $this->dbConnection->delete('map_object_extra_params');
    if (is_array($id)) {
      $query->condition('map_object_id', $id, 'IN');
    }
    elseif (is_numeric($id) || is_string($id)) {
      $query->condition('map_object_id', $id);
    }
    $query->execute();
  }

}
