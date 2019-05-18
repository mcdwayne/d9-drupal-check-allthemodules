<?php

/**
 * @file
 * Contains \Drupal\packaging\Plugin\Strategy\PackageByKey.
 *
 * Packaging strategy. Puts products with identical keys into same package.
 */

namespace Drupal\packaging\Plugin\Strategy;

use Drupal\packaging\Strategy;
use Drupal\packaging\Product;
use Drupal\packaging\Package;
use Drupal\packaging\Context;


/**
 * Puts products with identical keys into same package.
 *
 * The "By key" strategy uses certain keys set for each product to determine
 * how to package an order. Product keys are simply properties of the
 * Product object which have been designated as keys by invoking
 * Context::designateKeys(array $keys). You may pass an array of
 * property names to that method to identify which properties are keys.
 *
 * Once the keys have been designated, this strategy will look for products
 * which have a common set of keys and put those products in one package.
 *
 * A typical use case would be for products that are drop-shipped from a
 * location other than your store. In this use case, products may have
 * different origin addresses, but may be destined for the same delivery
 * address. This physically requires more than one package. Designating both
 * origin and destination addresses as keys ensures that there is one package
 * for each combination in the set {origin, destination}. For example, with
 * two origin addresses and one destination address you will get two boxes.
 * for two origin addresses and two destination addresses you may get up to
 * four boxes.
 *
 * Another use case is to use a taxonomy term as a key. This strategy will
 * then package all products with the same term in the same package. This is
 * useful when you have perishable products, for instance, that may need to
 * be shipped together via an Express method, or with special handling.
 *
 * @Strategy(
 *   id = "packaging_bykey",
 *   admin_label = @Translation("By key", context = "Packaging")
 * )
 */
class PackageByKey implements Strategy {

  /**
   * Implements Strategy::getDescription().
   */
  public function getDescription() {
    return t("The 'By key' strategy uses certain keys set for each product to determine how to package an order. Product keys are simply properties of the Product object which have been designated as keys by invoking Context::designateKeys(array \$keys). You may pass an array of property names to that method to identify which properties are keys.

Once the keys have been designated, this strategy will look for products which have a common set of keys and put those products in one package.");
  }

  /**
   * Implements Strategy::packageProducts().
   */
  public function packageProducts(Context $context, array $products) {

    // Weight units are set on a per-product basis, so convert all products
    // to default weight units before performing any calculations.
    foreach ($products as $product) {
      $product->setWeight($product->getWeight() * packaging_weight_conversion($product->getWeightUnits(), $context->getDefaultWeightUnits()));
      $product->setWeightUnits($context->getDefaultWeightUnits());
    }

    $hashtable = array();
    $keynames = Context::getKeys();

//debug($products);
    // Loop over products to build hashtable. Each entry in the hashtable
    // represents a package. Products with the same hash value will be in
    // the same package.
    foreach ($products as $index => $product) {
      // Compute a hash value using all the designated keys.
      // Products with equal key values will have equal hash values.
      $hash = '';
      foreach ($keynames as $key) {
        if (!property_exists($product, $key)) {
          // This product doesn't have one of the required keys, so we quit.
          return array();
        }
        $hash .= $product->$key;
      }
      // Save index in the hashtable.
      $hashtable[$hash][] = $index;
    }

//debug($hashtable);

    // Loop over hashtable entries to build Package objects.
    // Each hashtable entry corresponds to one package.
    $packages = array();
    foreach ($hashtable as $table_entry) {
      // Create package object.
      $package  = new Package();
      $package->setWeightUnits($context->getDefaultWeightUnits());

      // Loop over product indexes stored in the hashtable.
      foreach ($table_entry as $index) {
        // Add the product to this package.
        $package->addProduct($products[$index]);
      }

      // Copy designated product keys onto the package object so the package
      // can be queried by key later.
      foreach ($keynames as $key) {
        // Values for the designated product keys are taken from first
        // product in the package, but all products in the package will have
        // the same values at this point.
        $package->$key = $products[reset($table_entry)]->$key;
      }

      // Markup weight of package.
      $package->setShipWeight(packaging_weight_markup($package->getWeight()));
//debug($package);

      // Save the package to the array.
      $packages[] = $package;
    }

    return $packages;
  }

}
