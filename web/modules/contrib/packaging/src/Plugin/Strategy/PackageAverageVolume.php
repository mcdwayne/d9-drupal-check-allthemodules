<?php

/**
 * @file
 * Contains \Drupal\packaging\Plugin\Strategy\PackageAverageVolume.
 *
 * Packaging strategy. Creates identical packages based on product averages.
 */

namespace Drupal\packaging\Plugin\Strategy;

use Drupal\packaging\Strategy;
use Drupal\packaging\Product;
use Drupal\packaging\Package;
use Drupal\packaging\Context;


/**
 * Puts all products into packages, subject only to package maximum volume.
 *
 * The "Average volume" strategy computes the number of packages needed by
 * dividing the total volume of all products by the maximum allowed package
 * volume. The resulting packages are assigned identical weights, prices,
 * etc.  so as to simulate uniform distribution of products amongst the
 * packages.
 *
 * @Strategy(
 *   id = "packaging_averagevolume",
 *   admin_label = @Translation("Average volume", context = "Packaging")
 * )
 */
class PackageAverageVolume implements Strategy {

  /**
   * Implements Strategy::getDescription().
   */
  public function getDescription() {
    return t("The 'Average volume' strategy computes the number of packages needed by dividing the total volume of all products by the maximum allowed package volume. The resulting packages are assigned identical weights, prices, etc. so as to simulate uniform distribution of products amongst the packages.");
  }

  /**
   * Implements Strategy::packageProducts().
   */
  public function packageProducts(Context $context, array $products) {

    // Create product aggregate for averaging.
    $product_aggregate = new Product();
    $product_aggregate->setWeightUnits($context->getDefaultWeightUnits());
    $product_aggregate->setLengthUnits($context->getDefaultLengthUnits());

    foreach ($products as $product) {
      // Get item dimensions. Length units are set on a per-product basis, so
      // we convert as necessary in order to perform all calculations in the
      // default length units.
      $dimensions = $product->getDimensions();
      foreach ($dimensions as &$dimension) {
        $dimension *= packaging_length_conversion($product->getLengthUnits(), $context->getDefaultLengthUnits());
      }
      // Do this before we set dimensions!!
      // setDimensions() should (but currently doesn't) rescale all
      // dimensions automatically.
      $product->setLengthUnits($context->getDefaultLengthUnits());
      $product->setDimensions($dimensions);

      // Accumulate aggregate values.
      $product_aggregate->setQuantity($product_aggregate->getQuantity() + $product->getQuantity());
      $product_aggregate->setPrice($product_aggregate->getPrice() + $product->getPrice());
      $product_aggregate->setWeight($product_aggregate->getWeight() + $product->getQuantity() * $product->getWeight() * packaging_weight_conversion($product->getWeightUnits(), $context->getDefaultWeightUnits()));
      $product_aggregate->setVolume($product_aggregate->getVolume() + $product->getQuantity() * $product->getVolume());
    }

    // Calculate the number of packages we will need.
    if ($context->getMaximumPackageVolume() == Context::UNLIMITED_PACKAGE_VOLUME) {
      $num_packages = 1;
    }
    else {
      $num_packages = ceil($product_aggregate->getVolume() / $context->getMaximumPackageVolume());
    }

    $average_quantity = $product_aggregate->getQuantity() / $num_packages;
    $average_price = $product_aggregate->getPrice() / $num_packages;
    $average_weight = $product_aggregate->getWeight() / $num_packages;
    $average_volume = $product_aggregate->getVolume() / $num_packages;

    $packages = array();
    for ($i = 0; $i < $num_packages; $i++) {
      // Create package.
      $package = new Package();

      // Should use "Worst fit" strategy here to allocate products
      // to packages as evenly as possible.

      // Set package values to the average values.
      $package->setQuantity($average_quantity);
      $package->setPrice($average_price);
      $package->setWeight($average_weight);
      $package->setWeightUnits($context->getDefaultWeightUnits());
      $package->setVolume($average_volume);
      $package->setLengthUnits($context->getDefaultLengthUnits());

      // Markup weight of package.
      $package->setShipWeight(packaging_weight_markup($package->getWeight()));

      // Save the package to the array.
      $packages[] = $package;
    }

    return $packages;
  }

}
