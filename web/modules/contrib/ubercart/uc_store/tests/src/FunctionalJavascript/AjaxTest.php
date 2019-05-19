<?php

namespace Drupal\Tests\uc_store\FunctionalJavascript;

use Drupal\uc_country\Entity\Country;
use Drupal\uc_store\AjaxAttachTrait;
use Drupal\uc_quote\Entity\ShippingQuoteMethod;

/**
 * Tests Ajax updating of checkout and order pages.
 *
 * @group ubercart
 */
class AjaxTest extends UbercartJavascriptTestBase {
  use AjaxAttachTrait;

  public static $modules = [
    // 'rules_admin',
    'uc_payment',
    'uc_payment_pack',
    'uc_quote',
  ];
  public static $adminPermissions = [
    // 'administer rules',
    // 'bypass rules access',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalLogin($this->adminUser);

    // In order to test zone-based conditions, this particular test class
    // assumes that US is enabled and set as the store country.
    Country::load('US')->enable()->save();
    \Drupal::configFactory()->getEditable('uc_store.settings')->set('address.country', 'US')->save();
  }

  /**
   * Sets a zone-based condition for a particular payment method.
   *
   * @param string $method
   *   The method to set (e.g. 'check')
   * @param int $zone
   *   The zone id (numeric) to check for.
   * @param bool $negate
   *   TRUE to negate the condition.
   */
  protected function addPaymentZoneCondition($method, $zone, $negate = FALSE) {
    $not = $negate ? 'NOT ' : '';
    $name = 'uc_payment_method_' . $method;
    $label = ucfirst($method) . ' conditions';
    $condition = [
      'LABEL' => $label,
      'PLUGIN' => 'and',
      'REQUIRES' => ['rules'],
      'USES VARIABLES' => [
        'order' => [
          'label' => 'Order',
          'type' => 'uc_order',
        ],
      ],
      'AND' => [
        [
          $not . 'data_is' => [
            'data' => ['order:billing-address:zone'],
            'value' => $zone,
          ],
        ],
      ],
    ];
    $newconfig = rules_import([$name => $condition]);
    $oldconfig = rules_config_load($name);
    if ($oldconfig) {
      $newconfig->id = $oldconfig->id;
      unset($newconfig->is_new);
      $newconfig->status = ENTITY_CUSTOM;
    }
    $newconfig->save();
    entity_flush_caches();
    //$this->drupalGet('admin/config/workflow/rules/components/edit/' . $newconfig->id);
  }

  /**
   * Tests Ajax on the checkout form.
   */
  public function testCheckoutAjax() {
    /** @var \Behat\Mink\Element\DocumentElement $page */
    $page = $this->getSession()->getPage();
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    // Enable two payment methods and set a condition on one.
    $this->createPaymentMethod('check');
    // Use randomMachineName() as randomString() has escaping problems when
    // sent over Ajax; see https://www.drupal.org/node/2664320
    $other = $this->createPaymentMethod('other', ['label' => $this->randomMachineName()]);
    // $this->addPaymentZoneCondition($other['id'], 'KS');

    // Specify that the billing zone should update the payment pane.
    \Drupal::configFactory()->getEditable('uc_cart.settings')
      ->set('ajax.checkout.panes][billing][address][zone', ['payment-pane' => 'payment-pane'])
      ->save();

    // Go to the checkout page, verify that the conditional payment method
    // is not available.
    $product = $this->createProduct(['shippable' => 0]);
    $this->addToCart($product);
    // Can't set a number form element with fillField() or drupalPostForm().
    // $this->drupalPostForm('cart', ['items[0][qty]' => 1], 'Checkout');
    $this->drupalGet('cart');
    // $page->fillField('items[0][qty]',  1);
    $page->findButton('Checkout')->press();
    $assert->assertWaitOnAjaxRequest();
    // @todo Re-enable when shipping quote conditions are available.
    // $this->assertNoEscaped($other['label']);

    // Set the billing zone. This should trigger Ajax to load the payment
    // pane with the applicable payment methods for this zone. We then verify
    // that payment pane contains the method we expect.
    $page->findField('panes[billing][zone]')->selectOption('KS');
    $assert->assertWaitOnAjaxRequest();
    $field = $page->findField('panes[billing][zone]');
    $this->assertNotEmpty($field);
    $this->assertEquals($field->getValue(), 'KS');
    $assert->assertEscaped($other['label']);

    // Change the billing zone. This should trigger Ajax to change the
    // available payment options. We then verify that payment pane contains
    // the new value we expect.
    $page->findField('panes[billing][zone]')->selectOption('AL');
    $assert->assertWaitOnAjaxRequest();
    $field = $page->findField('panes[billing][zone]');
    $this->assertNotEmpty($field);
    $this->assertEquals($field->getValue(), 'AL');
    $assert->assertEscaped($other['label']);
    // Not in Kansas any more...
    // @todo Re-enable when shipping quote conditions are available.
    // $this->assertNoEscaped($other['label']);
  }

  /**
   * Tests Ajax on the checkout panes.
   */
  public function testCheckoutPaneAjax() {
    /** @var \Behat\Mink\Element\DocumentElement $page */
    $page = $this->getSession()->getPage();
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    // Create two shipping quote methods.
    $quote1 = $this->createQuote();
    $quote2 = $this->createQuote();

    // Create two unique policy messages for our two payment methods.
    // Use randomMachineName() as randomString() has escaping problems when
    // sent over Ajax; see https://www.drupal.org/node/2664320
    $policy1 = $this->randomMachineName();
    $policy2 = $this->randomMachineName();

    // Add first Cash-On-Delivery payment method.
    $payment1 = $this->createPaymentMethod('cod', ['settings[policy]' => $policy1]);

    // Add second COD method, with different policy message.
    $payment2 = $this->createPaymentMethod('cod', ['settings[policy]' => $policy2]);

    // Add a shippable product to the cart.
    $product = $this->createProduct(['shippable' => 1]);
    $this->addToCart($product);
    // Can't set a number form element with  fillField() or drupalPostForm().
    // $this->drupalPostForm('cart', ['items[0][qty]' => 1], 'Checkout');
    $this->drupalGet('cart');
    // $page->fillField('items[0][qty]',  1);
    $page->findButton('Checkout')->press();
    $assert->assertWaitOnAjaxRequest();

    //
    // Changing the payment method.
    //

    // Change the payment method to $payment1. This should trigger Ajax
    // to change the payment pane and show the correct policy message.
    $page->findField('panes[payment][payment_method]')->selectOption($payment1['id']);
    $assert->assertWaitOnAjaxRequest();
    $field = $page->findField('panes[payment][payment_method]');
    $this->assertNotEmpty($field);
    $this->assertEquals($field->getValue(), $payment1['id']);
    // Check that the payment method detail div changes.
    $assert->pageTextContains($policy1, 'After changing the payment method, the payment method policy string is updated.');

    // Now change the payment method to $payment2. This should trigger Ajax
    // to change the payment pane and show the correct policy message.
    $page->findField('panes[payment][payment_method]')->selectOption($payment2['id']);
    $assert->assertWaitOnAjaxRequest();
    $field = $page->findField('panes[payment][payment_method]');
    $this->assertNotEmpty($field);
    $this->assertEquals($field->getValue(), $payment2['id']);
    // Check that the payment method detail div changes.
    $assert->pageTextContains($policy2, 'After changing again the payment method, the payment method policy string is updated.');

    //
    // Changing the shipping method.
    //

    // Change the shipping quote to $quote1. This should trigger Ajax
    // to change the order total pane to show the quote.
    $page->findField('panes[quotes][quotes][quote_option]')->selectOption($quote1->id() . '---0');
    $assert->assertWaitOnAjaxRequest();
    $field = $page->findField('panes[quotes][quotes][quote_option]');
    $this->assertNotEmpty($field);
    $this->assertEquals($field->getValue(), $quote1->id() . '---0');

    // Check that the shipping line item in the payment pane shows the correct
    // quote method title and price.
    $this->assertEquals(
      $page->find('css', 'tr.line-item-shipping td.title')->getHtml(),
      $quote1->label() . ':'
    );
    $config = $quote1->getPluginConfiguration();
    $rate = (float) $config['base_rate'] + (float) $config['product_rate'];
    $this->assertEquals(
      $page->find('css', 'tr.line-item-shipping td.price')->getHtml(),
      uc_currency_format($rate)
    );

    // Change the shipping quote to $quote2. This should trigger Ajax
    // to change the order total pane to show the quote.
    $page->findField('panes[quotes][quotes][quote_option]')->selectOption($quote2->id() . '---0');
    $assert->assertWaitOnAjaxRequest();
    $field = $page->findField('panes[quotes][quotes][quote_option]');
    $this->assertNotEmpty($field);
    $this->assertEquals($field->getValue(), $quote2->id() . '---0');

    // Check that the shipping line item in the payment pane shows the correct
    // quote method title and price.
    $this->assertEquals(
      $page->find('css', 'tr.line-item-shipping td.title')->getHtml(),
      $quote2->label() . ':'
    );
    $config = $quote2->getPluginConfiguration();
    $rate = (float) $config['base_rate'] + (float) $config['product_rate'];
    $this->assertEquals(
      $page->find('css', 'tr.line-item-shipping td.price')->getHtml(),
      uc_currency_format($rate)
    );
  }

  /**
   * Creates a new quote.
   *
   * @param array $edit
   *   (optional) An associative array of shipping quote method fields to change
   *   from the defaults. Keys are shipping quote method field names.
   *   For example, 'plugin' => 'flatrate'.
   *
   * @return \Drupal\uc_quote\ShippingQuoteMethodInterface
   *   The created ShippingQuoteMethod object.
   */
  protected function createQuote(array $edit = []) {
    // Create a flatrate.
    $edit += [
      'id' => $this->randomMachineName(),
      'label' => $this->randomMachineName(),
      'status' => 1,
      'weight' => 0,
      'plugin' => 'flatrate',
      'settings' => [
        'base_rate' => mt_rand(1, 10),
        'product_rate' => mt_rand(1, 10),
      ],
    ];

    $method = ShippingQuoteMethod::create($edit);
    $method->save();
    return $method;
  }

}
