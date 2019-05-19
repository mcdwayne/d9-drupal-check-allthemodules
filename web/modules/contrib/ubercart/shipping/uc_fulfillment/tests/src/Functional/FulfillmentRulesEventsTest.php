<?php

namespace Drupal\Tests\uc_fulfillment\Functional;

use Drupal\rules\Context\ContextConfig;

/**
 * Tests the one event that uc_fulfillment provides for use in Rules module.
 *
 * @group ubercart
 */
class FulfillmentRulesEventsTest extends FulfillmentTestBase {

  /**
   * Additional modules required.
   *
   * @var string[]
   */
  public static $modules = ['rules'];

  /**
   * Reaction Rules entity storage.
   *
   * @var \Drupal\rules\Entity\ReactionRuleStorage
   */
  protected $rulesStorage;

  /**
   * The Rules expression manager.
   *
   * @var \Drupal\rules\Engine\ExpressionManager
   */
  protected $expressionManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // All of the events we're testing are or can be initiated
    // by an administrator's actions.
    $this->drupalLogin($this->adminUser);

    $this->rulesStorage = $this->container->get('entity_type.manager')->getStorage('rules_reaction_rule');
    $this->expressionManager = $this->container->get('plugin.manager.rules_expression');
  }

  /**
   * Tests the one event provided by uc_fulfillment.
   */
  public function testRulesEvents() {
    // Create one reaction rule for each event that uc_fulfillment triggers.
    $rule_data = [
      1 => ['uc_fulfillment_shipment_save', 'A shipment is saved'],
    ];
    foreach ($rule_data as $i => list($event_name, $description)) {
      $rule[$i] = $this->expressionManager->createRule();
      $message[$i] = 'RULES message ' . $i . ': ' . $description;
      $rule[$i]->addAction('rules_system_message', ContextConfig::create()
        ->setValue('message', $message[$i])
        ->setValue('type', 'status')
      );
      $config_entity = $this->rulesStorage->create([
        'id' => 'rule' . $i,
        'events' => [['event_name' => $event_name]],
        'expression' => $rule[$i]->getConfiguration(),
      ]);
      $config_entity->save();
    }

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

    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    // Select product and create one package.
    $this->drupalPostForm(
      'admin/store/orders/' . $order->id() . '/packages',
      ['shipping_types[small_package][table][' . $order->id() . '][checked]' => 1],
      'Create one package'
    );

    // Select all packages and make shipment using the default "Manual" method.
    $this->drupalPostForm(
      'admin/store/orders/' . $order->id() . '/shipments/new',
      ['shipping_types[small_package][table][' . $order->id() . '][checked]' => 1],
      'Ship packages'
    );

    // Make the shipment.
    // This should trigger the uc_fulfillment_shipment_save event.
    $edit = $this->populateShipmentForm();
    $this->drupalPostForm(NULL, $edit, 'Save shipment');

    // Check for message triggered by the event.
    $assert->pageTextContains($message[1], '"' . $message[1] . '" IS shown');
  }

}
