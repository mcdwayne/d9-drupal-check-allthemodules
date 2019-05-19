<?php

namespace Drupal\Tests\uc_payment\Functional;

use Drupal\rules\Context\ContextConfig;
use Drupal\Tests\uc_store\Functional\UbercartBrowserTestBase;

/**
 * Tests the one event that uc_payment provides for use in Rules module.
 *
 * @group ubercart
 */
class PaymentRulesEventsTest extends UbercartBrowserTestBase {

  /**
   * The profile to install as a basis for testing.
   *
   * @var string
   */
  protected $profile = 'testing';

  /**
   * Additional modules required.
   *
   * @var string[]
   */
  public static $modules = [
    'uc_payment',
    'uc_payment_pack',
    'uc_order',
    'rules',
  ];
  public static $adminPermissions = [
    'view payments',
    'manual payments',
    'delete payments',
  ];

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
   * Tests the one event provided by uc_payment.
   */
  public function testRulesEvents() {
    // Create one reaction rule for each event that uc_payment triggers.
    $rule_data = [
      1 => ['uc_payment_entered', 'A payment gets entered for an order'],
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

    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    // Create a payment method to use for checkout.
    $method = $this->createPaymentMethod('check');

    // Create an order to test payment events.
    $order = $this->createOrder();

    // Add a payment.
    $edit = [
      'amount' => $order->getTotal(),
      'method' => 'check',
    ];
    $this->drupalPostForm(
      'admin/store/orders/' . $order->id() . '/payments',
      $edit,
      'Record payment'
    );
    $assert->pageTextContains('Payment entered.');
    $assert->pageTextContains($message[1], '"' . $message[1] . '" IS shown');
  }

}
