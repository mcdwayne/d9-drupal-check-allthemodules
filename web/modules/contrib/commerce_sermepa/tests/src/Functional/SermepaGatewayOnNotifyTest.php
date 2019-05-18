<?php

namespace Drupal\Tests\commerce_sermepa\Functional;

use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;

/**
 * Tests the payment gateway notify route for 'Sermepa' case.
 *
 * @group commerce_sermepa
 */
class SermepaGatewayOnNotifyTest extends CommerceBrowserTestBase {

  /**
   * An off-site payment gateway.
   *
   * @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface
   */
  protected $paymentGateway;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_sermepa',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    /** @var \Drupal\commerce_payment\Entity\PaymentGateway $payment_gateway */
    $this->paymentGateway = $this->createEntity('commerce_payment_gateway', [
      'id' => 'commerce_sermepa',
      'label' => 'Commerce Sermepa',
      'plugin' => 'commerce_sermepa',
    ]);
  }

  /**
   * Tests the payment gateway notify route.
   */
  public function testSermepaGatewayNotifyRuote() {
    $this->drupalGet('/payment/notify/' . $this->paymentGateway->id());
    $this->assertSession()->statusCodeEquals(200);
  }

}
