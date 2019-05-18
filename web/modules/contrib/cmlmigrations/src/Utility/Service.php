<?php

namespace Drupal\cmlmigrations\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Service.
 */
class Service extends ControllerBase {

  /**
   * Fill uuid.
   */
  public static function uuid1cFill() {
    $variations = self::query();
    $otvet = 'GO!';
    foreach ($variations as $key => $variation) {
      $pid = $variation->sku->value;
      $product_uuid = strstr("{$pid}#", "#", TRUE);
      $variation->product_uuid->setValue($product_uuid);
      $variation->save();
      $otvet .= "$key\n";
    }
    return $otvet;
  }

  /**
   * Clear uuid.
   */
  public static function uuid1cClear() {
    $variations = self::query('clear');
    $otvet = 'GO!';
    foreach ($variations as $key => $variation) {
      $variation->product_uuid->setValue(NULL);
      $variation->save();
      $otvet .= "$key\n";
    }
    return $otvet;
  }

  /**
   * Query.
   */
  public static function query($mode = 'fill') {
    $entities = [];
    $entity_type = 'commerce_product_variation';
    $storage = \Drupal::entityManager()->getStorage($entity_type);
    $query = \Drupal::entityQuery($entity_type)
      ->range(0, 1000);
    if ($mode == 'clear') {
      $query->condition('product_uuid', 'NULL', '<>');
    }
    else {
      $query->notExists('product_uuid');
    }
    $ids = $query->execute();
    if (!empty($ids)) {
      foreach ($storage->loadMultiple($ids) as $id => $entity) {
        $entities[$id] = $entity;
      }
    }
    return $entities;
  }

}
