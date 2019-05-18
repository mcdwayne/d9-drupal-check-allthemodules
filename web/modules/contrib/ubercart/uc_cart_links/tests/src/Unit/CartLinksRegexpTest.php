<?php

namespace Drupal\Tests\uc_cart_links\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;
use Drupal\uc_cart_links\CartLinksValidator;

/**
 * @coversDefaultClass \Drupal\uc_cart_links\CartLinksValidator
 *
 * @group ubercart
 */
class CartLinksRegexpTest extends UnitTestCase {

  /**
   * The Drupal service container.
   *
   * @var \Drupal\Core\DependencyInjection\Container
   */
  protected $container;

  /**
   * The mocked expression manager object.
   *
   * @var \Drupal\uc_cart_links\CartLinksValidatorInterface
   */
  protected $cartLinksValidator;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $container = new ContainerBuilder();

    $this->messenger = new TestMessenger();
    $this->cartLinksValidator = new CartLinksValidator($this->messenger);

    $container->set('uc_cart_links.validator', $this->cartLinksValidator);
    $container->set('messenger', $this->messenger);

    \Drupal::setContainer($container);
    $this->container = $container;
  }

  /**
   * Tests that isValidSyntax() throws an exception when the status is locked.
   *
   * @covers ::isValidSyntax
   */
  public function testIsValidSyntax() {
    $links = [
      '/cart/add/p23',
      '/cart/add/p23_q5',
      '/cart/add/p23_q5-p18_q2',
      '/cart/add/e-p23_q5-m15-m32',
      '/cart/add/e-p23_q5_a12o5_a19o9_a1oA%20Text%20String_s-ispecialoffer-m77?destination=/cart/checkout',
    ];

    // Test valid links.
    foreach ($links as $link) {
      $this->assertTrue($this->cartLinksValidator->isValidSyntax($link, FALSE));
    }

    $links = [
      '/cart/add/q23',
    ];

    // Test invalid links.
    foreach ($links as $link) {
      $this->assertFalse($this->cartLinksValidator->isValidSyntax($link, FALSE));
    }
  }

}
