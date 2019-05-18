<?php
namespace Drupal\map_object_field\MapObject;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;

/**
 * Contains methods to manipulate map objects.
 */
class MapObjectService {
  const MAP_OBJECT_CACHE_KEY = 'map_object';
  /**
   * Contains dataMapper object.
   *
   * @var \Drupal\map_object_field\MapObject\MapObjectDataMapper $mapObjectDataMapper
   */
  protected $mapObjectDataMapper;
  /**
   * Contains cache object.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface $cache
   */
  protected $cache;

  /**
   * Constructor.
   */
  public function __construct(MapObjectDataMapper $map_object_data_mapper, CacheBackendInterface $cache) {
    $this->mapObjectDataMapper = $map_object_data_mapper;
    $this->cache = $cache;
  }

  /**
   * Returns array of map objects filtered by params.
   */
  public function getMapObjectsByFieldData($entity_type, $entity_id, $revision_id, $delta) {
    $cache_key = self::MAP_OBJECT_CACHE_KEY . ":{$entity_type}:{$entity_id}:{$revision_id}:{$delta}";
    if (FALSE === ($map_objects = $this->cache->get($cache_key))) {
      /** @var MapObject $map_objects */
      $map_objects = $this->mapObjectDataMapper->getMapObjectsByFieldData($entity_type, $entity_id, $revision_id, $delta);
      if (!is_null($map_objects)) {
        $this->cache->set(
          $cache_key,
          $map_objects,
          Cache::PERMANENT,
          [
            self::MAP_OBJECT_CACHE_KEY . ":{$entity_type}:{$entity_id}:{$revision_id}",
            "{$entity_type}:{$entity_id}",
          ]
        );
        return $map_objects;
      }
      return NULL;
    }
    return $map_objects->data;
  }

  /**
   * Returns map objects packed to string.
   */
  public function getMapObjectsByFieldDataAsString($entity_type, $entity_id, $revision_id, $delta) {
    $map_object_field_data = $this->getMapObjectsByFieldData($entity_type, $entity_id, $revision_id, $delta);
    // Json::serialize doesn't suit because of JSON_NUMERIC_CHECK.
    return json_encode(
      $map_object_field_data,
      JSON_HEX_TAG
      | JSON_HEX_APOS
      | JSON_HEX_AMP
      | JSON_HEX_QUOT
      | JSON_NUMERIC_CHECK
    );
  }

  /**
   * Saves map objects.
   */
  public function saveMapObjects($entity_type, $entity_id, $revision_id, $delta, $data) {
    $cache_key = self::MAP_OBJECT_CACHE_KEY . ":{$entity_type}:{$entity_id}:{$revision_id}";
    $this->cache->delete($cache_key);
    if (!empty($data) && is_array($data)) {
      // Delete all map objects for field.
      foreach ($data as $map_object_data) {
        $map_object_data['entity_type'] = $entity_type;
        $map_object_data['entity_id'] = $entity_id;
        $map_object_data['entity_revision_id'] = $revision_id;
        $map_object_data['entity_field_delta'] = $delta;
        $map_object = $this->createMapObject($map_object_data);
        $this->saveMapObject($map_object);
        $result[] = $map_object;
      }
      return $result;
    }
    else {
      $this->mapObjectDataMapper->deleteMapObject(
        $entity_type,
        $entity_id,
        $revision_id,
        $delta
      );
    }
  }

  /**
   * Saves single map object.
   */
  public function saveMapObject(MapObject $map_object) {
    return $this->mapObjectDataMapper->saveMapObject($map_object);
  }

  /**
   * Creates MapObject instance.
   *
   * @return \Drupal\map_object_field\MapObject\MapObject
   *   MapObject instance.
   */
  public function createMapObject(array $data) {
    return new MapObject($data);
  }

  /**
   * Deletes all map objects for Entity.
   */
  public function deleteAllMapObjectsForEntity($entity_type, $entity_id, $revision_id = NULL, $delta = NULL) {
    $this->mapObjectDataMapper->deleteMapObject($entity_type, $entity_id, $revision_id, $delta);
    $cache_tag = self::MAP_OBJECT_CACHE_KEY . ":{$entity_type}:{$entity_id}:{$revision_id}";
    $this->cache->delete($cache_tag);
  }

}
