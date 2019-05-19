<?php

namespace Drupal\Tests\uc_cart\Functional;

use Drupal\Tests\uc_store\Functional\UbercartBrowserTestBase;

/**
 * Tests the cart block functionality.
 *
 * @group ubercart
 */
class CartBlockTest extends UbercartBrowserTestBase {

  public static $modules = ['uc_cart', 'block'];

  /**
   * The cart block being tested.
   *
   * @var \Drupal\block\Entity\Block
   */
  protected $block;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->block = $this->drupalPlaceBlock('uc_cart_block');
  }

  /**
   * Test cart block functionality.
   */
  public function testCartBlock() {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    // Test the empty cart block.
    $this->drupalGet('');

    $assert->responseContains('empty');
    $assert->pageTextContains('There are no products in your shopping cart.');
    $assert->pageTextContains('0 Items');
    $assert->pageTextContains('Total: $0.00');
    $assert->linkNotExists('View cart');
    $assert->linkNotExists('Checkout');

    // Test the cart block with an item.
    $this->addToCart($this->product);
    $this->drupalGet('');

    $assert->responseNotContains('empty');
    $assert->pageTextNotContains('There are no products in your shopping cart.');
    $assert->pageTextContains('1 ×');
    $assert->pageTextContains($this->product->label());
    $this->assertNoUniqueText(uc_currency_format($this->product->price->value));
    $assert->pageTextContains('1 Item');
    $assert->pageTextContains('Total: ' . uc_currency_format($this->product->price->value));
    $assert->linkExists('View cart');
    $assert->linkExists('Checkout');
  }

  /**
   * Test hide cart when empty functionality.
   */
  public function testHiddenCartBlock() {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    $this->block->getPlugin()->setConfigurationValue('hide_empty', TRUE);
    $this->block->save();

    // Test the empty cart block.
    $this->drupalGet('');
    $assert->pageTextNotContains($this->block->label());

    // Test the cart block with an item.
    $this->addToCart($this->product);
    $this->drupalGet('');
    $assert->pageTextContains($this->block->label());
  }

  /**
   * Test show cart icon functionality.
   */
  public function testCartIcon() {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    $this->drupalGet('');
    $assert->responseContains('cart-block-icon');

    $this->block->getPlugin()->setConfigurationValue('show_image', FALSE);
    $this->block->save();

    $this->drupalGet('');
    $assert->responseNotContains('cart-block-icon');
  }

  /**
   * Test cart block collapse functionality.
   */
  public function testCartCollapse() {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    $this->drupalGet('');
    $assert->responseContains('cart-block-arrow');
    $assert->responseContains('collapsed');

    $this->block->getPlugin()->setConfigurationValue('collapsed', FALSE);
    $this->block->save();

    $this->drupalGet('');
    $assert->responseNotContains('collapsed');

    $this->block->getPlugin()->setConfigurationValue('collapsible', FALSE);
    $this->block->save();

    $this->drupalGet('');
    $assert->responseNotContains('cart-block-arrow');
  }

}
