<?php

namespace Drupal\Tests\commerce_product_variation_csv\Kernel;

use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_product\Entity\ProductVariationType;

/**
 * @group commerce_product_variation_csv
 */
class CsvImporterTest extends CommerceProductBulkTestBase {

  public function testImportWithoutGeneratedTitles() {
    $variation_type = ProductVariationType::load('default');
    $variation_type->setGenerateTitle(FALSE);
    $variation_type->save();
    $this->container->get('entity_field.manager')->clearCachedFieldDefinitions();

    $csv_handler = $this->container->get('commerce_product_variation_csv.csv_importer');

    $product = Product::create([
      'type' => 'default',
      'title' => 'My Super Product',
    ]);

    $csv_handler->importCsv($product, __DIR__ . '/../../fixtures/variations_with_titles.csv');

    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface[] $variations */
    $variations = $product->getVariations();
    self::assertCount(2, $variations);

    $first_variation = array_shift($variations);
    $second_variation = array_shift($variations);

    self::assertEquals('SKU1234', $first_variation->getSku());
    self::assertTrue($first_variation->isPublished());
    self::assertEquals(new Price('12.00', 'USD'), $first_variation->getPrice());
    self::assertNull($first_variation->getListPrice());
    self::assertEquals('My Product 1234', $first_variation->label());

    self::assertEquals('SKU5678', $second_variation->getSku());
    self::assertTrue($second_variation->isPublished());
    self::assertEquals(new Price('12.10', 'USD'), $second_variation->getPrice());
    self::assertNull($second_variation->getListPrice());
    self::assertEquals('My Product 5678', $second_variation->label());
  }

  public function testImportGeneratedTitles() {
    $csv_handler = $this->container->get('commerce_product_variation_csv.csv_importer');

    $product = Product::create([
      'type' => 'default',
      'title' => 'My Super Product',
    ]);

    $csv_handler->importCsv($product, __DIR__ . '/../../fixtures/variations_generated_titles.csv');

    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface[] $variations */
    $variations = $product->getVariations();
    self::assertCount(2, $variations);

    $first_variation = array_shift($variations);
    $second_variation = array_shift($variations);

    self::assertEquals('SKU1234', $first_variation->getSku());
    self::assertTrue($first_variation->isPublished());
    self::assertEquals(new Price('12.00', 'USD'), $first_variation->getPrice());
    self::assertNull($first_variation->getListPrice());
    self::assertEquals($product->label(), $first_variation->label());

    self::assertEquals('SKU5678', $second_variation->getSku());
    self::assertTrue($second_variation->isPublished());
    self::assertEquals(new Price('12.10', 'USD'), $second_variation->getPrice());
    self::assertNull($second_variation->getListPrice());
    self::assertEquals($product->label(), $second_variation->label());
  }

  public function testImportMismatchedColumns() {
    $this->setExpectedException(
      \InvalidArgumentException::class,
      'Mismatched columns'
    );

    $csv_handler = $this->container->get('commerce_product_variation_csv.csv_importer');

    $product = Product::create([
      'type' => 'default',
      'title' => 'My Super Product',
    ]);

    $csv_handler->importCsv($product, __DIR__ . '/../../fixtures/variations_with_titles.csv');
  }

  public function testSKuColumnRequired() {
    $this->setExpectedException(
      \InvalidArgumentException::class,
      'SKU is required'
    );

    $csv_handler = $this->container->get('commerce_product_variation_csv.csv_importer');

    $product = Product::create([
      'type' => 'default',
      'title' => 'My Super Product',
    ]);

    $csv_handler->importCsv($product, __DIR__ . '/../../fixtures/variations_missing_sku.csv');
  }

  public function testImportUpdateExistingVariations() {
    $csv_handler = $this->container->get('commerce_product_variation_csv.csv_importer');

    $product = Product::create([
      'type' => 'default',
      'title' => 'My Super Product',
    ]);
    $product->save();

    $variation = ProductVariation::create([
      'type' => 'default',
      'sku' => 'SKU5678',
      'price' => new Price('5.00', 'USD')
    ]);
    $variation->save();
    $product->addVariation($variation);
    $product->save();

    $csv_handler->importCsv($product, __DIR__ . '/../../fixtures/variations_generated_titles.csv');

    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface[] $variations */
    $variations = $product->getVariations();
    self::assertCount(2, $variations);

    /** @var \Drupal\commerce_product\Entity\ProductVariation $variation */
    $variation = $this->reloadEntity($variation);
    self::assertEquals('SKU5678', $variation->getSku());
    self::assertEquals(new Price('12.10', 'USD'), $variation->getPrice());
  }

  public function testImportWithAttributeColorNotExisting() {
    $csv_handler = $this->container->get('commerce_product_variation_csv.csv_importer');

    $product = Product::create([
      'type' => 'default',
      'title' => 'My Super Product',
    ]);
    $this->createAttributeSet('default', 'color', []);

    $csv_handler->importCsv($product, __DIR__ . '/../../fixtures/variations_attribute_color.csv');

    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface[] $variations */
    $variations = $product->getVariations();
    self::assertCount(2, $variations);

    $first_variation = array_shift($variations);
    $second_variation = array_shift($variations);

    self::assertEquals('SKU1234', $first_variation->getSku());
    self::assertTrue($first_variation->isPublished());
    self::assertEquals(new Price('12.00', 'USD'), $first_variation->getPrice());
    self::assertNull($first_variation->getListPrice());
    self::assertEquals($product->label() . ' - Red', $first_variation->label());
    self::assertEquals('Red', $first_variation->getAttributeValue('attribute_color')->label());

    self::assertEquals('SKU5678', $second_variation->getSku());
    self::assertTrue($second_variation->isPublished());
    self::assertEquals(new Price('12.10', 'USD'), $second_variation->getPrice());
    self::assertNull($second_variation->getListPrice());
    self::assertEquals($product->label() . ' - Blue', $second_variation->label());
    self::assertEquals('Blue', $second_variation->getAttributeValue('attribute_color')->label());
  }

  public function testImportWithAttributeColorExisting() {
    $csv_handler = $this->container->get('commerce_product_variation_csv.csv_importer');

    $product = Product::create([
      'type' => 'default',
      'title' => 'My Super Product',
    ]);
    $product->save();
    $this->createAttributeSet('default', 'color', [
      'red' => 'Red',
      'blue' => 'Blue',
    ]);

    $csv_handler->importCsv($product, __DIR__ . '/../../fixtures/variations_attribute_color.csv');

    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface[] $variations */
    $variations = $product->getVariations();
    self::assertCount(2, $variations);

    $first_variation = array_shift($variations);
    $second_variation = array_shift($variations);

    self::assertEquals('SKU1234', $first_variation->getSku());
    self::assertTrue($first_variation->isPublished());
    self::assertEquals(new Price('12.00', 'USD'), $first_variation->getPrice());
    self::assertNull($first_variation->getListPrice());
    self::assertEquals($product->label() . ' - Red', $first_variation->label());
    self::assertEquals('Red', $first_variation->getAttributeValue('attribute_color')->label());

    self::assertEquals('SKU5678', $second_variation->getSku());
    self::assertTrue($second_variation->isPublished());
    self::assertEquals(new Price('12.10', 'USD'), $second_variation->getPrice());
    self::assertNull($second_variation->getListPrice());
    self::assertEquals($product->label() . ' - Blue', $second_variation->label());
    self::assertEquals('Blue', $second_variation->getAttributeValue('attribute_color')->label());

    $attribute_value_storage = $this->container->get('entity_type.manager')->getStorage('commerce_product_attribute_value');
    $count = $attribute_value_storage->getQuery()->accessCheck(FALSE)->count()->execute();
    $this->assertEquals(2, $count, 'Existing attribute values were reused.');
  }

}
