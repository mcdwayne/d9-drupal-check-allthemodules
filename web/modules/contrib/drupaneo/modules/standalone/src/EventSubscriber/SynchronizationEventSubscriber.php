<?php

namespace Drupal\drupaneo_standalone\EventSubscriber;

use Drupal\drupaneo\Event\SynchronizationEvent;
use Drupal\drupaneo_standalone\Entity\Product;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SynchronizationEventSubscriber implements EventSubscriberInterface {

    /**
     * {@inheritdoc}
     */
    static function getSubscribedEvents() {
        return array(SynchronizationEvent::SYNCHRONIZED => array('onSynchronized', 100));
    }

    /**
     * @param SynchronizationEvent $event
     */
    public function onSynchronized(SynchronizationEvent $event) {
        $product = Product::create();
        $product->set('identifier', $event->product->identifier);
        $product->set('family', $event->product->family);
        $product->save();
    }
}