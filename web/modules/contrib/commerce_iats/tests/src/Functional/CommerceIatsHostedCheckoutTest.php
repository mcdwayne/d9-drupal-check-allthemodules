<?php

namespace Drupal\Tests\commerce_iats\Functional;

use Drupal\commerce_payment\Entity\PaymentGateway;
use Drupal\commerce_store\StoreCreationTrait;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests checkout using the iATS hosted form payment processor.
 *
 * @group commerce_iats
 */
class CommerceIatsHostedCheckoutTest extends BrowserTestBase {

  use StoreCreationTrait;

  /**
   * The product.
   *
   * @var \Drupal\commerce_product\Entity\ProductInterface
   */
  protected $product;

  /**
   * The store entity.
   *
   * @var \Drupal\commerce_store\Entity\Store
   */
  protected $store;

  /**
   * Modules to enable.
   *
   * @var string[]
   */
  protected static $modules = [
    'commerce_iats',
    'commerce_iats_test',
    'commerce_cart',
    'commerce_checkout',
    'commerce_product',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->setUpGateway();
    $this->store = $this->createStore();

    $variation = $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
      'price' => [
        'number' => 9.99,
        'currency_code' => 'USD',
      ],
    ]);

    /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
    $this->product = $this->createEntity('commerce_product', [
      'type' => 'default',
      'title' => 'My product',
      'variations' => [$variation],
      'stores' => [$this->store],
    ]);
  }

  /**
   * Tests checkout with the hosted form.
   */
  public function testHostedFormCheckout() {
    // Get product in cart and begin checkout.
    $this->drupalGet($this->product->toUrl()->toString());
    $this->submitForm([], 'Add to cart');
    $this->drupalGet(Url::fromRoute('commerce_cart.page'));
    $this->submitForm([], 'Checkout');
    $this->submitForm([], 'Continue as Guest');

    // Analyze the hosted form placeholder.
    $el = $this->cssSelect('#checkout-embed');
    $this->assertCount(1, $el, 'One hosted form placeholder found.');
    $el = reset($el);
    $this->assertEquals('123456', $el->getAttribute('data-transcenter'), 'Transaction center set.');
    $this->assertEquals('987654', $el->getAttribute('data-processor'), 'Processor set.');
    $this->assertEquals('Vault', $el->getAttribute('data-type'), 'Type set.');
    $this->assertEquals('Card', $el->getAttribute('data-form'), 'Form set.');
  }

  /**
   * Sets up the payment gateway.
   */
  protected function setUpGateway() {
    $payment_gateway = PaymentGateway::create([
      'label' => 'Commerce iATS CC Hosted',
      'id' => 'commerce_iats_hosted_test',
      'plugin' => 'commerce_iats_cc',
      'status' => 1,
    ]);
    $payment_gateway->setPluginConfiguration([
      'transcenter' => '123456',
      'processor' => '987654',
      'gateway_id' => '39b0eed0-4d1e-4f24-a5c2-a23a899d365e',
      'processing_type' => 'hosted',
    ]);
    $payment_gateway->save();
  }

  /**
   * Creates a new entity.
   *
   * @param string $entity_type
   *   The entity type to be created.
   * @param array $values
   *   An array of settings.
   *   Example: 'id' => 'foo'.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   A new entity.
   */
  protected function createEntity($entity_type, array $values) {
    /** @var \Drupal\Core\Entity\EntityStorageInterface $storage */
    $storage = \Drupal::service('entity_type.manager')->getStorage($entity_type);
    $entity = $storage->create($values);
    $status = $entity->save();
    $this->assertEquals(SAVED_NEW, $status, new FormattableMarkup('Created %label entity %type.', [
      '%label' => $entity->getEntityType()->getLabel(),
      '%type' => $entity->id(),
    ]));
    // The newly saved entity isn't identical to a loaded one, and would fail
    // comparisons.
    $entity = $storage->load($entity->id());

    return $entity;
  }

}
