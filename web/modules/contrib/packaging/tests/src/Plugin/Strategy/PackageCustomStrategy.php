<?php

/**
 * @file
 * Contains \Drupal\packaging_test\Plugin\Strategy\PackageCustomStrategy.
 *
 * Test of custom Packaging strategy. Always creates 23 boxes.
 */

namespace Drupal\packaging_test\Plugin\Strategy;

use Drupal\packaging\Strategy;
use Drupal\packaging\Product;
use Drupal\packaging\Package;
use Drupal\packaging\Context;


/**
 * Always creates 23 boxes, regardless of number of products in order.
 *
 * This is a clone of \Drupal\packaging\Plugin\Strategy\PackageAverageWeight,
 * with the number of packages hardwired to 23.
 *
 * @Strategy(
 *   id = "packaging_custom_strategy",
 *   admin_label = @Translation("Creates 23 packages", context = "Packaging")
 * )
 */
class PackageCustomStrategy implements Strategy {

  /**
   * Implements Strategy::getDescription().
   */
  public function getDescription() {
    return t("This strategy always creates 23 boxes, regardless of number of products in order. Its only use is to provide a custom strategy for automated testing of the Packaging module.");
  }

  /**
   * Implements Strategy::packageProducts().
   */
  public function packageProducts(Context $context, array $products) {
    // Creates twenty-three packages, independent of number of products.
    $num_packages = 23;

    // Create product aggregate for averaging.
    $product_aggregate = new Product();
    foreach ($products as $product) {
      // Get item weight. Weight units are set on a per-product basis, so we
      // convert as necessary in order to perform all calculations in the store
      // weight units.
      $product_aggregate->setQuantity($product_aggregate->getQuantity() + $product->getQuantity());
      $product_aggregate->setPrice($product_aggregate->getPrice() + $product->getPrice());
      $product_aggregate->setWeight($product_aggregate->getWeight() + $product->getQuantity() * $product->getWeight() * packaging_weight_conversion($product->getWeightUnits(), $context->getDefaultWeightUnits()));
    }

    $average_quantity = $product_aggregate->getQuantity() / $num_packages;
    $average_price = $product_aggregate->getPrice() / $num_packages;
    $average_weight = $product_aggregate->getWeight() / $num_packages;

    $packages = array();
    for ($id = 0; $id < $num_packages; $id++) {
      // Create package.
      $package = new Package();

      // Set package values to the average values.
      $package->setQuantity($average_quantity);
      $package->setPrice($average_price);
      $package->setWeight($average_weight);
      $package->setWeightUnits($context->getDefaultWeightUnits());

      // Markup weight of package.
      $package->setShipWeight(packaging_weight_markup($package->getWeight()));

      // Save the package to the array.
      $packages[] = $package;
    }

    return $packages;
  }

}
