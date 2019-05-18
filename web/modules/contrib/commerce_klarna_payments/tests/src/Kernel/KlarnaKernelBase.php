<?php

namespace Drupal\Tests\commerce_klarna_payments\Kernel;

use Drupal\commerce_payment\Entity\PaymentGateway;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Kernel test base for Klarna payments.
 */
abstract class KlarnaKernelBase extends CommerceKernelTestBase {

  public static $modules = [
    'entity_reference_revisions',
    'profile',
    'path',
    'state_machine',
    'commerce_product',
    'commerce_order',
    'commerce_payment',
    'commerce_checkout',
    'commerce_klarna_payments',
  ];

  /**
   * The payment gateway.
   *
   * @var \Drupal\commerce_payment\Entity\PaymentGateway
   */
  protected $gateway;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('profile');
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_order_item');
    $this->installEntitySchema('commerce_payment');
    $this->installEntitySchema('commerce_product');
    $this->installEntitySchema('commerce_product_variation');
    $this->installConfig(['commerce_product', 'commerce_order']);
    $this->installConfig('commerce_payment');

    $this->gateway = PaymentGateway::create([
      'id' => 'klarna_payments',
      'label' => 'Klarna',
      'plugin' => 'klarna_payments',
    ]);
    $this->gateway->save();
  }

}
