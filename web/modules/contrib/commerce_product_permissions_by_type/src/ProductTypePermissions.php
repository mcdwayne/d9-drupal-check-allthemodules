<?php

namespace Drupal\commerce_product_permissions_by_type;

use Drupal\Core\StringTranslation\StringTranslationTrait;

class ProductTypePermissions {

  use StringTranslationTrait;


  // Defines permissions for controlling the ability to add products to
  // the cart on a per product type basis.

  function addToCartByProductTypePermissions() {
    $perms = [];

    /** @var \Drupal\Core\Entity\EntityStorageInterface $product_type_storage */
    $product_type_storage = \Drupal::entityTypeManager()->getStorage('commerce_product_type');

    foreach ($product_type_storage->loadMultiple() as $type) {
      /** @var \Drupal\commerce_product\Entity\ProductType $type */

      // Define a new permission to control the 'add to cart' button by product type.
      $perms['add ' . $type->id() . ' commerce_product to cart'] = [
        'title' => $this->t('@type_name: Add products to cart', [
          '@type_name' => $type->label(),
        ]),
      ];

    };

    return $perms;
  }



  // Defines permissions for controlling the ability to view products
  // on a per product type basis.
  //
  // Note: this needs commerce 2.0 (not rc2!) - see https://www.drupal.org/node/2909973

  function viewByProductTypePermissions() {
    $perms = [];

    /** @var \Drupal\Core\Entity\EntityStorageInterface $product_type_storage */
    $product_type_storage = \Drupal::entityTypeManager()->getStorage('commerce_product_type');

    foreach ($product_type_storage->loadMultiple() as $type) {
      /** @var \Drupal\commerce_product\Entity\ProductType $type */

      // Define a new permission to control viewing any products by product type.
      $perms['view ' . $type->id() . ' commerce_product'] = [
        'title' => $this->t('@type_name: View products', [
          '@type_name' => $type->label(),
        ]),
        'provider' => 'commerce_product',
      ];

    };

    return $perms;
  }


}
