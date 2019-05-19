# SuperFaktura
This module integrates Superfaktura API to Drupal Commerce,
so you can issue invoices via this service.

- Introduction
- Requirements
- Install
- Configuration
- Authors and maintainers
- Sponsor

## Introduction

SuperFaktura is an easy to use online app, that allows you to create
great looking online invoices, proformas, price estimates, orders,
delivery and credit notes. You can invoice in foreign currencies and languages.
Accept payments online, organize your stock management,
send electronic invoices online, or in paper form via hybrid post.

## Requirements

- you need SuperFaktura API (SFAPI)
https://github.com/superfaktura/apiclient (if you install this Drupal module via
composer - SFAPI is installed as dependency)
- in order to support tax on shipping apply patch to Drupal Commerce
https://www.drupal.org/project/commerce_shipping/issues/2874158

## Install

- with composer

    ```
     composer require drupal/superfaktura
    ```

- with drush

    ```
     drush en superfaktura
    ```

## Configuration

After installation you need to set your SFAPI email and key (from your
SuperFaktura account) and other invoice details on page

```
/admin/commerce/config/superfaktura
```

Next step is to create own module with subscriber to call SuperFaktura service.
In example below SuperFaktura service is called when Order is placed.

```
<?php

namespace Drupal\your_module\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\state_machine\Event\WorkflowTransitionEvent;

/**
 * Superfaktura event subscriber.
 */
class SuperfakturaSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      'commerce_order.place.pre_transition' => ['createInvoice', -200],
    ];
  }

  /**
   * Create Invoice in Superfaktura from created order.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   Transition Event.
   */
  public function createInvoice(WorkflowTransitionEvent $event) {
    /** @var \Drupal\commerce_order\Entity\Order $order */
    $order = $event->getEntity();
    $invoice = \Drupal::service('superfaktura.invoice_service');
    $invoice->createInvoice($order);
  }

}
```

## Authors and maintainers

- Miro Michalicka (D8 branch), https://www.drupal.org/u/mirom
- Peter Hrabovcin (D8 branch), https://www.drupal.org/u/pedro_sv
- Peter Lachky (D7 branch), https://www.drupal.org/u/petiar

## Sponsor

The development of this module is sponsored by
[SuperFaktura](https://www.superfaktura.sk/ "SuperFaktura")
