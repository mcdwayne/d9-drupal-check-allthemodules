<?php

namespace Drupal\Tests\commerce_vantiv\FunctionalJavascript;

use Drupal\commerce_payment\Entity\PaymentGateway;
use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;
use Drupal\Tests\commerce\FunctionalJavascript\JavascriptTestTrait;

/**
 * Tests the integration between payments and checkout.
 *
 * @group commerce
 */
class ConfigurationFormTest extends CommerceBrowserTestBase {

  use JavascriptTestTrait;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The product.
   *
   * @var \Drupal\commerce_product\Entity\ProductInterface
   */
  protected $product;

  /**
   * A non-reusable order payment method.
   *
   * @var \Drupal\commerce_payment\Entity\PaymentMethodInterface
   */
  protected $orderPaymentMethod;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_vantiv',
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
   * Tests creating a Vantiv onsite payment gateway via the config UI.
   */
  public function testCreateGateway() {
    $this->drupalGet('admin/commerce/config/payment-gateways');
    $this->getSession()->getPage()->clickLink('Add payment gateway');
    $this->assertSession()->addressEquals('admin/commerce/config/payment-gateways/add');
    $radio_button = $this->getSession()->getPage()->findField('Vantiv (Onsite)');
    $radio_button->click();
    $this->waitForAjaxToFinish();
    $values = [
      'plugin' => 'vantiv_onsite',
      'id' => 'vantiv_onsite_us',
      'label' => 'Vantiv Onsite US',
      'configuration[vantiv_onsite][mode]' => 'test',
      'configuration[vantiv_onsite][user]' => 'UserName',
      'configuration[vantiv_onsite][password]' => 'PassWord',
      'configuration[vantiv_onsite][currency_merchant_map][default]' => '0137147',
      'configuration[vantiv_onsite][proxy]' => 'prox://y',
      'configuration[vantiv_onsite][paypage_id]' => 'PayPageID',
      'configuration[vantiv_onsite][batch_requests_path]' => '/batch-requests-path',
      'configuration[vantiv_onsite][litle_requests_path]' => '/litle-requests-path',
      'configuration[vantiv_onsite][sftp_username]' => 'sFTP Username',
      'configuration[vantiv_onsite][sftp_password]' => 'sFTP Password',
      'configuration[vantiv_onsite][batch_url]' => 'batch://url',
      'configuration[vantiv_onsite][tcp_port]' => '3000',
      'configuration[vantiv_onsite][tcp_timeout]' => '20',
      'configuration[vantiv_onsite][tcp_ssl]' => '1',
      'configuration[vantiv_onsite][print_xml]' => '1',
      'configuration[vantiv_onsite][timeout]' => '10',
      'configuration[vantiv_onsite][report_group]' => 'eCommerce',
      'status' => '1',
    ];
    $this->submitForm($values, 'Save');
    $this->assertSession()->pageTextContains('Saved the Vantiv Onsite US payment gateway.');

    $payment_gateway = PaymentGateway::load('vantiv_onsite_us');
    $payment_gateway_plugin = $payment_gateway->getPlugin();
    $config = $payment_gateway_plugin->getConfiguration();
    $this->assertEquals('vantiv_onsite_us', $payment_gateway->id());
    $this->assertEquals('Vantiv Onsite US', $payment_gateway->label());
    $this->assertEquals('vantiv_onsite', $payment_gateway->getPluginId());
    $this->assertEquals(TRUE, $payment_gateway->status());
    $this->assertEquals('test', $payment_gateway_plugin->getMode());
    $this->assertEquals('UserName', $config['user']);
    $this->assertEquals('PassWord', $config['password']);
    $this->assertEquals('0137147', $config['currency_merchant_map']['default']);
    $this->assertEquals('prox://y', $config['proxy']);
    $this->assertEquals('PayPageID', $config['paypage_id']);
    $this->assertEquals('/batch-requests-path', $config['batch_requests_path']);
    $this->assertEquals('/litle-requests-path', $config['litle_requests_path']);
    $this->assertEquals('sFTP Username', $config['sftp_username']);
    $this->assertEquals('sFTP Password', $config['sftp_password']);
    $this->assertEquals('batch://url', $config['batch_url']);
    $this->assertEquals('3000', $config['tcp_port']);
    $this->assertEquals('20', $config['tcp_timeout']);
    $this->assertEquals('1', $config['tcp_ssl']);
    $this->assertEquals('1', $config['print_xml']);
    $this->assertEquals('10', $config['timeout']);
    $this->assertEquals('eCommerce', $config['report_group']);
  }

}
