# Xero Module

[![Build Status](https://travis-ci.org/mradcliffe/xero.svg?branch=8.x-1.x)](https://travis-ci.org/mradcliffe/xero)

This module provides classes to help interface with the Xero Account SaaS product. You will need to be familiar with the [Xero API](http://developer.xero.com).

The module provides a factory class which instantiates XeroClient, an extension of Guzzle Client. This allows you to make requests to the Xero API via Guzzle. As well, all of Xero types are mapped out as a TypedData replacing the old `xero_make` system, and the raw JSON or XML can be fed into Serializer to normalize and denormalize data.

## XeroBundle

Xero module now requires [xeroclient](https://github.com/mradcliffe/xeroclient) instead of PHP-Xero or XeroBundle. This requires downloading the dependency with Composer either with [Composer Manager](http://drupal.org/project/composer_manager) module or by [managing Drupal with Composer](https://www.drupal.org/node/2404989) itself. Do not attempt to enable the module without installing the dependencies first or the Symfony container will crash.

With Drupal package repository:

* `composer require drupal/xero`

## Using XeroQuery to fetch into TypedData

The `xero.query` service is a HTTP query builder built for Xero that is similar to the Database API.

```
  $query = \Drupal::service('xero.query');

  $contacts = $query
    ->setType('xero_contact')
    ->addCondition('FirstName', 'John')
    ->execute();

  foreach ($contacts as $contact) {
    $mail = $contact->get('Email')->getValue();
  }
```
Multiple conditions are supported, separated by addOperator().

```
  $contacts = $query
    ->setType('xero_contact')
    ->addCondition('FirstName', 'John')
    ->addOperator('AND')
    ->addCondition('LastName', 'Smith', '!=')
    ->execute();
```
Supported comparison operations:

- ==: Equal to the value (default)
- !=: Not equal to the value
- StartsWith: Starts with the value
- EndsWith: Ends with the value
- Contains: Contains the value
- guid: Equality for guid values (see Xero API)
- NULL: Is empty
- NOT NULL: Is not empty

Non-boolean comparison operations on optional Xero fields should be preceded by a null field guard:

```
  $contacts = $query
    ->setType('xero_contact')
    ->addCondition('EmailAddress', '', 'NOT NULL')
    ->addOperator('AND')
    ->addCondition('EmailAddress', 'drupal.org', 'EndsWith')
    ->execute();
```


## Using XeroClient to fetch into TypedData manually

It is advised to use dependency injection to retrieve the `xero.client` and `serializer` services. This example assumes that this is stored in an object variable called `client` and serializer is `serializer`.

```
  try {
    // Do Guzzle things.
    $options = array('query' => array('where' => 'Contact.FirstName = John'));
    $response = $this->client->get('Contacts', array(), $options);

    // Do Serializer things. The context array must have a key plugin_id with
    // the plugin id of the data type because Drupal.
    $context = array('plugin_id' => 'xero_contact');
    $contacts = $this->serializer->deserialize($response->getBody()->getContent(), 'Drupal\xero\Plugin\DataType\Contact', 'xml', $context);

    // Contacts is a list item and can be iterated through like an entity or
    // other typed data.
    foreach ($contacts as $contact) {
      $mail = $contact->get('Email')->getValue();
    }
  }
  catch (RequestException $e) {
    // Do Logger things.
  }
```

## Using TypedData to post to Xero

Previously the Xero Make system allowed to create associative arrays. This has been modified to use the TypedData API. Each Xero Type is implemented as a data type.

```
  $typedDataManager = \Drupal::typedDataManager();

  // Xero likes lists, so it's a good idea to create a the list item for an
  // equivalent xero data type using typed data manager.
  $definition = $typedDataManager->createListDataDefinition('xero_invoice');
  $invoices = $typedDataManager->create($definition, 'xero_invoice');

  foreach ($invoices as $invoice) {
    $invoice->setValue('ACCREC');
    // etc...
  }

  $query = \Drupal::service('xero.query');

  $response_data = $query
    ->setType('xero_invoice')
    ->setMethod('post')
    ->setData($invoices)
    ->execute();
```

## Caching Data

The Xero API for Drupal will keep a cache of objects if you use the `XeroQuery::getCache()` method. This is a simple way to grab all accounts, contacts, etc... frequently used in forms.

The cache will be invalidated immediately, which means that it will be cleared at the next Drupal cache clear.

```
  $query = \Drupal::service('xero.query');
  $contacts = $query->getCache('Contacts');
```

Note: at this time it is not possible to filter these queries.


## Form Helper

Xero API Module provides a form element helper service that will return form elements for a given Xero data type. This also works with any data type.

```
  $formBuilder = \Drupal::service('xero.form_builder');

  // Build an entire nested array for valid elements for an account type.
  $form['Account'] = $formBuilder->getElementFor('xero_account');

  // Build an autocomplete textfield for contacts.
  $definition = \Drupal::service('typed_data_manager')->createDataDefinition('xero_contact');
  $form['ContactID'] = $formBuilder->getElementForDefinition($definition, 'ContactID');
```

## Field API

Xero API provides the Xero Reference field type and associated widgets and formatters. This allows to store the Xero ID and Label associated with a given Xero data type, and is useful for tracking transactions or invoices associated with e-commerce payments or internal accounting data structures.

### Field Widgets

1. The textfield widget allows a user to specify the ID, Label, and Type with text fields.
2. The autocomplete widget allows a user to search Xero for records by the record label.
   * Note that there are daily API limits, and excessive searches may bump into this limit.


## Theming Typed Data

The main Xero data types have a view method that will return a render array. See the `templates` directory for details.

```
  $query = \Drupal::get('xero.query');

  $contacts = $query
    ->setType('xero_contact')
    ->execute();

  foreach ($contacts as $contact) {
    $render[] = $contact->view();
  }
```

## Xero Data Types

A xero type is a data type as defined by the [Xero Developer API](http://developer.xero.com/documentation/api/api-overview/). Data types current supported:

* Accounts
* Bank Transactions
* Bank Transfern
* Branding Themes
* Contacts
   * Addresses
   * Phones
   * Links
* Contact Groups
* Credit Notes
* Currencies
* Employees
* Expense Claims
* Invoices
   * Line items
* Invoice Reminders
* Items
* Journals
   * Journal Line Items
* Linked Transactions
* Organisation
* Payments
* Receipts
* Repeating Invoices
   * Schedules
* Tax Components
   * Tax Rates
* Tracking Categories
* Users
