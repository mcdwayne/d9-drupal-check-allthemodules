<?php

namespace Drupal\Tests\uc_product\Functional;

use Drupal\Tests\uc_store\Functional\UbercartBrowserTestBase;

/**
 * Tests the product edit page tabs.
 *
 * @group ubercart
 */
class ProductTabsTest extends UbercartBrowserTestBase {

  public static $modules = ['uc_product', 'uc_attribute', 'uc_stock'];
  public static $adminPermissions = [
    'bypass node access',
    'administer attributes',
    'administer product attributes',
    'administer product options',
    'administer product stock',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests presence of the tabs attached to the product node page.
   */
  public function testProductTabs() {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    $product = $this->createProduct();
    $this->drupalGet('node/' . $product->id() . '/edit');

    // Check we are on the edit page.
    $this->assertFieldByName('title[0][value]', $product->getTitle());

    // Check that each of the tabs exist.
    $assert->linkExists('Product');
    $assert->linkExists('Attributes');
    $assert->linkExists('Options');
    $assert->linkExists('Adjustments');
    $assert->linkExists('Features');
    $assert->linkExists('Stock');
  }

  /**
   * Tests that product tabs don't show up elsewhere.
   */
  public function testNonProductTabs() {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    $this->drupalCreateContentType(['type' => 'page']);
    $page = $this->drupalCreateNode(['type' => 'page']);
    $this->drupalGet('node/' . $page->id() . '/edit');

    // Check we are on the edit page.
    $this->assertFieldByName('title[0][value]', $page->getTitle());

    // Check that each of the tabs do not exist.
    $assert->linkNotExists('Product');
    $assert->linkNotExists('Attributes');
    $assert->linkNotExists('Options');
    $assert->linkNotExists('Adjustments');
    $assert->linkNotExists('Features');
    $assert->linkNotExists('Stock');
  }

  /**
   * Tests that product tabs show up on the product content type page.
   */
  public function testProductTypeTabs() {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    $this->drupalGet('admin/structure/types/manage/product');

    // Check we are on the node type page.
    $this->assertFieldByName('name', 'Product');

    // Check that each of the tabs exist.
    $assert->linkExists('Product attributes');
    $assert->linkExists('Product options');
  }

  /**
   * Tests that product tabs don't show non-product content type pages.
   */
  public function testNonProductTypeTabs() {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    $type = $this->drupalCreateContentType(['type' => 'page']);
    $this->drupalGet('admin/structure/types/manage/' . $type->id());

    // Check we are on the node type page.
    $this->assertFieldByName('name', $type->label());

    // Check that each of the tabs do not exist.
    $assert->linkNotExists('Product attributes');
    $assert->linkNotExists('Product options');
  }

}
