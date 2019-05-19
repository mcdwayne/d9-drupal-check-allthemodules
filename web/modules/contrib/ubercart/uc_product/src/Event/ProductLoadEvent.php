<?php

namespace Drupal\uc_product\Event;

use Drupal\node\NodeInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event that is fired when a product is being loaded.
 */
class ProductLoadEvent extends Event {

  const EVENT_NAME = 'uc_product_load';

  /**
   * The product.
   *
   * @var \Drupal\node\NodeInterface
   */
  public $product;

  /**
   * Constructs the object.
   *
   * @param \Drupal\node\NodeInterface $product
   *   The product object.
   */
  public function __construct(NodeInterface $product) {
    $this->product = $product;
  }

}
