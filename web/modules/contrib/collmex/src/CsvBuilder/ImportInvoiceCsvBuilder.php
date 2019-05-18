<?php

namespace Drupal\collmex\CsvBuilder;

use MarcusJaschen\Collmex\Type\Invoice;

class ImportInvoiceCsvBuilder extends ImportCsvBuilderBase implements ImportCsvBuilderInterface {

  protected function makeCollmexObject(array $values) {
    return new Invoice($values);
  }

  public function getDefaultValues() {
    return ['client_id' => '1'];
  }

  public function getIdKeys() {
    return ['invoice_id'];
  }

  public function getDeleteMarkValues() {
    return ['deleted' => 1];
  }

  public function getFields() {
    return [
      'invoice_id'                     => 'Invoice ID',
      'position'                       => 'Position',
      'invoice_type'                   => 'Invoice type',
      'client_id'                      => 'Client ID', // 5
      'order_id'                       => 'Order ID',
      'customer_id'                    => 'Customer ID',
      'customer_salutation'            => 'Customer salutation',
      'customer_title'                 => 'Customer title',
      'customer_forename'              => 'Customer forename', // 10
      'customer_lastname'              => 'Customer lastname',
      'customer_firm'                  => 'Customer firm',
      'customer_department'            => 'Customer department',
      'customer_street'                => 'Customer street',
      'customer_zipcode'               => 'Customer zipcode', // 15
      'customer_city'                  => 'Customer city',
      'customer_country'               => 'Customer country',
      'customer_phone'                 => 'Customer phone',
      'customer_phone_2'               => 'Customer phone 2',
      'customer_fax'                   => 'Customer fax', // 20
      'customer_email'                 => 'Customer email',
      'customer_bank_account'          => 'Customer bank account',
      'customer_bank_code'             => 'Customer bank code',
      'customer_bank_account_owner'    => 'Customer bank account owner',
      'customer_bank_iban'             => 'Customer bank iban', // 25
      'customer_bank_bic'              => 'Customer bank bic',
      'customer_bank_name'             => 'Customer bank name',
      'customer_vat_id'                => 'Customer vat ID',
      'reserved'                       => 'Reserved',
      'invoice_date'                   => 'Invoice date', // 30
      'price_date'                     => 'Price date',
      'terms_of_payment'               => 'Terms of payment',
      'currency'                       => 'Currency',
      'price_group'                    => 'Price group',
      'discount_id'                    => 'Discount ID', // 35
      'discount_final'                 => 'Discount final',
      'discount_reason'                => 'Discount reason',
      'invoice_text'                   => 'Invoice text',
      'final_text'                     => 'Final text',
      'annotation'                     => 'Annotation', // 40
      'deleted'                        => 'Deleted',
      'language'                       => 'Language',
      'employee_id'                    => 'Employee ID',
      'agent_id'                       => 'Agent ID',
      'system_name'                    => 'System name', // 45
      'status'                         => 'Status',
      'discount_final_2'               => 'Discount final 2',
      'discount_final_2_reason'        => 'Discount final 2 reason',
      'shipping_id'                    => 'Shipping ID',
      'shipping_costs'                 => 'Shipping costs', // 50
      'cod_costs'                      => 'COD costs',
      'time_of_delivery'               => 'Time of delivery',
      'delivery_conditions'            => 'Delivery conditions',
      'delivery_conditions_additional' => 'Delivery conditions additional',
      'delivery_salutation'            => 'Delivery salutation', // 55
      'delivery_title'                 => 'Delivery title',
      'delivery_forename'              => 'Delivery forename',
      'delivery_lastname'              => 'Delivery lastname',
      'delivery_firm'                  => 'Delivery firm',
      'delivery_department'            => 'Delivery department', // 60
      'delivery_street'                => 'Delivery street',
      'delivery_zipcode'               => 'Delivery zipcode',
      'delivery_city'                  => 'Delivery city',
      'delivery_country'               => 'Delivery country',
      'delivery_phone'                 => 'Delivery phone', // 65
      'delivery_phone_2'               => 'Delivery phone 2',
      'delivery_fax'                   => 'Delivery fax',
      'delivery_email'                 => 'Delivery email',
      'position_type'                  => 'Position type',
      'product_id'                     => 'Product ID', // 70
      'product_description'            => 'Product description',
      'quantity_unit'                  => 'Quantity unit',
      'quantity'                       => 'Quantity',
      'price'                          => 'Price',
      'price_quantity'                 => 'Price quantity', // 75
      'position_discount'              => 'Position discount',
      'position_value'                 => 'Position value',
      'product_type'                   => 'Product type',
      'tax_rate'                       => 'Tax rate',
      'foreign_tax'                    => 'Foreign tax', // 80
      'customer_order_position'        => 'Customer order position',
      'revenue_type'                   => 'Revenue type',
      'sum_over_positions'             => 'Sum over positions',
      'revenue'                        => 'Revenue',
      'costs'                          => 'Costs', // 85
      'gross_profit'                   => 'Gross profit',
      'margin'                         => 'Margin',
      'costs_manually'                 => 'Costs manually',
      'ean'                            => 'EAN', // 89
    ];
  }

  protected function getFieldDefinitions() {
    return parent::getFieldDefinitions() + [
      'invoice_id'                     => 'i8',
      'position'                       => 'i8',
      'invoice_type'                   => 'i8',
      'client_id'                      => 'i8', // 5
      'order_id'                       => 'i8',
      'customer_id'                    => 'i8',
      'customer_salutation'            => 'c10',
      'customer_title'                 => 'c10',
      'customer_forename'              => 'c40', // 10
      'customer_lastname'              => 'c40',
      'customer_firm'                  => 'c80',
      'customer_department'            => 'c80',
      'customer_street'                => 'c80',
      'customer_zipcode'               => 'c10', // 15
      'customer_city'                  => 'c20',
      'customer_country'               => 'c2',
      'customer_phone'                 => 'c20',
      'customer_phone_2'               => 'c20',
      'customer_fax'                   => 'c20', // 20
      'customer_email'                 => 'c50',
      'customer_bank_account'          => 'c20',
      'customer_bank_code'             => 'c20',
      'customer_bank_account_owner'    => 'c40',
      'customer_bank_iban'             => 'c20', // 25
      'customer_bank_bic'              => 'c20',
      'customer_bank_name'             => 'c20',
      'customer_vat_id'                => 'c20',
      'reserved'                       => 'i8',
      'invoice_date'                   => 'd8', // 30
      'price_date'                     => 'd8',
      'terms_of_payment'               => 'i8',
      'currency'                       => 'c3',
      'price_group'                    => 'i8',
      'discount_id'                    => 'i8', // 35
      'discount_final'                 => 'i8',
      'discount_reason'                => 'c255',
      'invoice_text'                   => 'c1024',
      'final_text'                     => 'c1024',
      'annotation'                     => 'c1024', // 40
      'deleted'                        => 'i8',
      'language'                       => 'i8',
      'employee_id'                    => 'i8',
      'agent_id'                       => 'i8',
      'system_name'                    => 'c20', // 45
      'status'                         => 'i8',
      'discount_final_2'               => 'm18',
      'discount_final_2_reason'        => 'c20',
      'shipping_id'                    => 'i8',
      'shipping_costs'                 => 'm18', // 50
      'cod_costs'                      => 'm18',
      'time_of_delivery'               => 'd8',
      'delivery_conditions'            => 'c3',
      'delivery_conditions_additional' => 'c40',
      'delivery_salutation'            => 'c10', // 55
      'delivery_title'                 => 'c10',
      'delivery_forename'              => 'c40',
      'delivery_lastname'              => 'c40',
      'delivery_firm'                  => 'c80',
      'delivery_department'            => 'c80', // 60
      'delivery_street'                => 'c80',
      'delivery_zipcode'               => 'c10',
      'delivery_city'                  => 'c20',
      'delivery_country'               => 'c2',
      'delivery_phone'                 => 'c20', // 65
      'delivery_phone_2'               => 'c20',
      'delivery_fax'                   => 'c20',
      'delivery_email'                 => 'c50',
      'position_type'                  => 'i8',
      'product_id'                     => 'c20', // 70
      'product_description'            => 'c10000',
      'quantity_unit'                  => 'c3',
      'quantity'                       => 'n18',
      'price'                          => 'm18',
      'price_quantity'                 => 'n18', // 75
      'position_discount'              => 'm18',
      'position_value'                 => 'm18',
      'product_type'                   => 'i8',
      'tax_rate'                       => 'i8',
      'foreign_tax'                    => 'i8', // 80
      'customer_order_position'        => 'i8',
      'revenue_type'                   => 'i8',
      'sum_over_positions'             => 'i8',
      'revenue'                        => 'm18',
      'costs'                          => 'm18', // 85
      'gross_profit'                   => 'm18',
      'margin'                         => 'm18',
      'costs_manually'                 => 'm18',
      'ean'                            => 'c13', // 89
    ];
  }

}
