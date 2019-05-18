<?php

namespace Drupal\Tests\commerce_avatax\Kernel;

use Drupal\commerce_order\Adjustment;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_product\Entity\ProductVariationType;
use Drupal\commerce_tax\Entity\TaxType;
use Drupal\Component\Serialization\Json;
use Drupal\profile\Entity\Profile;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;

/**
 * Tests the tax type plugin.
 *
 * @group commerce_avatax
 */
class TaxTypePluginTest extends CommerceKernelTestBase {

  /**
   * The config.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The tax type plugin.
   *
   * @var \Drupal\commerce_tax\Plugin\Commerce\TaxType\TaxTypeInterface
   */
  protected $plugin;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'entity_reference_revisions',
    'path',
    'profile',
    'state_machine',
    'commerce_product',
    'commerce_order',
    'commerce_tax',
    'commerce_avatax',
  ];

  /**
   * A sample order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('profile');
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_order_item');
    $this->installEntitySchema('commerce_product');
    $this->installEntitySchema('commerce_product_variation');
    $this->installConfig(['commerce_product', 'commerce_order', 'commerce_avatax']);

    $user = $this->createUser(['mail' => $this->randomString() . '@example.com']);

    // Turn off title generation to allow explicit values to be used.
    $variation_type = ProductVariationType::load('default');
    $variation_type->setGenerateTitle(FALSE);
    $variation_type->save();

    $product = Product::create([
      'type' => 'default',
      'title' => 'Default testing product',
    ]);
    $product->save();

    $variation1 = ProductVariation::create([
      'type' => 'default',
      'sku' => 'TEST_' . strtolower($this->randomMachineName()),
      'title' => $this->randomString(),
      'status' => 1,
      'price' => new Price('12.00', 'USD'),
    ]);
    $variation1->save();
    $product->addVariation($variation1)->save();

    $profile = Profile::create([
      'type' => 'customer',
      'address' => [
        'country_code' => 'US',
        'postal_code' => '53177',
        'locality' => 'Milwaukee',
        'address_line1' => 'Pabst Blue Ribbon Dr',
        'administrative_area' => 'WI',
        'given_name' => 'Frederick',
        'family_name' => 'Pabst',
      ],
      'uid' => $user->id(),
    ]);
    $profile->save();
    $profile = $this->reloadEntity($profile);

    /** @var \Drupal\commerce_order\Entity\Order $order */
    $order = Order::create([
      'type' => 'default',
      'state' => 'draft',
      'mail' => $user->getEmail(),
      'uid' => $user->id(),
      'ip_address' => '127.0.0.1',
      'order_number' => '6',
      'billing_profile' => $profile,
      'store_id' => $this->store->id(),
    ]);
    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
    $order_item = OrderItem::create([
      'type' => 'default',
      'quantity' => 1,
      'unit_price' => new Price('12.00', 'USD'),
      'purchased_entity' => $variation1,
    ]);
    $order_item->save();
    $order->addItem($order_item);
    $order->save();
    $this->order = $this->reloadEntity($order);
    $tax_type = TaxType::load('avatax');
    $this->plugin = $tax_type->getPlugin();
    $this->config = $this->config('commerce_avatax.settings');
  }

  /**
   * Tests the default configuration assertions.
   *
   * Need to ensure DEFAULT is the company code.
   */
  public function testPluginConfiguration() {
    $config = $this->config->get();
    unset($config['_core']);
    $this->assertEquals([
      'account_id' => '',
      'api_mode' => 'development',
      'company_code' => 'DEFAULT',
      'customer_code_field' => 'mail',
      'disable_commit' => FALSE,
      'disable_tax_calculation' => FALSE,
      'license_key' => '',
      'logging' => FALSE,
      'shipping_tax_code' => 'FR020100',
    ], $config);
  }

  /**
   * Assert that the request array doesn't have "lines" when billing is empty.
   */
  public function testNoLinesWhenBillingEmpty() {
    $avatax_lib = $this->container->get('commerce_avatax.avatax_lib');
    $this->order->get('billing_profile')->setValue(NULL);
    $this->assertTrue($this->plugin->applies($this->order));
    $request = $avatax_lib->prepareTransactionsCreate($this->order);
    $this->assertEmpty($request['lines']);
  }

  /**
   * Tests applying.
   */
  public function testApply() {
    list($order_item) = $this->order->getItems();
    $this->mockResponse([
      new Response(200, [], Json::encode([
        'lines' => [
          [
            'lineNumber' => $order_item->uuid(),
            'tax' => 5.25,
            'details' => [
              [
                'taxName' => 'CA STATE TAX',
              ],
            ],
          ],
        ],
      ]))
    ]);
    $this->assertTrue($this->plugin->applies($this->order));
    $this->plugin->apply($this->order);
    $adjustments = $this->order->collectAdjustments();
    $this->assertCount(1, $adjustments);
    $adjustment = reset($adjustments);
    $this->assertEquals('tax', $adjustment->getType());
    $this->assertEquals('CA STATE TAX', $adjustment->getLabel());
    $this->assertEquals('avatax|avatax', $adjustment->getSourceId());
    $this->assertEquals(new Price('5.25', 'USD'), $adjustment->getAmount());

    // Disable the tax calculation and ensure the tax type plugin no longer
    // applies.
    $this->config->set('disable_tax_calculation', TRUE)->save();
    $this->assertFalse($this->plugin->applies($this->order));
  }

  /**
   * Tests that the transaction type is correct when applying the adjustment.
   *
   * @link https://developer.avalara.com/avatax/dev-guide/transactions/should-i-commit/
   */
  public function testTransactionType() {
    $this->mockResponse([
      new Response(200, [], Json::encode([
        'lines' => [
          [
            'lineNumber' => 1,
            'tax' => 5.25,
          ]
        ],
      ])),
    ], [
      function (callable $handler) {
        return function (RequestInterface $request, array $options) use ($handler) {
          $body = $request->getBody()->getContents();
          $body = Json::decode($body);
          \Drupal::state()->set('avatax_request_body', $body);
          return $handler($request, $options);
        };
      }
    ]);
    $this->plugin->apply($this->order);
    $request_body = $this->container->get('state')->get('avatax_request_body');
    $this->assertNotEmpty($request_body);
    $this->assertEquals('SalesOrder', $request_body['type'], 'Request transaction type is correct.');
  }

  /**
   * Tests that a transaction is committed when an order is placed.
   */
  public function testCommitTransaction() {
    $this->mockResponse([
      new Response(200, [], Json::encode([
        'lines' => [
          [
            'lineNumber' => 1,
            'tax' => 5.25,
          ]
        ],
      ])),
      new Response(200, [], Json::encode([
        'lines' => [
          [
            'lineNumber' => 1,
            'tax' => 5.25,
          ],
        ],
      ]))
    ], [
      function (callable $handler) {
        return function (RequestInterface $request, array $options) use ($handler) {
          $count = \Drupal::state()->get('avatax_request_count', 0);
          $count++;
          \Drupal::state()->set('avatax_request_count', $count);
          if ($count == 2) {
            $body = $request->getBody()->getContents();
            $body = Json::decode($body);
            \Drupal::state()->set('avatax_commit_request_body', $body);
          }
          return $handler($request, $options);
        };
      }
    ]);
    $this->plugin->apply($this->order);
    $adjustments = $this->order->collectAdjustments();
    $this->assertCount(1, $adjustments);
    $transition = $this->order->getState()->getTransitions()['place'];
    $this->order->getState()->applyTransition($transition);
    $this->order->save();
    $this->assertEquals(2, $this->container->get('state')->get('avatax_request_count'));
    $body = $this->container->get('state')->get('avatax_commit_request_body');
    $this->assertEquals('SalesInvoice', $body['type']);
    $this->assertTrue($body['commit']);
  }


  /**
   * Tests that a transaction is not committed when configured to skip.
   */
  public function testDisableCommitTransaction() {
    $this->config->set('disable_commit', TRUE);
    $this->config->save();
    $this->mockResponse([
      new Response(200, [], Json::encode([
        'lines' => [
          [
            'lineNumber' => 1,
            'tax' => 5.25,
          ]
        ],
      ])),
      new Response(200, [], Json::encode([
        'lines' => [
          [
            'lineNumber' => 1,
            'tax' => 5.25,
          ]
        ],
      ])),
    ], [
      function (callable $handler) {
        return function (RequestInterface $request, array $options) use ($handler) {
          $count = \Drupal::state()->get('avatax_request_count', 0);
          $count++;
          \Drupal::state()->set('avatax_request_count', $count);
          return $handler($request, $options);
        };
      }
    ]);
    $this->plugin->apply($this->order);
    $adjustments = $this->order->collectAdjustments();
    $this->assertCount(1, $adjustments);
    $transition = $this->order->getState()->getTransitions()['place'];
    $this->order->getState()->applyTransition($transition);

    $this->order->save();
    $this->assertEquals(1, $this->container->get('state')->get('avatax_request_count'));
  }

  /**
   * Tests the case code resolver.
   */
  public function testTaxCodeResolver() {
    $avatax_lib = $this->container->get('commerce_avatax.avatax_lib');
    $request_body =$avatax_lib->prepareTransactionsCreate($this->order);
    $this->assertNull($request_body['lines'][0]['taxCode']);
    $variation2 = ProductVariation::create([
      'type' => 'default',
      'sku' => 'TEST_' . strtolower($this->randomMachineName()),
      'title' => $this->randomString(),
      'status' => 1,
      'price' => new Price('12.00', 'USD'),
      'avatax_tax_code' => 'TESTCODE123',
    ]);
    $variation2->save();
    $order_item = OrderItem::create([
      'type' => 'default',
      'quantity' => 1,
      'unit_price' => new Price('12.00', 'USD'),
      'purchased_entity' => $variation2,
    ]);
    $order_item->save();
    $this->order->addItem($order_item);
    $this->order->save();
    $request_body = $avatax_lib->prepareTransactionsCreate($this->order);
    $this->assertNull($request_body['lines'][0]['taxCode']);
    $this->assertEquals('TESTCODE123', $request_body['lines'][1]['taxCode']);
  }

  /**
   * Tests that order adjustments are correctly sent.
   */
  public function testOrderAdjustments() {
    $this->order->addAdjustment(new Adjustment([
      'type' => 'custom',
      'label' => 'Custom adjustment',
      'amount' => new Price('4.00', 'USD'),
      'source_id' => '1',
    ]));
    $this->order->save();
    $order_items = $this->order->getItems();
    $order_item = reset($order_items);
    $lines = [
      [
        'lineNumber' => $order_item->uuid(),
        'tax' => 5.25,
      ],
      [
        'lineNumber' => 2,
        'tax' => 2.00,
      ],
    ];
    $this->mockResponse([
      new Response(200, [], Json::encode([
        'lines' => $lines,
      ]))
    ]);
    $this->plugin->apply($this->order);
    $adjustments = $this->order->collectAdjustments();

    $tax_adjustments = [];
    $tax_adjustment_total = NULL;
    foreach ($adjustments as $adjustment) {
      if ($adjustment->getType() !== 'tax') {
        continue;
      }
      $tax_adjustments[] = $adjustment;
      $this->assertEquals('Sales tax', $adjustment->getLabel());
      $this->assertEquals('avatax|avatax', $adjustment->getSourceId());
      $tax_adjustment_total = $tax_adjustment_total ? $tax_adjustment_total->add($adjustment->getAmount()) : $adjustment->getAmount();
    }
    $this->assertCount(2, $tax_adjustments);
    $this->assertEquals(new Price('7.25', 'USD'), $tax_adjustment_total);
  }

  /**
   * Test that the correct customerCode is sent for anonymous users.
   */
  public function testCustomerCodeAnonymous() {
    $avatax_lib = $this->container->get('commerce_avatax.avatax_lib');
    $this->order->setEmail('');
    $request_body = $avatax_lib->prepareTransactionsCreate($this->order);
    $this->assertEquals('anonymous-' . $this->order->id(), $request_body['customerCode']);
  }

  /**
   * Test that the email is sent as the customerCode by default.
   */
  public function testCustomerCodeEmail() {
    $avatax_lib = $this->container->get('commerce_avatax.avatax_lib');
    $request_body = $avatax_lib->prepareTransactionsCreate($this->order);
    $this->assertEquals($this->order->getEmail(), $request_body['customerCode']);
  }

  /**
   * Test that the uid is sent as the customerCode when configured to do so.
   */
  public function testCustomerCodeUid() {
    $this->config->set('customer_code_field', 'uid');
    $this->config->save();
    $avatax_lib = $this->container->get('commerce_avatax.avatax_lib');
    $request_body = $avatax_lib->prepareTransactionsCreate($this->order);
    $this->assertEquals($this->order->getCustomerId(), $request_body['customerCode']);
  }

  /**
   * Test that the tax exemption type|number are correctly sent.
   */
  public function testTaxExemptions() {
    $avatax_lib = $this->container->get('commerce_avatax.avatax_lib');
    $request_body = $avatax_lib->prepareTransactionsCreate($this->order);
    $this->assertArrayNotHasKey('ExemptionNo', $request_body);
    $this->assertArrayNotHasKey('CustomerUsageType', $request_body);
    $customer = $this->order->getCustomer();
    $customer->set('avatax_tax_exemption_number', 'XX');
    $customer->save();

    $request_body = $avatax_lib->prepareTransactionsCreate($this->order);
    $this->assertArrayHasKey('ExemptionNo', $request_body);
    $this->assertEquals('XX', $request_body['ExemptionNo']);
    $this->assertArrayNotHasKey('CustomerUsageType', $request_body);

    $customer->set('avatax_tax_exemption_type', 'A');
    $customer->save();
    $request_body = $avatax_lib->prepareTransactionsCreate($this->order);
    $this->assertArrayHasKey('CustomerUsageType', $request_body);
    $this->assertEquals('A', $request_body['CustomerUsageType']);
  }

  /**
   * Test that the request is correctly cached.
   */
  public function testRequestCaching() {
    $response = [
      'lines' => [
        [
          'lineNumber' => 1,
          'tax' => 5.25,
        ]
      ],
    ];
    $this->mockResponse([
      new Response(200, [], Json::encode($response))
    ], [
      function (callable $handler) {
        return function (RequestInterface $request, array $options) use ($handler) {
          $count = \Drupal::state()->get('avatax_request_count', 0);
          $count++;
          \Drupal::state()->set('avatax_request_count', $count);
          return $handler($request, $options);
        };
      }
    ]);
    $this->assertTrue($this->plugin->applies($this->order));
    $this->plugin->apply($this->order);
    $cached_data = \Drupal::cache('commerce_avatax')->get('transactions_create:' . $this->order->id())->data;
    $this->assertEquals($response, $cached_data['response']);
    $this->plugin->apply($this->order);
    $this->assertEquals(1, $this->container->get('state')->get('avatax_request_count'));

    $order_items = $this->order->getItems();
    $order_items[0]->setQuantity(2);
    $order_items[0]->save();
    $this->plugin->apply($this->order);
    $this->assertEquals(2, $this->container->get('state')->get('avatax_request_count'));
  }

  /**
   * Test voiding a transaction.
   */
  public function testVoidTransaction() {
    $this->mockResponse([
      new Response(200, [], Json::encode([
        'lines' => [
          [
            'lineNumber' => 1,
            'tax' => 5.25,
          ]
        ],
      ])),
      new Response(200, [], Json::encode([
        'lines' => [
          [
            'lineNumber' => 1,
            'tax' => 5.25,
          ]
        ],
      ])),
      new Response(200, [], Json::encode([
        'lines' => [
          [
            'lineNumber' => 1,
            'tax' => 5.25,
          ]
        ],
      ])),
    ], [
      function (callable $handler) {
        return function (RequestInterface $request, array $options) use ($handler) {
          $count = \Drupal::state()->get('avatax_request_count', 0);
          $count++;
          \Drupal::state()->set('avatax_request_count', $count);
          $body = $request->getBody()->getContents();
          \Drupal::state()->set('avatax_request_body_' . $count, Json::decode($body));
          return $handler($request, $options);
        };
      }
    ]);
    $this->plugin->apply($this->order);
    $transition = $this->order->getState()->getTransitions()['cancel'];
    $this->order->getState()->applyTransition($transition);
    $this->order->save();
    $this->assertEquals(2, $this->container->get('state')->get('avatax_request_count'));
    $request_body = $this->container->get('state')->get('avatax_request_body_2');
    $this->assertEquals(['code' => 'DocVoided'], $request_body);
    $this->order->removeItem($this->order->getItems()[0]);
    $this->order->delete();
    $this->assertEquals(3, $this->container->get('state')->get('avatax_request_count'));
    $request_body = $this->container->get('state')->get('avatax_request_body_3');
    $this->assertEquals(['code' => 'DocVoided'], $request_body);
  }

  /**
   * Mock responses.
   *
   * @param \Psr\Http\Message\ResponseInterface[] $responses
   *   An array of mocked responses.
   * @param callable[] $middlewares
   *   An array of callable handlers.
   *
   * @throws \Exception
   */
  protected function mockResponse(array $responses = [], array $middlewares = []) {
    $avatax_lib = $this->container->get('commerce_avatax.avatax_lib');
    $mock_handler = new MockHandler($responses);
    $mock_handler_stack = HandlerStack::create($mock_handler);
    foreach ($middlewares as $middleware) {
      $mock_handler_stack->push($middleware);
    }
    $mock_client = new Client([
      'handler' => $mock_handler_stack,
      'base_uri' => 'https://sandbox-rest.avatax.com/',
      'headers' => [
        'Authorization' => 'Basic ' . base64_encode($this->config->get('account_id') . ':' . $this->config->get('license_key')),
        'Content-Type' => 'application/json',
        'x-Avalara-UID' => 'a0o33000003waOC',
        'x-Avalara-Client' => 'Test Client',
      ],
    ]);
    $avatax_lib->setClient($mock_client);
  }

}
