<?php

namespace Drupal\collmex\CsvBuilder;

use MarcusJaschen\Collmex\Type\Product;

class ImportProductCsvBuilder extends ImportCsvBuilderBase implements ImportCsvBuilderInterface {

  protected function makeCollmexObject(array $values) {
    return new Product($values);
  }

  public function getDefaultValues() {
    return ['client_id' => '1'];
  }

  public function getIdKeys() {
    return ['product_id'];
  }

  public function getDeleteMarkValues() {
    return ['inactive' => 1];
  }

  public function getFields() {
    return [
      'product_id'                    => 'Product ID',
      'product_description'           => 'Product description',
      'product_description_eng'       => 'Product description eng',
      'quantity_unit'                 => 'Quantity unit',
      'product_group'                 => 'Product group',
      'client_id'                     => 'Client ID',
      'tax_rate'                      => 'Tax rate',
      'weight'                        => 'Weight',
      'weight_unit'                   => 'Weight unit', // 10
      'price_quantity'                => 'Price quantity',
      'product_type'                  => 'Product type',
      'inactive'                      => 'Inactive',
      'price_group'                   => 'Price group',
      'price'                         => 'Price',
      'ean'                           => 'EAN',
      'manufacturer'                  => 'Manufacturer',
      'shipping_group'                => 'Shipping group',
      'minimum_quantity'              => 'Minimum quantity',
      'quantity'                      => 'Quantity', // 20
      'lot_mandatory'                 => 'Lot mandatory',
      'procurement'                   => 'Procurement',
      'production_time'               => 'Production time',
      'labor_costs'                   => 'Labor costs',
      'labor_costs_reference_amount'  => 'Labor costs reference amount',
      'annotation'                    => 'Annotation',
      'costing'                       => 'Costing',
      'costs'                         => 'Costs',
      'reference_amount_cost'         => 'Reference amount cost',
      'purchase_supplier'             => 'Purchase supplier', // 30
      'purchase_tax_rate'             => 'Purchase tax rate',
      'product_number_supplier'       => 'Product number supplier',
      'purchase_quantity_per_package' => 'Purchase quantity per package',
      'purchase_description'          => 'Purchase description',
      'purchase_price'                => 'Purchase price',
      'purchase_price_quantity'       => 'Purchase price quantity',
      'purchase_delivery_time'        => 'Purchase delivery time',
      'purchase_currency'             => 'Purchase currency',
      'reserved01'                    => 'Reserved01',
      'reserved02'                    => 'Reserved02', // 40
      'website_id'                    => 'Website ID',
      'shop_short_text'               => 'Shop short text',
      'shop_long_text'                => 'Shop long text',
      'text_type'                     => 'Text type',
      'filename'                      => 'Filename',
      'keywords'                      => 'Keywords',
      'title'                         => 'Title',
      'template_id'                   => 'Template ID',
      'image_url'                     => 'Image URL',
      'base_price_quantity_product'   => 'Base price quantity product', // 50
      'base_price_quantity_base_unit' => 'Base price quantity base unit',
      'base_unit'                     => 'Base unit',
      'requested_price'               => 'Requested price',
      'inactive_alt'                  => 'Inactive alt',
      'shop_category_ids'             => 'shop category IDs',
      'reserved03'                    => 'Reserved03',
      'reserved04'                    => 'Reserved04',
      'reserved05'                    => 'Reserved05',
      'product_number_manufacturer'   => 'Product number manufacturer',
      'delivery_relevant'             => 'Delivery relevant', // 60
      'amazon_asin'                   => 'Amazon asin',
      'ebay_item_number'              => 'Ebay item number',
      'direct_delivery'               => 'Direct delivery',
      'hs_code'                       => 'Hs code', // 64
      'storage_bin'                   => 'Storage bin',
    ];
  }

  protected function getFieldDefinitions() {
    return parent::getFieldDefinitions() + [
      'product_id'                    => 'c20',
      'product_description'           => 'c10000',
      'product_description_eng'       => 'c10000',
      'quantity_unit'                 => 'c3',
      'product_group'                 => 'i8',
      'client_id'                     => 'i8',
      'tax_rate'                      => 'i8',
      'weight'                        => 'n18',
      'weight_unit'                   => 'c3', // 10
      'price_quantity'                => 'n18',
      'product_type'                  => 'i18',
      'inactive'                      => 'i18',
      'price_group'                   => 'i18',
      'price'                         => 'm18',
      'ean'                           => 'c20',
      'manufacturer'                  => 'c40',
      'shipping_group'                => 'i18',
      'minimum_quantity'              => 'n18',
      'quantity'                      => 'n18', // 20
      'lot_mandatory'                 => 'i8',
      'procurement'                   => 'i8',
      'production_time'               => 'i8',
      'labor_costs'                   => 'm18',
      'labor_costs_reference_amount'  => 'n18',
      'annotation'                    => 'c1024',
      'costing'                       => 'i8',
      'costs'                         => 'm8',
      'reference_amount_cost'         => 'n18',
      'purchase_supplier'             => 'n18', // 30
      'purchase_tax_rate'             => 'i8',
      'product_number_supplier'       => 'c20',
      'purchase_quantity_per_package' => 'n18',
      'purchase_description'          => 'c10000',
      'purchase_price'                => 'm18',
      'purchase_price_quantity'       => 'n18',
      'purchase_delivery_time'        => 'i18',
      'purchase_currency'             => 'c3',
      'reserved01'                    => 'i8',
      'reserved02'                    => 'i8', // 40
      'website_id'                    => 'i8',
      'shop_short_text'               => 'c255',
      'shop_long_text'                => 'c10000',
      'text_type'                     => 'i8',
      'filename'                      => 'c80',
      'keywords'                      => 'c255',
      'title'                         => 'c255',
      'template_id'                   => 'i8',
      'image_url'                     => 'c255',
      'base_price_quantity_product'   => 'n18', // 50
      'base_price_quantity_base_unit' => 'n18',
      'base_unit'                     => 'i8',
      'requested_price'               => 'i8',
      'inactive_alt'                  => 'i8',
      'shop_category_ids'             => 'i8',
      'reserved03'                    => 'i8',
      'reserved04'                    => 'i8',
      'reserved05'                    => 'i8',
      'product_number_manufacturer'   => 'c40',
      'delivery_relevant'             => 'i8', // 60
      'amazon_asin'                   => 'c40',
      'ebay_item_number'              => 'c40',
      'direct_delivery'               => 'i8',
      'hs_code'                       => 'c20', // 64
      'storage_bin'                   => 'c20',
    ];
  }

}
