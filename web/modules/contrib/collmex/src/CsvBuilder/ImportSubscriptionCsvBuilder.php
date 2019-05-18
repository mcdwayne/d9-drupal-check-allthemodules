<?php

namespace Drupal\collmex\CsvBuilder;

use MarcusJaschen\Collmex\Type\Subscription;

class ImportSubscriptionCsvBuilder extends ImportCsvBuilderBase implements ImportCsvBuilderInterface {

  protected function makeCollmexObject(array $values) {
    return new Subscription($values);
  }

  public function getDefaultValues() {
    return ['client_id' => '1'];
  }

  public function getIdKeys() {
    return ['customer_id'];
  }

  public function getDeleteMarkValues() {
    return [];
  }

  public function getFields() {
    return [
      'customer_id'         => 'Customerid',
      'client_id'           => 'Client id',
      'valid_from'          => 'Valid from',
      'valid_to'            => 'Valid to',
      'product_id'          => 'Product id',
      'product_description' => 'Product description',
      'price'               => 'Price',
      'interval'            => 'Interval',
      'next_invoice'        => 'Next invoice',
    ];
  }

  protected function getFieldDefinitions() {
    return parent::getFieldDefinitions() + [
      'customer_id'         => 'i8',
      'client_id'           => 'i8',
      'valid_from'          => 'd8',
      'valid_to'            => 'd8',
      'product_id'          => 'c20',
      'product_description' => 'c1024',
      'price'               => 'm18',
      'interval'            => 'i8',
      'next_invoice'        => 'd8',
    ];
  }

}
