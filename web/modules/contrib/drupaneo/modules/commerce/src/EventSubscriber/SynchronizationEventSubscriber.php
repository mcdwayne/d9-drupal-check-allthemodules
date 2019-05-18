<?php

namespace Drupal\drupaneo_commerce\EventSubscriber;

use Drupal\drupaneo\Event\SynchronizationEvent;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductType;
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

        $title = $event->product->identifier;
        $type = 'default';
        $body = '';

        // Is this a variation ?

        if (isset($event->product->parent)) {
            // $product = Product::load($event->product->parent);
        }

        // Load or create product type if akeneo product has a family

        if (isset($event->product->family)) {
            $type = $event->product->family;
            $productType = ProductType::load($type);
            if (is_null($productType)) {
                $productType = ProductType::create(array(
                    'id' => $type,
                    'label' => ucwords(strtolower(str_replace('_', ' ' , $type))),
                ));
                $productType->save();

                commerce_product_add_variations_field($productType);
                commerce_product_add_stores_field($productType);
                commerce_product_add_body_field($productType);
            }
        }

        // Set product attributes

        if (isset($event->product->values)) {
            foreach($event->product->values as $key => $value) {
                if ($key === 'name' && count($value) > 0) {
                    $title = $value[0]->data;
                }
                else if ($key === 'description' && count($value) > 0) {
                    $body = $value[0]->data;
                }
            }
        }

        $product = Product::create(array(
            'type' => $type,
            'title' => $title,
            'body' => $body,
        ));
        $product->save();
    }
}
