<?php

namespace Drupal\Tests\uc_fulfillment\Functional;

/**
 * Tests fulfillment backend functionality.
 *
 * @group ubercart
 */
class FulfillmentTest extends FulfillmentTestBase {

  /**
   * Tests packaging and shipping a simple order with the "Manual" plugin.
   */
  public function testFulfillmentProcess() {
    // Log on as administrator to fulfill order.
    $this->drupalLogin($this->adminUser);

    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    // A payment method for the order.
    $method = $this->createPaymentMethod('other');

    // Create an anonymous, shippable order.
    $order = $this->createOrder([
      'uid' => 0,
      'payment_method' => $method['id'],
      'primary_email' => $this->randomMachineName() . '@example.org',
    ]);
    $order->products[1]->data->shippable = 1;
    $order->save();

    // Check out with the test product.
    uc_payment_enter($order->id(), 'other', $order->getTotal());

    // Check for Packages tab and Shipments tab. BOTH should
    // redirect us to $order->id()/packages/new at this point,
    // because we have no packages or shipments yet.

    // Test Packages tab.
    $this->drupalGet('admin/store/orders/' . $order->id());
    // Test presence of tab to package products.
    $assert->linkByHrefExists('admin/store/orders/' . $order->id() . '/packages');
    // Go to packages tab.
    $this->clickLink('Packages');
    $assert->statusCodeEquals(200);
    // Check redirected path.
    $assert->addressEquals('admin/store/orders/' . $order->id() . '/packages/new');
    $assert->pageTextContains(
      "This order's products have not been organized into packages.",
      'Packages tab found.'
    );

    // Test Shipments tab.
    $this->drupalGet('admin/store/orders/' . $order->id());
    // Test presence of tab to make shipments.
    $assert->linkByHrefExists('admin/store/orders/' . $order->id() . '/shipments');
    // Go to Shipments tab.
    $this->clickLink('Shipments');
    $assert->statusCodeEquals(200);
    // Check redirected path.
    $assert->addressEquals('admin/store/orders/' . $order->id() . '/packages/new');
    $assert->pageTextContains(
      "This order's products have not been organized into packages.",
      'Shipments tab found.'
    );

    // Now package the products in this order.
    $this->drupalGet('admin/store/orders/' . $order->id() . '/packages');
    $assert->pageTextContains(
      $order->products[1]->title->value,
      'Product title found.'
    );
    $assert->pageTextContains(
      $order->products[1]->model->value,
      'Product sku found.'
    );
    // Check that product is available for packaging.
    $assert->fieldValueEquals('shipping_types[small_package][table][' . $order->id() . '][checked]', '');

    // Select product and create one package.
    $this->drupalPostForm(
      NULL,
      ['shipping_types[small_package][table][' . $order->id() . '][checked]' => 1],
      'Create one package'
    );
    // Check that we're now on the package list page.
    $assert->addressEquals('admin/store/orders/' . $order->id() . '/packages');
    $assert->pageTextContains(
      $order->products[1]->qty->value . ' x ' . $order->products[1]->model->value,
      'Product quantity x SKU found.'
    );

    // Test the Shipments tab.
    $this->drupalGet('admin/store/orders/' . $order->id());
    $this->clickLink('Shipments');
    $assert->statusCodeEquals(200);
    // Check redirected path.
    $assert->addressEquals('admin/store/orders/' . $order->id() . '/shipments/new');
    $assert->pageTextContains(
      'No shipments have been made for this order.',
      'New shipments page reached.'
    );
    $assert->pageTextContains(
      $order->products[1]->qty->value . ' x ' . $order->products[1]->model->value,
      'Product quantity x SKU found.'
    );
    // Check that manual shipping method is selected.
    $assert->fieldValueEquals('method', 'manual');

    // Select all packages and make shipment using the default "Manual" method.
    $this->drupalPostForm(
      NULL,
      ['shipping_types[small_package][table][' . $order->id() . '][checked]' => 1],
      'Ship packages'
    );
    // Check that we're now on the shipment details page.
    $assert->addressEquals('admin/store/orders/' . $order->id() . '/ship?method_id=manual&0=1');
    $assert->pageTextContains(
      'Origin address',
      'Origin address pane found.'
    );
    $assert->pageTextContains(
      'Destination address',
      'Destination address pane found.'
    );
    $assert->pageTextContains(
      'Package 1',
      'Packages data pane found.'
    );
    $assert->pageTextContains(
      'Shipment data',
      'Shipment data pane found.'
    );

    // Make the shipment.
    $edit = $this->populateShipmentForm();
    $this->drupalPostForm(NULL, $edit, 'Save shipment');

    // Check that we're now on the shipments overview page.
    $assert->addressEquals('admin/store/orders/' . $order->id() . '/shipments');
    $assert->pageTextContains(
      'Shipment ID',
      'Shipment summary found.'
    );
    $assert->pageTextContains(
      '1234567890ABCD',
      'Shipment data present.'
    );

    // Check for "Tracking" order pane after this order has
    // been shipped and a tracking number entered.
    $this->drupalGet('admin/store/orders/' . $order->id());
    $assert->pageTextContains(
      'Tracking numbers:',
      'Tracking order pane found.'
    );
    $assert->pageTextContains(
      '1234567890ABCD',
      'Tracking number found.'
    );

    // Delete Order and check to see all Package/Shipment data has been removed.
  }

}
