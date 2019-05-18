<?php

namespace Drupal\Tests\commerce_product_variation_csv\Functional;

use Drupal\commerce_product\Entity\Product;
use Drupal\Core\Url;
use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;

/**
 * @group commerce_product_variation_csv
 */
class CsvImportFormTest extends CommerceBrowserTestBase {
  public static $modules = [
    'commerce_product_variation_csv',
  ];

  /**
   * The product to test against.
   *
   * @var \Drupal\commerce_product\Entity\ProductInterface
   */
  protected $product;

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge([
      'administer commerce_product',
      'administer commerce_product_type',
      'access commerce_product overview',
    ], parent::getAdministratorPermissions());
  }

  protected function setUp() {
    parent::setUp();

    $this->product = Product::create([
      'type' => 'default',
      'title' => 'My Super Product',
    ]);
    $this->product->save();
  }

  public function testCsvImportActionLink() {
    $url = Url::fromRoute('entity.commerce_product_variation.collection', ['commerce_product' => $this->product->id()]);
    $this->drupalGet($url);

    $this->assertSession()->linkExists('Add variation');
    $this->assertSession()->linkExists('Import CSV');
  }

  public function testCsvImportForm() {
    $url = Url::fromRoute('entity.commerce_product_variation.collection', ['commerce_product' => $this->product->id()]);
    $this->drupalGet($url);

    $this->clickLink('Import CSV');
    $this->assertSession()->pageTextContains('Unsure about the format? Download the source file.');
    $this->clickLink('Back to variations');
    $this->assertSession()->linkExists('Add variation');
    $this->assertSession()->linkExists('Import CSV');

    $this->clickLink('Import CSV');
    $this->getSession()->getPage()->attachFileToField('files[csv]', __DIR__ . '/../../fixtures/variations_generated_titles.csv');
    $this->submitForm([], 'Import variations from CSV');
    $this->assertSession()->pageTextContains('Imported 2 variations.');

    /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
    $product = $this->reloadEntity($this->product);
    $this->assertCount(2, $product->getVariationIds());

  }

}
