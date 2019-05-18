<?php

namespace Drupal\collmex\CsvBuilder;

use MarcusJaschen\Collmex\Type\MemberGet;

class QueryMembersCsvBuilder extends QueryCsvBuilderBase implements QueryCsvBuilderInterface {

  /**
   * @inheritDoc
   */
  protected function makeCollmexObject(array $values) {
    return new MemberGet($values);
  }

  /**
   * @inheritDoc
   */
  public function getIdKeys() {
    return ['customer_id'];
  }

  /**
   * @inheritDoc
   */
  public function getDefaultValues() {
    return [
      'client_id' =>'1',
      'system_name' => \Drupal::config('collmex.settings')->get('system_name'),
    ];
  }

  /**
   * @inheritDoc
   */
  public function getFields() {
    return [
      'customer_id'      => 'Customer ID',
      'client_id'        => 'Client ID',
      'query'            => 'Query',
      'zipcode'          => 'Zipcode',
      'address_group_id' => 'Address group ID',
      'exited_too'       => 'Exited too',
      'only_changed'     => 'Only changed',
      'system_name'      => 'System name',
    ];
  }

  /**
   * @inheritDoc
   */
  protected function getFieldDefinitions() {
    return parent::getFieldDefinitions() + [
        'customer_id'      => 'i8',
        'client_id'        => 'i8',
        'query'            => 'c80',
        'zipcode'          => 'c8',
        'address_group_id' => 'i8',
        'exited_too'       => 'i8',
        'only_changed'     => 'i8',
        'system_name'      => 'c20',
      ];
  }

}
