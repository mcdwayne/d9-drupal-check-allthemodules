<?php

/**
 * @file
 * Contains \Drupal\packaging\Strategy.
 */

namespace Drupal\packaging;


/**
 * Abstract class for packaging strategies.
 *
 * Declares a packageProducts() function which much be implemented by
 * subclasses wishing to provide their own packaging strategy. Defines
 * some utility routines.
 */
interface Strategy {

  /**
   * Abstract function for packaging strategies.
   *
   * Subclasses must implement this function to provide a strategy.
   *
   * @param Context $context
   *   An object holding extrinsic state information for a strategy instance.
   * @param $products
   *   An array of nodes of type Product.
   *
   * @return
   *   An array of Package objects, each containing one or more of the products.
   */
  public function packageProducts(Context $context, array $products);

  /**
   * Describes the packaging algorithm implemented by subclasses.
   *
   * @return
   *   String containing a description of the packaging strategy.
   */
  public function getDescription();

}
