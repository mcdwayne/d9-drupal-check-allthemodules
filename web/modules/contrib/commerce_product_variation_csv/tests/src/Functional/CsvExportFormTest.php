<?php

namespace Drupal\Tests\commerce_product_variation_csv\Functional;

use Drupal\commerce_product\Entity\Product;
use Drupal\Core\Url;
use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;

/**
 * @group commerce_product_variation_csv
 */
class CsvExportFormTest extends CommerceBrowserTestBase {
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

  public function testCsvExportActionLink() {
    $url = Url::fromRoute('entity.commerce_product_variation.collection', ['commerce_product' => $this->product->id()]);
    $this->drupalGet($url);

    $this->assertSession()->linkExists('Add variation');
    $this->assertSession()->linkExists('Export CSV');
  }

  public function testCsvExportForm() {
    $url = Url::fromRoute('entity.commerce_product_variation.collection', ['commerce_product' => $this->product->id()]);
    $this->drupalGet($url);

    $this->clickLink('Export CSV');
    $this->assertSession()->pageTextContains('Click "Export variations as CSV" to retrieve a CSV of variation data');
    $this->clickLink('Back to variations');
    $this->assertSession()->linkExists('Add variation');
    $this->assertSession()->linkExists('Export CSV');

    $this->clickLink('Export CSV');
    $this->getSession()->getPage()->pressButton('Export variations as CSV');

    // Due to \Behat\Mink\Element\DocumentElement::getXpath being hardcoded to
    // //html we cannot try to assert the raw response value.
    $this->assertSession()->responseHeaderEquals('Content-Disposition', sprintf('attachment; filename="%s.csv"', $this->product->label()));
  }

}
