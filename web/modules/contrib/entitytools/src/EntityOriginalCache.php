<?php

namespace Drupal\entitytools;

use Drupal\Core\Entity\EntityInterface;

class EntityOriginalCache {

  static $cache;

  public static function preSave(EntityInterface $entity) {
    if (isset($entity->original)) {
      self::$cache[$entity->getEntityTypeId()][$entity->id()] = $entity->original;
    }
  }

  public static function get($entityTypeId, $id) {
    return isset(self::$cache[$entityTypeId][$id]) ? self::$cache[$entityTypeId][$id] : NULL;
  }

}
