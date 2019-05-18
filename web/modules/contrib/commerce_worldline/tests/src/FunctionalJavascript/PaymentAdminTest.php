<?php

namespace Drupal\Tests\commerce_worldline\FunctionalJavascript;

use Drupal\commerce_payment\Entity\PaymentGateway;
use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;
use Drupal\Tests\commerce\FunctionalJavascript\JavascriptTestTrait;

/**
 * Tests the admin payment UI.
 *
 * @group commerce_worldline
 */
class PaymentAdminTest extends CommerceBrowserTestBase {

  use JavascriptTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce_worldline',
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
   * Tests creating a payment gateway through the admin UI.
   */
  public function testPaymentGatewayCreation() {
    // Add a new payment gateway.
    $this->drupalGet('admin/commerce/config/payment-gateways/add');

    // Use javascript to select the sips_payment payment plugin, needs to be
    // javascript because the form uses ajax to refresh when a payment plugin is
    // selected.
    $this->getSession()
      ->getPage()
      ->find('css', 'input[name="plugin"]')
      ->setValue('sips_payment');
    $this->waitForAjaxToFinish();

    // Create an array of values to be posted to the add form.
    $values = [
      'label' => 'Example',
      'id' => 'example',
      'plugin' => 'sips_payment',
      'configuration[sips_payment][display_label]' => 'Otter',
      'configuration[sips_payment][sips_interface_version]' => 'llama',
      'configuration[sips_payment][sips_passphrase]' => 'kitten',
      'configuration[sips_payment][sips_merchant_id]' => 'giraffe',
      'configuration[sips_payment][sips_key_version]' => 'owl',
    ];
    $this->submitForm($values, 'Save');

    // Check that we ended up on the overview.
    $this->assertSession()->addressEquals('admin/commerce/config/payment-gateways');
    $this->assertSession()->responseContains('Example');
    $this->assertSession()->responseContains('Test');

    // Edit the same gateway again; and check that the values were saved
    // correctly.
    $this->drupalGet('admin/commerce/config/payment-gateways/manage/example');
    $this->assertSession()->fieldValueEquals('configuration[sips_payment][sips_merchant_id]', 'giraffe');
    $this->assertSession()->fieldValueEquals('configuration[sips_payment][sips_passphrase]', 'kitten');
    $this->assertSession()->fieldValueEquals('configuration[sips_payment][sips_payment_method]', '');

    // Save the optional payment method field and check that has been saved
    // correctly and that the other fields haven't been emptied.
    $this->submitForm(['configuration[sips_payment][sips_payment_method]' => 'bunny'], 'Save');
    $this->drupalGet('admin/commerce/config/payment-gateways/manage/example');
    $this->assertSession()->fieldValueEquals('configuration[sips_payment][sips_merchant_id]', 'giraffe');
    $this->assertSession()->fieldValueEquals('configuration[sips_payment][sips_passphrase]', 'kitten');
    $this->assertSession()->fieldValueEquals('configuration[sips_payment][sips_payment_method]', 'bunny');

    // Load the payment gateway through the API.
    $payment_gateway = PaymentGateway::load('example');
    $this->assertEquals('example', $payment_gateway->id());
    $this->assertEquals('Example', $payment_gateway->label());
    $this->assertEquals('sips_payment', $payment_gateway->getPluginId());
    $this->assertTrue($payment_gateway->status());

    // Load the plugin from the gateway and check it's configuration.
    $payment_gateway_plugin = $payment_gateway->getPlugin();
    $this->assertEquals('0', $payment_gateway_plugin->getMode());
    $this->assertEquals('Otter', $payment_gateway_plugin->getDisplayLabel());

    // Load the configuration of the gateway plugin and check that the values we
    // set earlier are here.
    $configuration = $payment_gateway_plugin->getConfiguration();
    $this->assertEquals('llama', $configuration['sips_interface_version']);
    $this->assertEquals('kitten', $configuration['sips_passphrase']);
    $this->assertEquals('giraffe', $configuration['sips_merchant_id']);
    $this->assertEquals('owl', $configuration['sips_key_version']);
    $this->assertEquals('bunny', $configuration['sips_payment_method']);
  }

}
