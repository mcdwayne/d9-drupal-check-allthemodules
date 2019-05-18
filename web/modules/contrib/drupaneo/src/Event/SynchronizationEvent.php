<?php

namespace Drupal\drupaneo\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Event that is fired when a product is synchronized.
 */

class SynchronizationEvent extends Event {

    const SYNCHRONIZED = 'drupaneo.synchronized';

    /**
     * The product data to synchronize.
     * @var \stdClass
     */
    public $product;

    /**
     * Constructs the even.
     *
     * @param \stdClass $product
     * The product data to synchronize.
     */
    public function __construct(\stdClass $product) {
        $this->product = $product;
    }
}
