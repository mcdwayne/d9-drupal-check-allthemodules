<?php

namespace Drupal\Tests\commerce_amazon_lpa\Kernel;

use Drupal\commerce_payment\Entity\PaymentGateway;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests integration with the Payment module.
 *
 * @group commerce_amazon_lpa
 */
class PaymentIntegrationTest extends CommerceKernelTestBase {

  public static $modules = [
    'entity_reference_revisions',
    'profile',
    'state_machine',
    'commerce_order',
    'commerce_payment',
    'commerce_checkout',
    'commerce_amazon_lpa',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('commerce_order');
    $this->installConfig([
      'commerce_order',
      'commerce_payment',
      'commerce_amazon_lpa',
    ]);
  }

  /**
   * Test gateway.
   */
  public function testGatewayImmutable() {
    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $gateway */
    $gateway = PaymentGateway::load('amazon_pay');

    $user = $this->createUser([
      'administer commerce_payment_gateway',
    ]);

    $this->assertFalse($gateway->access('update', $user));
    $this->assertFalse($gateway->access('delete', $user));
  }

}
