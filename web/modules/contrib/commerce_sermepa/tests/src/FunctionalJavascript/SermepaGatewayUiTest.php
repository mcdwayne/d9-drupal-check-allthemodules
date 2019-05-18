<?php

namespace Drupal\Tests\commerce_sermepa\FunctionalJavascript;

use Drupal\Tests\commerce\FunctionalJavascript\CommerceWebDriverTestBase;

/**
 * Tests the payment gateway UI for 'Sermepa' case.
 *
 * @group commerce_sermepa
 */
class SermepaGatewayUiTest extends CommerceWebDriverTestBase {

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
  protected function getAdministratorPermissions() {
    return array_merge([
      'administer commerce_payment_gateway',
    ], parent::getAdministratorPermissions());
  }

  /**
   * Tests creating a Sermepa payment gateway.
   */
  public function testSermepaGatewayCreation() {
    $this->drupalGet('admin/commerce/config/payment-gateways/add');
    $this->assertSession()->addressEquals('admin/commerce/config/payment-gateways/add');

    $this->assertSession()->fieldExists('label')->setValue('Sermepa');
    $this->assertSession()->waitForElementVisible('css', '.edit-label-machine-name-suffix');
    $this->assertSession()->buttonExists('Edit')->click();
    $this->assertSession()->fieldExists('id')->setValue('sermepa');

    // Select the plugin and wait for the Sermepa fields.
    $this->assertSession()->fieldExists('plugin')->setValue('commerce_sermepa');
    $this->waitForAjaxToFinish();

    $this->assertSession()->fieldExists('configuration[commerce_sermepa][mode]')->setValue('test');
    $this->assertSession()->fieldExists('configuration[commerce_sermepa][merchant_name]')->setValue('Merchant name');
    $this->assertSession()->fieldExists('configuration[commerce_sermepa][merchant_code]')->setValue('000000001');
    $this->assertSession()->fieldExists('configuration[commerce_sermepa][merchant_group]')->setValue('MG');
    $this->assertSession()->fieldExists('configuration[commerce_sermepa][merchant_password]')->setValue('00000000000000000000000000000000');
    $this->assertSession()->fieldExists('configuration[commerce_sermepa][merchant_terminal]')->setValue('001');
    $this->assertSession()->fieldExists('configuration[commerce_sermepa][merchant_paymethods][]')->setValue(['D']);
    $this->assertSession()->fieldExists('configuration[commerce_sermepa][merchant_consumer_language]')->setValue('001');
    $this->assertSession()->fieldExists('configuration[commerce_sermepa][currency]')->setValue(840);
    $this->assertSession()->fieldExists('configuration[commerce_sermepa][transaction_type]')->setValue(0);
    $this->assertSession()->fieldExists('configuration[commerce_sermepa][instructions][value]')->setValue('Instructions');
    $this->assertSession()->buttonExists('Save')->click();

    // Wait for the payment gateways collection page.
    $this->assertSession()->waitForElementVisible('css', '.commerce-payment-gateways');
    $this->assertSession()->addressEquals('admin/commerce/config/payment-gateways');
    $this->assertSession()->responseContains('Sermepa');
    $this->assertSession()->responseContains('Test');

    $payment_gateway = $this->container->get('entity_type.manager')->getStorage('commerce_payment_gateway')->load('sermepa');
    $this->assertEquals('sermepa', $payment_gateway->id());
    $this->assertEquals('Sermepa', $payment_gateway->label());
    $this->assertEquals('commerce_sermepa', $payment_gateway->getPluginId());
    $payment_gateway_plugin = $payment_gateway->getPlugin();
    $this->assertEquals('test', $payment_gateway_plugin->getMode());
    $configuration = $payment_gateway_plugin->getConfiguration();
    $this->assertEquals('Merchant name', $configuration['merchant_name']);
    $this->assertEquals('000000001', $configuration['merchant_code']);
    $this->assertEquals('MG', $configuration['merchant_group']);
    $this->assertEquals('00000000000000000000000000000000', $configuration['merchant_password']);
    $this->assertEquals('001', $configuration['merchant_terminal']);
    $this->assertEquals(['D'], $configuration['merchant_paymethods']);
    $this->assertEquals('001', $configuration['merchant_consumer_language']);
    $this->assertEquals(840, $configuration['currency']);
    $this->assertEquals(0, $configuration['transaction_type']);
    $this->assertEquals('Instructions', $configuration['instructions']['value']);
    $this->assertEquals('plain_text', $configuration['instructions']['format']);
  }

  /**
   * Tests editing a Sermepa payment gateway.
   */
  public function testSermepaGatewayEditing() {
    $values = [
      'id' => 'edit_sermepa',
      'label' => 'Edit sermepa',
      'plugin' => 'commerce_sermepa',
      'status' => 1,
    ];
    $payment_gateway = $this->createEntity('commerce_payment_gateway', $values);

    $this->drupalGet('admin/commerce/config/payment-gateways/manage/' . $payment_gateway->id());
    $this->assertSession()->fieldExists('configuration[commerce_sermepa][mode]')->setValue('test');
    $this->assertSession()->fieldExists('configuration[commerce_sermepa][merchant_name]')->setValue('Merchant name');
    $this->assertSession()->fieldExists('configuration[commerce_sermepa][merchant_code]')->setValue('000000001');
    $this->assertSession()->fieldExists('configuration[commerce_sermepa][merchant_group]')->setValue('MG');
    $this->assertSession()->fieldExists('configuration[commerce_sermepa][merchant_password]')->setValue('00000000000000000000000000000000');
    $this->assertSession()->fieldExists('configuration[commerce_sermepa][merchant_terminal]')->setValue('001');
    $this->assertSession()->fieldExists('configuration[commerce_sermepa][merchant_paymethods][]')->setValue(['D']);
    $this->assertSession()->fieldExists('configuration[commerce_sermepa][merchant_consumer_language]')->setValue('001');
    $this->assertSession()->fieldExists('configuration[commerce_sermepa][currency]')->setValue(840);
    $this->assertSession()->fieldExists('configuration[commerce_sermepa][transaction_type]')->setValue(0);
    $this->assertSession()->fieldExists('configuration[commerce_sermepa][instructions][value]')->setValue('Instructions');
    $this->assertSession()->buttonExists('Save')->click();

    // Wait for the payment gateways collection page.
    $this->assertSession()->waitForElementVisible('css', '.commerce-payment-gateways');
    $this->assertSession()->addressEquals('admin/commerce/config/payment-gateways');
    $this->assertSession()->responseContains('Edit sermepa');

    $this->container->get('entity_type.manager')->getStorage('commerce_payment_gateway')->resetCache();
    $payment_gateway = $this->container->get('entity_type.manager')->getStorage('commerce_payment_gateway')->load('edit_sermepa');
    $this->assertEquals('edit_sermepa', $payment_gateway->id());
    $this->assertEquals('Edit sermepa', $payment_gateway->label());
    $this->assertEquals('commerce_sermepa', $payment_gateway->getPluginId());
    $payment_gateway_plugin = $payment_gateway->getPlugin();
    $this->assertEquals('test', $payment_gateway_plugin->getMode());
    $configuration = $payment_gateway_plugin->getConfiguration();
    $this->assertEquals('Merchant name', $configuration['merchant_name']);
    $this->assertEquals('000000001', $configuration['merchant_code']);
    $this->assertEquals('MG', $configuration['merchant_group']);
    $this->assertEquals('00000000000000000000000000000000', $configuration['merchant_password']);
    $this->assertEquals('001', $configuration['merchant_terminal']);
    $this->assertEquals(['D'], $configuration['merchant_paymethods']);
    $this->assertEquals('001', $configuration['merchant_consumer_language']);
    $this->assertEquals(840, $configuration['currency']);
    $this->assertEquals(0, $configuration['transaction_type']);
    $this->assertEquals('Instructions', $configuration['instructions']['value']);
    $this->assertEquals('plain_text', $configuration['instructions']['format']);
  }

}
