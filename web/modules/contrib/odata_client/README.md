# OData client

[![Brainsum](https://www.drupal.org/files/styles/grid-3/public/brainsum-logo.jpg?itok=BdL0fZV3)](https://brainsum.com)

## Introduction
OData client implements configuration entity for OData servers,
IO functions to OData collections and Drupal format OdataQuery object.

## Requirements
The module requires the following library:
  - saintsystems/odata-client: ^0.2.4

## Installation
Install as you would normally install a contributed Drupal module. Visit:
https://www.drupal.org/documentation/install/modules-themes/modules-8
for further information.

## Configuration
  - Go to /admin/structure/odata_server and create new server configuration

## Usage
After made a server configuration name default able to connect to server:
```php
$odata_client = \Drupal::service('odata_client.io');
$odata_client->connect('default');
```

#### Change collection:
In configuration can define the default collection but may to change it:
```php
$odata_client->setCollection('People');
```

#### Find an element by key:
Return the data of element key 'russellwhyte':
```php
$result = $odata_client->find('russellwhyte');
```

#### Count elements in collection:
Return elements count the collection:
```php
$result = $odata_client->count();
```

#### Create new element:
Create new element in collection:
```php
$data = array (
  'UserName' => 'teresa',
  'FirstName' => 'Teresa',
  'LastName' => 'Gilbert',
  'Gender' => 'Female',
  'Emails' => 
  array (
    0 => 'teresa@example.com',
    1 => 'teresa@contoso.com',
  ),
  'AddressInfo' => 
  array (
    0 => 
    array (
      'Address' => '1 Suffolk Ln.',
      'City' => 
      array (
        'CountryRegion' => 'United States',
        'Name' => 'Boise',
        'Region' => 'ID',
      ),
    ),
  ),
);
$result = $odata_client->post($data);
```

#### Get data from collection:
Return max 4 elements FirstName and LastName columns where FirstName is Teresa,
sort by LastName descending:
```php
$query = \Drupal::service('odata_client.query');
$query->connect('default')
    ->fields(['FirstName','LastName'])
    ->condition('FirstName', 'Teresa')
    ->orderBy('LastName', 'desc');
    ->range(0, 4);
$result = $query->execute();
```

# Maintainers
Current maintainer:
 - Jozsef Dudas (dj199) - https://www.drupal.org/user/387119

#Supporting organizations: 
Brainsum Kft. sponsored
