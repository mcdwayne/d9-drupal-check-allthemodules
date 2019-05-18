<?php

namespace Drupal\Tests\commerce_oci_checkout\Unit;

use Drupal\commerce_cart\CartSessionInterface;
use Drupal\commerce_oci_checkout\CartProvider;
use Drupal\commerce_store\CurrentStoreInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;

/**
 * Class ConstructorTest.
 *
 * @group commerce_oci_checkout
 */
class ConstructorTest extends \PHPUnit_Framework_TestCase {

  /**
   * Test that we can construct the thing, so it does not break on updates.
   */
  public function testConstructor() {
    $mock_etm = $this->createMock(EntityTypeManagerInterface::class);
    $mock_current_store = $this->createMock(CurrentStoreInterface::class);
    $mock_account = $this->createMock(AccountInterface::class);
    $mock_cart = $this->createMock(CartSessionInterface::class);
    $mock_attr = $this->createMock(AttributeBagInterface::class);
    // Now try to construct our class, and then we just assert something, to
    // make sure the test runs.
    new CartProvider($mock_etm, $mock_current_store, $mock_account, $mock_cart, $mock_attr);
    $this->assertEquals(TRUE, TRUE);
  }

}
