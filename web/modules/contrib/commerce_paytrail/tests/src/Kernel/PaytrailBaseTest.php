<?php

namespace Drupal\Tests\commerce_paytrail\Kernel;

use Drupal\commerce_payment\Entity\PaymentGateway;
use Drupal\commerce_paytrail\Plugin\Commerce\PaymentGateway\PaytrailBase;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * PaytrailBaseTest unit tests.
 *
 * @group commerce_paytrail
 * @coversDefaultClass \Drupal\commerce_paytrail\Plugin\Commerce\PaymentGateway\PaytrailBase
 */
class PaytrailBaseTest extends CommerceKernelTestBase {

  /**
   * The paytrail base.
   *
   * @var \Drupal\commerce_paytrail\Plugin\Commerce\PaymentGateway\PaytrailBase
   */
  protected $sut;

  /**
   * The payment gateway.
   *
   * @var \Drupal\commerce_payment\Entity\PaymentGateway
   */
  protected $gateway;

  public static $modules = [
    'language',
    'state_machine',
    'address',
    'profile',
    'entity_reference_revisions',
    'commerce_order',
    'commerce_payment',
    'commerce_paytrail',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->installEntitySchema('profile');
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_order_item');
    $this->installEntitySchema('commerce_payment');
    $this->installEntitySchema('commerce_payment_method');
    $this->installConfig('commerce_order');
    $this->installConfig('commerce_payment');
    $this->installConfig('commerce_paytrail');

    $this->gateway = PaymentGateway::create([
      'id' => 'paytrail',
      'label' => 'Paytrail',
      'plugin' => 'paytrail',
    ]);
    $this->gateway->getPlugin()->setConfiguration([
      'culture' => 'automatic',
      'merchant_id' => '13466',
      'merchant_hash' => '6pKF4jkv97zmqBJ3ZL8gUw5DfT2NMQ',
      'bypass_mode' => FALSE,
    ]);
    $this->gateway->save();

    $this->sut = $this->gateway->getPlugin();

    foreach (['fi', 'de'] as $langcode) {
      $language = $this->container->get('entity.manager')->getStorage('configurable_language')->create([
        'id' => $langcode,
      ]);
      $language->save();
    }
  }

  /**
   * Make sure test mode fallbacks to test credentials.
   *
   * @covers ::getMerchantId
   * @covers ::getMerchantHash
   * @covers ::defaultConfiguration
   * @covers ::__construct
   */
  public function testTestMode() {
    $this->assertEquals(PaytrailBase::MERCHANT_HASH, $this->sut->getMerchantHash());

    $this->sut->setConfiguration([
      'mode' => 'test',
      'merchant_id' => '123',
      'merchant_hash' => '321',
    ] + $this->sut->getConfiguration());
    // Make sure merchant hash and id stays the same when using test mode.
    $this->assertEquals('test', $this->sut->getMode());
    $this->assertEquals(PaytrailBase::MERCHANT_HASH, $this->sut->getMerchantHash());
    $this->assertEquals(PaytrailBase::MERCHANT_ID, $this->sut->getMerchantId());

    // Make sure merchant id does not fallback to the test credentials
    // when using live mode.
    $this->sut->setConfiguration(['mode' => 'live'] + $this->sut->getConfiguration());
    $this->assertEquals('live', $this->sut->getMode());
    $this->assertEquals('321', $this->sut->getMerchantHash());
    $this->assertEquals('123', $this->sut->getMerchantId());
  }

  /**
   * Make sure culture fallback works.
   *
   * @covers ::__construct
   * @covers ::getCulture
   * @covers ::defaultConfiguration
   */
  public function testCulture() {
    $this->assertEquals(PaytrailBase::MERCHANT_HASH, $this->sut->getMerchantHash());

    \Drupal::configFactory()->getEditable('system.site')->set('default_langcode', 'fi')->save();

    $this->sut->setConfiguration(['culture' => 'automatic'] + $this->sut->getConfiguration());
    // Make sure auto detection works.
    $this->assertEquals('fi_FI', $this->sut->getCulture());

    \Drupal::configFactory()->getEditable('system.site')->set('default_langcode', 'de')->save();

    // Make sure auto fallback works when using an unknown language.
    $this->assertEquals('en_US', $this->sut->getCulture());

    // Make sure manually set culture works.
    $this->sut->setConfiguration(['culture' => 'sv_SE'] + $this->sut->getConfiguration());
    $this->assertEquals('sv_SE', $this->sut->getCulture());
  }

  /**
   * Tests visible methods.
   *
   * @covers ::getVisibleMethods
   * @covers ::defaultConfiguration
   */
  public function testGetVisibleMethods() {
    // Make sure every payment method is enabled by default.
    $methods = $this->sut->getVisibleMethods();
    $this->assertTrue(count($methods) === 27);
    $methods = $this->sut->getVisibleMethods(FALSE);
    $this->assertTrue(count($methods) === 27);

    foreach ($methods as $method) {
      if ($method->id() > 5) {
        $method->setStatus(FALSE)->save();
      }
    }
    // Make sure we have 4 methods enabled now.
    $methods = $this->sut->getVisibleMethods();
    $this->assertTrue(count($methods) === 4);
    // Make sure we get all payment methods when querying for all payment
    // methods.
    $methods = $this->sut->getVisibleMethods(FALSE);
    $this->assertTrue(count($methods) === 27);
  }

  /**
   * Tests isDataIncluded().
   *
   * @covers ::isDataIncluded
   * @covers ::defaultConfiguration
   */
  public function testDataIncluded() {
    // Make sure both, product and payer details are enabled by default.
    foreach ([PaytrailBase::PAYER_DETAILS, PaytrailBase::PRODUCT_DETAILS] as $type) {
      $this->assertTrue($this->sut->isDataIncluded($type));
    }

    $this->sut->setConfiguration([
      'included_data' => [
        PaytrailBase::PRODUCT_DETAILS => 0,
        PaytrailBase::PAYER_DETAILS => 0,
      ],
    ] + $this->sut->getConfiguration());

    // Make sure details get disabled accordingly.
    foreach ([PaytrailBase::PAYER_DETAILS, PaytrailBase::PRODUCT_DETAILS] as $type) {
      $this->assertFalse($this->sut->isDataIncluded($type));
    }
  }

  /**
   * Tests isBypassModeEnabled()
   *
   * @covers ::isBypassModeEnabled
   * @covers ::defaultConfiguration
   */
  public function testIsByPassModeEnabled() {
    $this->assertFalse($this->sut->isBypassModeEnabled());
    $this->sut->setConfiguration(['bypass_mode' => TRUE] + $this->sut->getConfiguration());
    $this->assertTrue($this->sut->isBypassModeEnabled());
  }

}
