<?php

namespace Drupal\cmlmigrations\Hook;

/**
 * @file
 * Contains \Drupal\cmlmigrations\Hook\CommerceProductInsert.
 */

use Drupal\cmlmigrations\Utility\FindVariation;

/**
 * Hook insert.
 */
class CommerceProductInsert {

  /**
   * Hook.
   */
  public static function hook($product) {
    // Fix variation on insert.
    $id1c = $product->uuid->value;
    $variations = FindVariation::getBy1cUuid($id1c);
    if (isset($variations[$id1c])) {
      $product->variations->setValue($variations[$id1c]);
    }
    $product->save();
  }

}
