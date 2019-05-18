<?php

namespace Drupal\commerce_pagseguro_transp\EventSubscriber;

use CommerceGuys\Addressing\AddressFormat\AdministrativeAreaType;
use Drupal\address\Event\AddressEvents;
use Drupal\address\Event\AddressFormatEvent;
use Drupal\address\Event\SubdivisionsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AddressEventSubscriber implements EventSubscriberInterface {

  public static function getSubscribedEvents() {
    $events[AddressEvents::ADDRESS_FORMAT][] = array('onGetDefinition', 0);

    return $events;
  }

  public function onGetDefinition($event) {
    $definition = $event->getDefinition();

    $definition['required_fields'][] = 'dependentLocality';
    $definition['available_countries'] = ['BR'];
    $definition['format'] = "%organization\n%givenName %familyName\n%addressLine1\n%addressLine2\n%dependentLocality \n%administrativeArea %locality %postalCode";
    $event->setDefinition($definition);
  }
}
