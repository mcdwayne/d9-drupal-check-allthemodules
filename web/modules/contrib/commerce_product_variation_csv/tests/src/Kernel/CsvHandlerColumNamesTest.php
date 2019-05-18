<?php

namespace Drupal\Tests\commerce_product_variation_csv\Kernel;

use Drupal\commerce_product\Entity\ProductVariationType;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * @group commerce_product_variation_csv
 */
class CsvHandlerColumNamesTest extends CommerceProductBulkTestBase {

  public function testDefault() {
    $csv_handler = $this->container->get('commerce_product_variation_csv.csv_handler');

    $definitions = $csv_handler->getVariationFieldDefinitions('default');
    $columns = $csv_handler->getColumnNames($definitions);
    self::assertEquals([
      'status',
      'sku',
      'list_price__number',
      'list_price__currency_code',
      'price__number',
      'price__currency_code',
    ], $columns);
  }

  public function testWithManualTitles() {
    $csv_handler = $this->container->get('commerce_product_variation_csv.csv_handler');

    $variation_type = ProductVariationType::load('default');
    $variation_type->setGenerateTitle(FALSE);
    $variation_type->save();
    $this->container->get('entity_field.manager')->clearCachedFieldDefinitions();

    $definitions = $csv_handler->getVariationFieldDefinitions('default');
    $columns = $csv_handler->getColumnNames($definitions);
    self::assertEquals([
      'status',
      'sku',
      'title',
      'list_price__number',
      'list_price__currency_code',
      'price__number',
      'price__currency_code',
    ], $columns);
  }

  public function testCsvColumnsWithAttributes() {
    $csv_handler = $this->container->get('commerce_product_variation_csv.csv_handler');

    $this->createAttributeSet('default', 'color', [
      'red' => 'Red',
      'blue' => 'Blue',
    ]);

    $definitions = $csv_handler->getVariationFieldDefinitions('default');
    $columns = $csv_handler->getColumnNames($definitions);
    self::assertEquals([
      'status',
      'sku',
      'list_price__number',
      'list_price__currency_code',
      'price__number',
      'price__currency_code',
      'attribute_color'
    ], $columns);
  }

  public function testImageFieldPropertiesIgnored() {
    $csv_handler = $this->container->get('commerce_product_variation_csv.csv_handler');

    $this->installModule('image');
    FieldStorageConfig::create([
      'entity_type' => 'commerce_product_variation',
      'field_name' => 'image_test',
      'type' => 'image',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ])->save();
    FieldConfig::create([
      'entity_type' => 'commerce_product_variation',
      'field_name' => 'image_test',
      'bundle' => 'default',
      'settings' => [
        'file_extensions' => 'jpg',
      ],
    ])->save();

    $definitions = $csv_handler->getVariationFieldDefinitions('default');
    $columns = $csv_handler->getColumnNames($definitions);
    self::assertEquals([
      'status',
      'sku',
      'list_price__number',
      'list_price__currency_code',
      'price__number',
      'price__currency_code',
      'image_test__alt',
      'image_test__title',
    ], $columns);
  }
}
