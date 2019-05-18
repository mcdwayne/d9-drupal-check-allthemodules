<?php

namespace Drupal\collmex\CsvBuilder;

use MarcusJaschen\Collmex\Type\Member;

class ImportMemberCsvBuilder extends ImportCsvBuilderBase implements ImportCsvBuilderInterface {

  protected function makeCollmexObject(array $values) {
    return new Member($values);
  }

  public  function getIdKeys() {
    return ['customer_id'];
  }

  public  function getDefaultValues() {
    return [];
  }

  public  function getDeleteMarkValues() {
    // Pass "delete if not used". For legal reasons members usually are not
    // deleted in bookkeeping, only their membership fee subscriptions are deleted.
    return ['delete' => 2];
  }

  public function getFields() {
    return [
      'customer_id'                 => 'Customer ID',       //
      'salutation'                  => 'Salutation',       //
      'title'                       => 'Title',       //
      'forename'                    => 'Forename',       // 5
      'lastname'                    => 'Lastname',       //
      'firm'                        => 'Firm',       //
      'department'                  => 'Department',       //
      'street'                      => 'Street',       //
      'zipcode'                     => 'Zip code',       // 10
      'city'                        => 'City',       //
      'delete'                      => 'Delete',       //
      'url'                         => 'URL',       //
      'country'                     => 'Country',       //
      'phone'                       => 'Phone',       // 15
      'fax'                         => 'Fax',       //
      'email'                       => 'Email',       //
      'bank_account'                => 'Bank account',       //
      'bank_code'                   => 'Bank code',       //
      'iban'                        => 'IBAN',       // 20
      'bic'                         => 'BIC',       //
      'bank_name'                   => 'Bank name',       //
      'mandate_reference'           => 'Mandate reference',       //
      'mandate_reference_sign_date' => 'Mandate reference sign date',       //
      'birthday'                    => 'Birthday',       // 25
      'entrance_date'               => 'Entrance date',       //
      'exit_date'                   => 'Exit date',       //
      'annotation'                  => 'Annotation',       //
      'phone2'                      => 'Phone2',       //
      'skype'                       => 'Skype',       // 30
      'bankaccount_owner'           => 'Bank account owner',       //
      'printout_medium'             => 'Printout medium',       //
      'address_group'               => 'Address group',       //
      'payment_agreement'           => 'Payment agreement',       //
      'payment_via'                 => 'Payment via',       // 35
      'printout_language'           => 'Printout language',       //
      'cost_center'                 => 'Cost center',       //
    ];
  }

  protected function getFieldDefinitions() {
    return parent::getFieldDefinitions() + [
      'customer_id'                 => 'i8',       //
      'salutation'                  => 'c10',       //
      'title'                       => 'c10',       //
      'forename'                    => 'c40',       // 5
      'lastname'                    => 'c40',       //
      'firm'                        => 'c80',       //
      'department'                  => 'c40',       //
      'street'                      => 'c40',       //
      'zipcode'                     => 'c10',       // 10
      'city'                        => 'c20',       //
      'delete'                      => 'c10',       //
      'url'                         => 'c80',       //
      'country'                     => 'c2',       //
      'phone'                       => 'c40',       // 15
      'fax'                         => 'c40',       //
      'email'                       => 'c50',       //
      'bank_account'                => 'c20',       //
      'bank_code'                   => 'c20',       //
      'iban'                        => 'c40',       // 20
      'bic'                         => 'c20',       //
      'bank_name'                   => 'c40',       //
      'mandate_reference'           => 'c35',       //
      'mandate_reference_sign_date' => 'd8',       //
      'birthday'                    => 'd8',       // 25
      'entrance_date'               => 'd8',       //
      'exit_date'                   => 'd8',       //
      'annotation'                  => 'c1024',       //
      'phone2'                      => 'c20',       //
      'skype'                       => 'c50',       // 30
      'bankaccount_owner'           => 'c40',       //
      'printout_medium'             => 'i8',       //
      'address_group'               => 'i8',       //
      'payment_agreement'           => 'i8',       //
      'payment_via'                 => 'i8',       // 35
      'printout_language'           => 'i8',       //
      'cost_center'                 => 'c10',       //
    ];
  }

}
