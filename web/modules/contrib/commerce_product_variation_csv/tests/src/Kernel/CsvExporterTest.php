<?php

namespace Drupal\Tests\commerce_product_variation_csv\Kernel;

use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductVariation;

/**
 * @group commerce_product_variation_csv
 */
class CsvExporterTest extends CommerceProductBulkTestBase {
  public function testEmptyVariations() {
    $csv_handler = $this->container->get('commerce_product_variation_csv.csv_exporter');

    $product = Product::create([
      'type' => 'default',
      'title' => 'My Super Product',
    ]);

    $result = $csv_handler->createCsv($product);

    $this->assertEquals(
      'status,sku,list_price__number,list_price__currency_code,price__number,price__currency_code',
      trim($result)
    );
  }
  public function testHasVariations() {
    $csv_handler = $this->container->get('commerce_product_variation_csv.csv_exporter');

    $product = Product::create([
      'type' => 'default',
      'title' => 'My Super Product',
    ]);
    $variation = ProductVariation::create([
      'type' => 'default',
      'sku' => 'SKU5678',
      'price' => new Price('5.00', 'USD')
    ]);
    $variation->save();
    $product->addVariation($variation);
    $product->save();

    $result = $csv_handler->createCsv($product);

    $this->assertEquals(
      "status,sku,list_price__number,list_price__currency_code,price__number,price__currency_code\n1,SKU5678,,,5.000000,USD",
      trim($result)
    );
  }

  public function testHasAttributes() {
    $csv_handler = $this->container->get('commerce_product_variation_csv.csv_exporter');

    $attribute_values = $this->createAttributeSet('default', 'color', [
      'red' => 'Red',
      'blue' => 'Blue',
    ]);

    $product = Product::create([
      'type' => 'default',
      'title' => 'My Super Product',
    ]);
    $variation = ProductVariation::create([
      'type' => 'default',
      'sku' => 'SKU5678',
      'price' => new Price('5.00', 'USD'),
      'attribute_color' => $attribute_values['red']->id(),
    ]);
    $variation->save();
    $product->addVariation($variation);
    $variation = ProductVariation::create([
      'type' => 'default',
      'sku' => 'SKU5679',
      'price' => new Price('5.00', 'USD'),
      'attribute_color' => $attribute_values['blue']->id(),
    ]);
    $variation->save();
    $product->addVariation($variation);
    $product->save();

    $result = $csv_handler->createCsv($product);

    $this->assertEquals(
      'status,sku,list_price__number,list_price__currency_code,price__number,price__currency_code,attribute_color
1,SKU5678,,,5.000000,USD,Red
1,SKU5679,,,5.000000,USD,Blue',
      trim($result)
    );
  }

  public function testExportAndImport() {
    $attribute_values = $this->createAttributeSet('default', 'color', [
      'red' => 'Red',
      'blue' => 'Blue',
    ]);

    $product = Product::create([
      'type' => 'default',
      'title' => 'My Super Product',
    ]);
    $variation = ProductVariation::create([
      'type' => 'default',
      'sku' => 'SKU5678',
      'price' => new Price('5.00', 'USD'),
      'attribute_color' => $attribute_values['red']->id(),
    ]);
    $variation->save();
    $product->addVariation($variation);
    $variation = ProductVariation::create([
      'type' => 'default',
      'sku' => 'SKU5679',
      'price' => new Price('5.00', 'USD'),
      'attribute_color' => $attribute_values['blue']->id(),
    ]);
    $variation->save();
    $product->addVariation($variation);
    $product->save();

    $result = $this->container->get('commerce_product_variation_csv.csv_exporter')->createCsv($product);

    $this->assertEquals(
      'status,sku,list_price__number,list_price__currency_code,price__number,price__currency_code,attribute_color
1,SKU5678,,,5.000000,USD,Red
1,SKU5679,,,5.000000,USD,Blue',
      trim($result)
    );

    file_put_contents('public://test_import.csv', $result);

    // Change a variation's price and check if it reverts after import.
    $variation->setPrice(new Price('8.00', 'USD'));
    $variation->save();

    $this->container->get('commerce_product_variation_csv.csv_importer')->importCsv($product, 'public://test_import.csv');

    $variation = $this->reloadEntity($variation);
    $this->assertEquals(new Price('5.00', 'USD'), $variation->getPrice(0));
  }
}
