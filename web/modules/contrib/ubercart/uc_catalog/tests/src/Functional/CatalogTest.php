<?php

namespace Drupal\Tests\uc_catalog\Functional;

/**
 * Tests for the Ubercart catalog.
 *
 * @group ubercart
 */
class CatalogTest extends CatalogTestBase {

  public static $modules = ['history', 'uc_catalog', 'uc_attribute', 'field_ui'];
  public static $adminPermissions = [
    'administer catalog',
    'administer node fields',
    'administer taxonomy_term fields',
    'view catalog',
  ];

  /**
   * Tests the catalog display and "buy it now" button.
   */
  public function testCatalog() {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    $this->drupalLogin($this->adminUser);

    $term = $this->createCatalogTerm();
    $product = $this->createProduct([
      'taxonomy_catalog' => [$term->id()],
    ]);

    $this->drupalGet('catalog');
    $assert->titleEquals('Catalog | Drupal');
    $assert->linkExists($term->label(), 0, 'The term is listed in the catalog.');

    $this->clickLink($term->label());
    $assert->titleEquals($term->label() . ' | Drupal');
    $assert->linkExists($product->label(), 0, 'The product is listed in the catalog.');
    // Check that the product SKU is shown in the catalog.
    $assert->pageTextContains($product->model->value);
    // Check that the product price is shown in the catalog.
    $assert->pageTextContains(uc_currency_format($product->price->value));

    $this->drupalPostForm(NULL, [], 'Add to cart');
    $assert->pageTextContains($product->label() . ' added to your shopping cart.');
  }

  /**
   * Tests the catalog with a product with attributes.
   */
  public function testCatalogAttribute() {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    $this->drupalLogin($this->adminUser);

    $term = $this->createCatalogTerm();
    $product = $this->createProduct([
      'taxonomy_catalog' => [$term->id()],
    ]);
    $attribute = $this->createAttribute(['display' => 0]);
    uc_attribute_subject_save($attribute, 'product', $product->id());

    $this->drupalGet('catalog/' . $term->id());
    $this->drupalPostForm(NULL, [], 'Add to cart');
    $assert->pageTextNotContains($product->label() . ' added to your shopping cart.');
    $assert->pageTextContains('This product has options that need to be selected before purchase. Please select them in the form below.');
  }

  /**
   * Tests the catalog from the node page.
   */
  public function testCatalogNode() {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    $this->drupalLogin($this->adminUser);

    $term = $this->createCatalogTerm();
    $product = $this->createProduct([
      'taxonomy_catalog' => [$term->id()],
    ]);

    $this->drupalGet('node/' . $product->id());
    $assert->linkExists($term->label(), 0, 'The product links back to the catalog term.');
    $assert->linkByHrefExists('/catalog/' . $term->id(), 0, 'The product links back to the catalog view.');
  }

  /**
   * Tests the catalog taxonomy field.
   */
  public function testCatalogField() {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    $this->drupalLogin($this->adminUser);

    $this->drupalGet('admin/structure/taxonomy/manage/catalog/overview/fields');
    // Check that the catalog term image field exists.
    $assert->pageTextContains('uc_catalog_image');

    $this->drupalGet('admin/structure/types/manage/product/fields');
    // Check that the catalog taxonomy term reference field exists for products.
    $assert->pageTextContains('taxonomy_catalog');

    $this->drupalGet('node/add/product');
    $this->assertFieldByName('taxonomy_catalog', NULL, 'Catalog taxonomy field is shown on product node form.');

    // Check that product kits get the catalog taxonomy.
    \Drupal::service('module_installer')->install(['uc_product_kit'], FALSE);

    $this->drupalGet('admin/structure/types/manage/product_kit/fields');
    // Check that the catalog taxonomy term reference field exists for
    // product kits.
    $assert->pageTextContains('taxonomy_catalog');
  }

  /**
   * Tests the catalog repair function.
   */
  public function testCatalogRepair() {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    $this->drupalLogin($this->adminUser);

    $this->drupalPostForm('admin/structure/types/manage/product/fields/node.product.taxonomy_catalog/delete', [], 'Delete');
    // Check that catalog taxonomy term reference field was deleted.
    $assert->pageTextContains('The field Catalog has been deleted from the Product content type.');

    $this->drupalGet('admin/structure/types/manage/product/fields');
    // Check that catalog taxonomy term reference field does not exist.
    $assert->pageTextNotContains('taxonomy_catalog');

    $this->drupalGet('admin/store');
    // Check that store status message mentions the missing field.
    $assert->pageTextContains('The catalog taxonomy reference field is missing.');

    $this->drupalGet('admin/store/config/catalog/repair');
    // Check that repair message is displayed.
    $assert->pageTextContains('The catalog taxonomy reference field has been repaired.');

    $this->drupalGet('admin/structure/types/manage/product/fields');
    // Check that catalog taxonomy term reference field exists.
    $assert->pageTextContains('taxonomy_catalog');
  }

}
