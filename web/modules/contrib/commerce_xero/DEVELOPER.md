# Development notes for Drupal 8

* [META: Development Plan for Commerce Xero](https://www.drupal.org/node/1949288)

## Strategy pattern

There are different strategies that commerce site owners would want to use to integrate their commerce orders and payment transactions with their Xero bank statements or invoices.

Commerce Xero should provide default strategies and a way to create new strategies.

A strategy is related to

* a Xero data type e.g. Invoice, Bank Transaction, Invoice-Payment
* an unique Commerce payment gateway e.g. Auth.net, Stripe, Manual payment, PayPal, etc...
* a Xero revenue account
* a Xero bank account
* or it is possible that a developer would want to come up with multiple strategies using one payment method and different revenue/bank accounts and create code to choose the strategy.

A strategy contains a configurable, ordered set of plugins to execute by Commerce event to

* Reconcile tax calculations that vary between Commerce and Xero :*(
   * Line amount type makes this difficult, and most likely the user should make Xero the system of record.
* Reconcile Contact on the order
* Reconcile Tracking Categories for each order item
* Store Xero reference object on the order item so that it can be updated and referenced
   * Order item or order type?

## Strategy resolver

Commerce Xero should provide a strategy resolver, which has a fallback based on the current resolver in Drupal 7 (payment method) and allows to chain other custom resolvers first.

A Strategy resolver needs to

* return the Strategy entity to use to process the order.

## Processor plugin

A Processor plugin refers to plugins that are run on Commerce event and added to a strategy entity.

A processor has

* a process method that does what it needs to
   * Perhaps borrow from Serializer component and use a $context parameter to pass in contextual data like the Typed Data data to process?
* a base plugin class that injects xero.query service.
* a plugin manager that instantiates plugins for a strategy.


```graphviz
digraph G {
  labelloc=t
  rankdir=LR

  "Commerce Event" -> Resolver -> Strategy -> "Event Plugins" -> Plugin -> Process;
}
```

### Queue

Similar pattern to 7.x-1.x, but could look at batching for API limits, and whenever a queue item is to be inserted, look at adding it to an unclaimed item.

## TODO

* Reimplement processor invoke via action.
* Reimplement queue process plugin for a strategy based on various data.
* Implement a chain resolver to replace the basic resolver.
   * See if the following issues are resolved in state_machine:
      * [2931447: WorkflowTransitionEvent should know transition ID](https://www.drupal.org/project/state_machine/issues/2931447)
      * [2894810: Fire events named after the destination state rather than the transition name](https://www.drupal.org/project/state_machine/issues/2894810)
      * [2832415: Fire generic events when transition are applied](https://www.drupal.org/project/state_machine/issues/2832415)
* Reimplement bank transaction suppport via processor plugin.
* Reimplement invoice support via processor plugin.
   * Split into Invoice and Payment processor plugins.
* Implement Order processors.
* Implement processor plugins for TrackingCategories.
* Implement processor plugin for Tax.
* Implement processor plugin for Contact (search/update existing contact).
* Implement batching as a queue process plugin.

## Development Environment

There are plenty of development environment setups based on your preferences from virtual machines to native solutions.

### Getting code

Clone core and module dependencies in case you need to work on Drupal core or any of this module's dependencies.

    git clone --branch 8.6.x https://git.drupal.org/project/drupal.git
    cd drupal
    git clone --branch 8.x-2.x https://git.drupal.org/project/commerce.git modules/commerce
    git clone --branch 8.x-1.x https://git.drupal.org/project/address.git modules/address
    git clone --branch 8.x-1.x https://git.drupal.org/project/entity.git modules/entity
    git clone --branch 8.x-1.x https://git.drupal.org/project/entity_reference_revisions.git modules/entity_reference_revisions
    git clone --branch 8.x-1.x https://git.drupal.org/project/inline_entity_form.git modules/inline_entity_form
    git clone --branch 8.x-1.x https://git.drupal.org/project/profile.git modules/profile
    git clone --branch 8.x-1.x https://git.drupal.org/project/state_machine.git modules/state_machine
    git clone --branch 8.x-1.x https://git.drupal.org/project/xero.git modules/xero
	git clone --branch 8.x-1.x https://git.drupal.org/project/commerce_xero.git modules/commerce_xero
    composer require commerceguys/addressing:^1.0 commerceguys/intl:~0.7 mradcliffe/xeroclient

### Xero API

1. Create a [Xero account](https://developer.xero.com/documentation/getting-started/development-accounts) using a Demo company.
2. Create a [Private application](https://developer.xero.com/documentation/auth-and-limits/private-applications) tied to the Demo company.
3. Configure [Xero API module](https://drupal.org/project/xero) on your development site.

## Tests

Try to create pure Unit tests when possible rather than Functional-Javascript or Kernel tests.

Each new plugin, entity type, strategy resolver, etc... should have an unit test. Unit tests for UI elements isn't strictly necessary for a patch.

