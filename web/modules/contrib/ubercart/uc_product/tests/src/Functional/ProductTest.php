<?php

namespace Drupal\Tests\uc_product\Functional;

use Drupal\Tests\uc_store\Functional\UbercartBrowserTestBase;

/**
 * Tests the product content type.
 *
 * @group ubercart
 */
class ProductTest extends UbercartBrowserTestBase {

  public static $modules = ['path', 'uc_product'];
  public static $adminPermissions = ['administer content types'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests product administration view.
   */
  public function testProductAdmin() {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    $this->drupalGet('admin/store/products/view');
    $assert->pageTextContains('Title');
    $assert->pageTextContains($this->product->getTitle());
    $assert->pageTextContains('Price');
    $assert->pageTextContains(uc_currency_format($this->product->price->value));
  }

  /**
   * Tests product node form.
   */
  public function testProductNodeForm() {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    $this->drupalGet('node/add/product');

    $fields = [
      'model[0][value]',
      'price[0][value]',
      'shippable[value]',
      'weight[0][value]',
      'weight[0][units]',
      'dimensions[0][length]',
      'dimensions[0][width]',
      'dimensions[0][height]',
      'dimensions[0][units]',
      'files[uc_product_image_0][]',
    ];
    foreach ($fields as $field) {
      $this->assertFieldByName($field, NULL);
    }

    $title_key = 'title[0][value]';
    $body_key = 'body[0][value]';

    // Make a node with those fields.
    $edit = [
      $title_key => $this->randomMachineName(32),
      $body_key => $this->randomMachineName(64),
      'model[0][value]' => $this->randomMachineName(8),
      'price[0][value]' => mt_rand(1, 150),
      'shippable[value]' => mt_rand(0, 1),
      'weight[0][value]' => mt_rand(1, 50),
      'weight[0][units]' => array_rand([
        'lb' => 'Pounds',
        'kg' => 'Kilograms',
        'oz' => 'Ounces',
        'g'  => 'Grams',
      ]),
      'dimensions[0][length]' => mt_rand(1, 50),
      'dimensions[0][width]' => mt_rand(1, 50),
      'dimensions[0][height]' => mt_rand(1, 50),
      'dimensions[0][units]' => array_rand([
        'in' => 'Inches',
        'ft' => 'Feet',
        'cm' => 'Centimeters',
        'mm' => 'Millimeters',
      ]),
    ];
    $this->drupalPostForm('node/add/product', $edit, 'Save');

    // Check for product created message, and check for the expected product
    // field values on the newly-created product page.
    $assert->pageTextContains(format_string('Product @title has been created.', ['@title' => $edit[$title_key]]));
    // Product body text.
    $assert->pageTextContains($edit[$body_key]);
    // Product model (SKU) text.
    $assert->pageTextContains($edit['model[0][value]']);
    // Product price text.
    $this->assertNoUniqueText(uc_currency_format($edit['price[0][value]']));
    // Formatted product weight text.
    $assert->pageTextContains(uc_weight_format($edit['weight[0][value]'], $edit['weight[0][units]']));
    // Formatted product dimensions text.
    $assert->pageTextContains(uc_length_format($edit['dimensions[0][length]'], $edit['dimensions[0][units]']));
    $assert->pageTextContains(uc_length_format($edit['dimensions[0][width]'], $edit['dimensions[0][units]']));
    $assert->pageTextContains(uc_length_format($edit['dimensions[0][height]'], $edit['dimensions[0][units]']));

    $elements = $this->xpath('//body[contains(@class, "uc-product-node")]');
    $this->assertEquals(count($elements), 1, 'Product page contains body CSS class.');

    // Update the node fields.
    $edit = [
      $title_key => $this->randomMachineName(32),
      $body_key => $this->randomMachineName(64),
      'model[0][value]' => $this->randomMachineName(8),
      'price[0][value]' => mt_rand(1, 150),
      'shippable[value]' => mt_rand(0, 1),
      'weight[0][value]' => mt_rand(1, 50),
      'weight[0][units]' => array_rand([
        'lb' => 'Pounds',
        'kg' => 'Kilograms',
        'oz' => 'Ounces',
        'g'  => 'Grams',
      ]),
      'dimensions[0][length]' => mt_rand(1, 50),
      'dimensions[0][width]' => mt_rand(1, 50),
      'dimensions[0][height]' => mt_rand(1, 50),
      'dimensions[0][units]' => array_rand([
        'in' => 'Inches',
        'ft' => 'Feet',
        'cm' => 'Centimeters',
        'mm' => 'Millimeters',
      ]),
    ];
    $this->clickLink('Edit');
    $this->drupalPostForm(NULL, $edit, 'Save');

    // Check for product updated message, and check for the expected product
    // field values on the updated product page.
    $assert->pageTextContains(format_string('Product @title has been updated.', ['@title' => $edit[$title_key]]));
    // Product body text.
    $assert->pageTextContains($edit[$body_key]);
    // Product model (SKU) text.
    $assert->pageTextContains($edit['model[0][value]']);
    // Product price text.
    $this->assertNoUniqueText(uc_currency_format($edit['price[0][value]']));
    // Formatted product weight text.
    $assert->pageTextContains(uc_weight_format($edit['weight[0][value]'], $edit['weight[0][units]']));
    // Formatted product dimensions text.
    $assert->pageTextContains(uc_length_format($edit['dimensions[0][length]'], $edit['dimensions[0][units]']));
    $assert->pageTextContains(uc_length_format($edit['dimensions[0][width]'], $edit['dimensions[0][units]']));
    $assert->pageTextContains(uc_length_format($edit['dimensions[0][height]'], $edit['dimensions[0][units]']));

    $this->clickLink('Delete');
    $this->drupalPostForm(NULL, [], 'Delete');
    // Check for product deleted message.
    $assert->pageTextContains(format_string('Product @title has been deleted.', ['@title' => $edit[$title_key]]));
  }

  /**
   * Tests adding a product with weight = dimensions = 0.
   */
  public function testZeroProductWeightAndDimensions() {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    $edit = [
      'title[0][value]' => $this->randomMachineName(32),
      'model[0][value]' => $this->randomMachineName(8),
      'price[0][value]' => mt_rand(1, 150),
      'shippable[value]' => mt_rand(0, 1),
      'weight[0][value]' => 0,
      'weight[0][units]' => array_rand([
        'lb' => 'Pounds',
        'kg' => 'Kilograms',
        'oz' => 'Ounces',
        'g'  => 'Grams',
      ]),
      'dimensions[0][length]' => 0,
      'dimensions[0][width]' => 0,
      'dimensions[0][height]' => 0,
      'dimensions[0][units]' => array_rand([
        'in' => 'Inches',
        'ft' => 'Feet',
        'cm' => 'Centimeters',
        'mm' => 'Millimeters',
      ]),
    ];
    $this->drupalPostForm('node/add/product', $edit, 'Save');

    // Check for product created message.
    $assert->pageTextContains(format_string('Product @title has been created.', ['@title' => $edit['title[0][value]']]));
    // Check that Weight and Dimensions are NOT shown on product page if
    // they are set to zero.
    $assert->pageTextNotContains('Weight');
    $assert->pageTextNotContains('Dimensions');
  }

  /**
   * Tests making node types into products.
   */
  public function testProductClassForm() {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    // Try making a new product class.
    $class = strtolower($this->randomMachineName(12));
    $edit = [
      'type' => $class,
      'name' => $class,
      'description' => $this->randomMachineName(32),
      'uc_product[product]' => 1,
    ];
    $this->drupalPostForm('admin/structure/types/add', $edit, 'Save content type');
    $this->assertTrue(uc_product_is_product($class), 'The new content type is a product class.');

    // Make an existing node type a product class.
    $type = $this->drupalCreateContentType([
      'description' => $this->randomMachineName(),
    ]);
    $edit = [
      'uc_product[product]' => 1,
    ];
    $this->drupalPostForm('admin/structure/types/manage/' . $type->getOriginalId(), $edit, 'Save content type');
    $this->assertTrue(uc_product_is_product($type->getOriginalId()), 'The updated content type is a product class.');

    // Check the product classes page.
    $this->drupalGet('admin/store/products/classes');
    // Check the product class is listed.
    $assert->pageTextContains($type->getOriginalId());
    // Check the product class description is found in the list.
    $assert->pageTextContains($type->getDescription());
    // Check the product class edit link is shown.
    $assert->linkByHrefExists('admin/structure/types/manage/' . $type->getOriginalId(), 0);
    // Check the product class delete link is shown.
    $assert->linkByHrefExists('admin/structure/types/manage/' . $type->getOriginalId() . '/delete', 0);

    // Remove the product class again.
    $edit = ['uc_product[product]' => FALSE];
    $this->drupalPostForm('admin/structure/types/manage/' . $class, $edit, 'Save content type');
    $this->assertFalse(uc_product_is_product($class), 'The updated content type is no longer a product class.');
  }

  /**
   * Tests product add-to-cart quantity.
   */
  public function testProductQuantity() {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    $edit = ['uc_product_add_to_cart_qty' => TRUE];
    $this->drupalPostForm('admin/store/config/products', $edit, 'Save configuration');

    // Check zero quantity message.
    $this->addToCart($this->product, ['qty' => 0]);
    $assert->pageTextContains('The quantity cannot be zero.');

    // Check invalid quantity messages.
    $this->addToCart($this->product, ['qty' => 'x']);
    $assert->pageTextContains('The quantity must be an integer.');

    $this->addToCart($this->product, ['qty' => '1a']);
    $assert->pageTextContains('The quantity must be an integer.');

    // Check cart add message.
    $this->addToCart($this->product, ['qty' => 1]);
    $assert->pageTextContains($this->product->getTitle() . ' added to your shopping cart.');

    // Check cart update message.
    $this->addToCart($this->product, ['qty' => 1]);
    $assert->pageTextContains('Your item(s) have been updated.');
  }

}
