<?php

namespace Drupal\cmlmigrations\Hook;

/**
 * @file
 * Contains \Drupal\cmlmigrations\Hook\CommerceProductVariationInsert.
 */

/**
 * Hook insert.
 */
class CommerceProductVariationInsert {

  /**
   * Hook.
   */
  public static function hook($variation) {
    // 1C UT load orrer fixer.
    $entity_type = 'commerce_product';
    $puuid = $variation->product_uuid->value;
    if ($puuid) {
      $product =  \Drupal::entityManager()->loadEntityByUuid($entity_type, $puuid);
      if ($product) {
        $variation->product_id->setValue($product);
        $variation->save();
      }
    }
  }

}
