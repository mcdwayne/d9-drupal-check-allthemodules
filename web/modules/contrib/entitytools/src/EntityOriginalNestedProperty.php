<?php

namespace Drupal\entitytools;

use Drupal\Core\TypedData\DataReferenceInterface;
use Drupal\Core\Entity\Plugin\DataType\EntityAdapter;
use Drupal\entitytools\EntityNestedProperty;
use Drupal\entitytools\EntityOriginalCache;

class EntityOriginalNestedProperty extends EntityNestedProperty {

  public static function create($current) {
    return new static(@$current->original);
  }

  protected static function dereference(DataReferenceInterface $next) {
    $ref = $next->getTarget();
    if ($ref instanceof EntityAdapter) {
      $entity = $ref->getValue();
      if ($entity) {
        $original = EntityOriginalCache::get($entity->getEntityTypeId(), $entity->id());
        if ($original) {
          return $original;
        }
      }
    }
    return parent::dereference($next);
  }

}
