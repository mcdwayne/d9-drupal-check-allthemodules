<?php

namespace Drupal\Tests\commerce_iats\Functional;

use Drupal\commerce_payment\Entity\PaymentGateway;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests configuring the iATS payment processor.
 *
 * @group commerce_iats
 */
class CommerceIatsSetupTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var string[]
   */
  protected static $modules = ['commerce_iats', 'commerce_iats_test'];

  /**
   * Tests setting up the hosted form processor.
   */
  public function testSetupHostedForm() {
    $adminUser = $this->drupalCreateUser(['administer commerce_payment_gateway']);
    $this->drupalLogin($adminUser);
    $this->drupalGet('admin/commerce/config/payment-gateways/add');

    $values = [
      'label' => 'Commerce iATS ACH Test',
      'id' => 'commerce_iats_ach_test',
      'plugin' => 'commerce_iats_ach',
      'configuration[commerce_iats_ach][transcenter]' => '123456',
      'configuration[commerce_iats_ach][processor]' => '987654',
      'configuration[commerce_iats_ach][gateway_id]' => '39b0eed0-4d1e-4f24-a5c2-a23a899d365e',
      'configuration[commerce_iats_ach][processing_type]' => 'direct_submission',
      'configuration[commerce_iats_ach][ach_category]' => 'Web sale',
      'status' => '1',
    ];
    $this->submitForm($values, 'Save');

    $this->assertSession()->addressEquals('admin/commerce/config/payment-gateways');
    $this->assertSession()->pageTextContains('Commerce iATS ACH Test');
    $this->assertSession()->pageTextContains('Live');
    $this->assertSession()->pageTextContains('Enabled');

    $payment_gateway = PaymentGateway::load('commerce_iats_ach_test');
    $this->assertNotEmpty($payment_gateway);

    $config = $payment_gateway->getPluginConfiguration();
    $this->assertEquals('123456', $config['transcenter']);
    $this->assertEquals('987654', $config['processor']);
    $this->assertEquals('39b0eed0-4d1e-4f24-a5c2-a23a899d365e', $config['gateway_id']);
    $this->assertEquals('direct_submission', $config['processing_type']);
    $this->assertEquals('Web sale', $config['ach_category']);
  }

}
