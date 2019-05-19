<?php

namespace Drupal\Tests\uc_product_kit\Functional;

use Drupal\Tests\uc_catalog\Traits\CatalogTestTrait;
use Drupal\Tests\uc_product\Traits\ProductTestTrait;
use Drupal\Tests\uc_store\Functional\UbercartBrowserTestBase;

/**
 * Tests product kit functionality.
 *
 * @group ubercart
 */
class ProductKitTest extends UbercartBrowserTestBase {
  use CatalogTestTrait;
  use ProductTestTrait;

  public static $modules = ['uc_product_kit', 'uc_catalog'];
  public static $adminPermissions = [
    'create product_kit content',
    'edit any product_kit content',
    'view catalog',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Need page_title_block because we test page titles.
    $this->drupalPlaceBlock('page_title_block');
  }

  /**
   * Tests creating product kits through the node form.
   */
  public function testProductKitNodeForm() {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    $this->drupalLogin($this->adminUser);

    // Allow the default quantity to be set.
    $edit = ['uc_product_add_to_cart_qty' => TRUE];
    $this->drupalPostForm('admin/store/config/products', $edit, 'Save configuration');

    // Create some test products.
    $products = [];
    for ($i = 0; $i < 3; $i++) {
      $products[$i] = $this->createProduct();
    }

    // Test the product kit fields.
    $this->drupalGet('node/add/product_kit');
    foreach (['mutable', 'products[]'] as $field) {
      $this->assertFieldByName($field);
    }

    // Test creation of a basic kit.
    $title_key = 'title[0][value]';
    $body_key = 'body[0][value]';
    $edit = [
      $title_key => $this->randomMachineName(32),
      $body_key => $this->randomMachineName(64),
      'products[]' => [
        $products[0]->id(),
        $products[1]->id(),
        $products[2]->id(),
      ],
    ];
    $this->drupalPostForm('node/add/product_kit', $edit, 'Save');
    $assert->pageTextContains(format_string('Product kit @title has been created.', ['@title' => $edit[$title_key]]));
    $assert->pageTextContains($edit[$body_key], 'Product kit body found.');
    $assert->pageTextContains('1 × ' . $products[0]->label(), 'Product 1 title found.');
    $assert->pageTextContains('1 × ' . $products[1]->label(), 'Product 2 title found.');
    $assert->pageTextContains('1 × ' . $products[2]->label(), 'Product 3 title found.');
    $total = $products[0]->price->value + $products[1]->price->value + $products[2]->price->value;
    $assert->pageTextContains(uc_currency_format($total), 'Product kit total found.');
  }

  /**
   * Tests product kit discounting.
   */
  public function testProductKitDiscounts() {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    $this->drupalLogin($this->adminUser);

    // Create some test products and a kit.
    $products = [];
    for ($i = 0; $i < 3; $i++) {
      $products[$i] = $this->createProduct();
    }
    $kit = $this->drupalCreateNode([
      'type' => 'product_kit',
      'title[0][value]' => $this->randomMachineName(32),
      'products' => [
        $products[0]->id(),
        $products[1]->id(),
        $products[2]->id(),
      ],
      'mutable' => UC_PRODUCT_KIT_UNMUTABLE_NO_LIST,
    ]);

    // Test the product kit extra fields available to configure discounts.
    $this->drupalGet('node/' . $kit->id() . '/edit');
    $this->assertFieldByName('kit_total');
    foreach ($products as $product) {
      $this->assertFieldByName('items[' . $product->id() . '][qty]');
      $this->assertFieldByName('items[' . $product->id() . '][ordering]');
      $this->assertFieldByName('items[' . $product->id() . '][discount]');
    }

    // Set some discounts.
    $discounts = [
      mt_rand(-100, 100),
      mt_rand(-100, 100),
      mt_rand(-100, 100),
    ];
    $edit = [
      'items[' . $products[0]->id() . '][discount]' => $discounts[0],
      'items[' . $products[1]->id() . '][discount]' => $discounts[1],
      'items[' . $products[2]->id() . '][discount]' => $discounts[2],
    ];
    $this->drupalPostForm('node/' . $kit->id() . '/edit', $edit, 'Save');

    // Check the discounted total.
    $total = $products[0]->price->value + $products[1]->price->value + $products[2]->price->value;
    $total += array_sum($discounts);
    $assert->pageTextContains(uc_currency_format($total), 'Discounted product kit total found.');

    // Check the discounts on the edit page.
    $this->drupalGet('node/' . $kit->id() . '/edit');
    $assert->pageTextContains('Currently, the total price is ' . uc_currency_format($total), 'Discounted product kit total found.');
    $this->assertFieldByName('items[' . $products[0]->id() . '][discount]', $discounts[0]);
    $this->assertFieldByName('items[' . $products[1]->id() . '][discount]', $discounts[1]);
    $this->assertFieldByName('items[' . $products[2]->id() . '][discount]', $discounts[2]);

    // Set the kit total.
    $total = 2 * ($products[0]->price->value + $products[1]->price->value + $products[2]->price->value);
    $this->drupalPostForm('node/' . $kit->id() . '/edit', ['kit_total' => $total], 'Save');

    // Check the fixed total.
    $assert->pageTextContains(uc_currency_format($total), 'Fixed product kit total found.');

    // Check the discounts on the edit page.
    $this->drupalGet('node/' . $kit->id() . '/edit');
    $this->assertFieldByName('kit_total', $total);
    $this->assertFieldByName('items[' . $products[0]->id() . '][discount]', $products[0]->price->value);
    $this->assertFieldByName('items[' . $products[1]->id() . '][discount]', $products[1]->price->value);
    $this->assertFieldByName('items[' . $products[2]->id() . '][discount]', $products[2]->price->value);

    // Reset the kit prices so the discounts should equal zero.
    $edit = [
      'price[0][value]' => $total - ($products[1]->price->value + $products[2]->price->value),
    ];
    $this->drupalPostForm('node/' . $products[0]->id() . '/edit', $edit, 'Save');

    // Check the kit total is still the same.
    $this->drupalGet('node/' . $kit->id());
    $assert->pageTextContains(uc_currency_format($total), 'Fixed product kit total found.');

    // Check the discounts are zeroed on the edit page.
    $this->drupalGet('node/' . $kit->id() . '/edit');
    $this->assertFieldByName('kit_total', $total);
    $this->assertFieldByName('items[' . $products[0]->id() . '][discount]', '0.000');
    $this->assertFieldByName('items[' . $products[1]->id() . '][discount]', '0.000');
    $this->assertFieldByName('items[' . $products[2]->id() . '][discount]', '0.000');
  }

  /**
   * Tests product kit mutability.
   */
  public function testProductKitMutability() {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    $this->drupalLogin($this->adminUser);

    // Create some test products and prepare a kit.
    $products = [];
    for ($i = 0; $i < 3; $i++) {
      $products[$i] = $this->createProduct();
    }
    $kit_data = [
      'type' => 'product_kit',
      'title[0][value]' => $this->randomMachineName(32),
      'products' => [
        $products[0]->id(),
        $products[1]->id(),
        $products[2]->id(),
      ],
    ];

    // Test kits with no listing.
    $kit_data['mutable'] = UC_PRODUCT_KIT_UNMUTABLE_NO_LIST;
    $kit = $this->drupalCreateNode($kit_data);
    $this->drupalGet('node/' . $kit->id());
    $assert->pageTextContains($kit->label(), 'Product kit title found.');
    $assert->pageTextNotContains($products[0]->label(), 'Product 1 title not shown.');
    $assert->pageTextNotContains($products[1]->label(), 'Product 2 title not shown.');
    $assert->pageTextNotContains($products[2]->label(), 'Product 3 title not shown.');

    $this->addToCart($kit);
    $this->drupalGet('cart');
    $assert->pageTextContains($kit->label(), 'Product kit title found.');
    $assert->pageTextNotContains($products[0]->label(), 'Product 1 title not shown.');
    $assert->pageTextNotContains($products[1]->label(), 'Product 2 title not shown.');
    $assert->pageTextNotContains($products[2]->label(), 'Product 3 title not shown.');

    $total = $products[0]->price->value + $products[1]->price->value + $products[2]->price->value;
    $this->assertSession()->pageTextMatches('/Subtotal:\s*' . preg_quote(uc_currency_format($total)) . '/', 'Product kit total found.');

    $qty = mt_rand(2, 10);
    $this->drupalPostForm(NULL, ['items[2][qty]' => $qty], 'Update cart');
    $this->assertSession()->pageTextMatches('/Subtotal:\s*' . preg_quote(uc_currency_format($total * $qty)) . '/', 'Updated product kit total found.');

    $this->drupalPostForm(NULL, [], 'Remove');
    $assert->pageTextContains('There are no products in your shopping cart.');

    // Test kits with listing.
    $kit_data['mutable'] = UC_PRODUCT_KIT_UNMUTABLE_WITH_LIST;
    $kit = $this->drupalCreateNode($kit_data);
    $this->drupalGet('node/' . $kit->id());
    $assert->pageTextContains($kit->label(), 'Product kit title found.');
    $assert->pageTextContains($products[0]->label(), 'Product 1 title shown.');
    $assert->pageTextContains($products[1]->label(), 'Product 2 title shown.');
    $assert->pageTextContains($products[2]->label(), 'Product 3 title shown.');

    $this->addToCart($kit);
    $this->drupalGet('cart');
    $assert->pageTextContains($kit->label(), 'Product kit title found.');
    $assert->pageTextContains($products[0]->label(), 'Product 1 title shown.');
    $assert->pageTextContains($products[1]->label(), 'Product 2 title shown.');
    $assert->pageTextContains($products[2]->label(), 'Product 3 title shown.');

    $total = $products[0]->price->value + $products[1]->price->value + $products[2]->price->value;
    $this->assertSession()->pageTextMatches('/Subtotal:\s*' . preg_quote(uc_currency_format($total)) . '/', 'Product kit total found.');

    $qty = mt_rand(2, 10);
    $this->drupalPostForm(NULL, ['items[2][qty]' => $qty], 'Update cart');
    $this->assertSession()->pageTextMatches('/Subtotal:\s*' . preg_quote(uc_currency_format($total * $qty)) . '/', 'Updated product kit total found.');

    $this->drupalPostForm(NULL, [], 'Remove');
    $assert->pageTextContains('There are no products in your shopping cart.');

    // Test mutable kits.
    $kit_data['mutable'] = UC_PRODUCT_KIT_MUTABLE;
    $kit = $this->drupalCreateNode($kit_data);
    $this->drupalGet('node/' . $kit->id());
    $assert->pageTextContains($kit->label(), 'Product kit title found.');
    $assert->pageTextContains($products[0]->label(), 'Product 1 title shown.');
    $assert->pageTextContains($products[1]->label(), 'Product 2 title shown.');
    $assert->pageTextContains($products[2]->label(), 'Product 3 title shown.');

    $this->addToCart($kit);
    $this->drupalGet('cart');
    $assert->pageTextNotContains($kit->label(), 'Product kit title not shown.');
    $assert->pageTextContains($products[0]->label(), 'Product 1 title shown.');
    $assert->pageTextContains($products[1]->label(), 'Product 2 title shown.');
    $assert->pageTextContains($products[2]->label(), 'Product 3 title shown.');

    $total = $products[0]->price->value + $products[1]->price->value + $products[2]->price->value;
    $this->assertSession()->pageTextMatches('/Subtotal:\s*' . preg_quote(uc_currency_format($total)) . '/', 'Product kit total found.');

    $qty = [mt_rand(2, 10), mt_rand(2, 10), mt_rand(2, 10)];
    $edit = [
      'items[0][qty]' => $qty[0],
      'items[1][qty]' => $qty[1],
      'items[2][qty]' => $qty[2],
    ];
    $this->drupalPostForm(NULL, $edit, 'Update cart');
    $total = $products[0]->price->value * $qty[0];
    $total += $products[1]->price->value * $qty[1];
    $total += $products[2]->price->value * $qty[2];
    $this->assertSession()->pageTextMatches('/Subtotal:\s*' . preg_quote(uc_currency_format($total)) . '/', 'Updated product kit total found.');

    $this->drupalPostForm(NULL, [], 'Remove');
    $this->drupalPostForm(NULL, [], 'Remove');
    $this->drupalPostForm(NULL, [], 'Remove');
    $assert->pageTextContains('There are no products in your shopping cart.');
  }

  /**
   * Verify uc_product_kit_uc_form_alter() doesn't break catalog view.
   *
   * @see https://www.drupal.org/project/ubercart/issues/2932702
   */
  public function testUcFormAlter() {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    $this->drupalLogin($this->adminUser);

    $term = $this->createCatalogTerm();
    $product = $this->createProduct([
      'taxonomy_catalog' => [$term->id()],
    ]);

    $this->drupalGet('catalog');
    $assert->linkExists($term->label(), 0, 'The term is listed in the catalog.');

    // Clicking this link generates a fatal error if the BuyItNowForm form
    // element 'node' does not exist.
    $this->clickLink($term->label());
    $assert->linkExists($product->label(), 0, 'The product is listed in the catalog.');
  }

}
