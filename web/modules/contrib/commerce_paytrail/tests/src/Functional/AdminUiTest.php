<?php

namespace Drupal\Tests\commerce_paytrail\Functional;

use Behat\Mink\Element\NodeElement;
use Drupal\commerce_payment\Entity\PaymentGateway;
use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;

/**
 * Provides tests for admin ui.
 *
 * @group commerce_paytrail.
 */
class AdminUiTest extends CommerceBrowserTestBase {

  public static $modules = [
    'commerce_paytrail',
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
   * Test payment gateway editing.
   */
  public function testPaymentMethodSave() {
    /** @var \Drupal\commerce_payment\Entity\PaymentGateway $gateway */
    $gateway = PaymentGateway::create([
      'id' => 'paytrail',
      'label' => 'Paytrail',
      'plugin' => 'paytrail',
    ]);
    $gateway->getPlugin()->setConfiguration([
      'culture' => 'automatic',
      'merchant_id' => '13466',
      'merchant_hash' => '6pKF4jkv97zmqBJ3ZL8gUw5DfT2NMQ',
      'bypass_mode' => TRUE,
    ]);
    $gateway->save();

    $this->drupalGet('admin/commerce/config/payment-gateways/manage/paytrail');
    $this->assertSession()->statusCodeEquals(200);

    // By default, we should have 27 payment methods enabled.
    $buttons = $this->assertPaymentButtons(27);

    foreach ($buttons as $index => $button) {
      // Make sure we can disable buttons.
      if ($index > 3) {
        $button->uncheck();
      }
    }
    $this->getSession()->getPage()->pressButton('Save');
    // We should have 4 buttons enabled now.
    $buttons = $this->assertPaymentButtons(4);

    // Re-enable 25th payment method and save the form.
    $buttons[25]->check();
    $this->getSession()->getPage()->pressButton('Save');

    // Now that we re-enabled one more payment method, we should have total of
    // 5 payment methods enabled.
    $this->assertPaymentButtons(5);
  }

  /**
   * Asserts that we have expected amount of buttons enabled.
   *
   * @param int $expected
   *   The expected number of buttons that should be enabled.
   *
   * @return \Behat\Mink\Element\NodeElement[]
   *   The buttons.
   */
  public function assertPaymentButtons(int $expected) {
    $this->drupalGet('admin/commerce/config/payment-gateways/manage/paytrail');
    $buttons = $this->getSession()->getPage()->findAll('css', '#edit-configuration-paytrail-visible-methods input');

    $checked = array_filter($buttons, function (NodeElement $button) {
      return $button->isChecked();
    });
    $this->assertEquals($expected, count($checked));

    return $buttons;
  }

}
