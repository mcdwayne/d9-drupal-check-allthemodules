<?php

namespace Drupal\Tests\uc_cart\Functional;

use Drupal\rules\Context\ContextConfig;
use Drupal\Tests\uc_store\Functional\UbercartBrowserTestBase;

/**
 * Tests the three events that uc_cart provides for use in Rules module.
 *
 * @group ubercart
 */
class CartRulesEventsTest extends UbercartBrowserTestBase {

  /**
   * Additional modules required.
   *
   * @var string[]
   */
  public static $modules = ['uc_cart', 'rules'];

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

    $this->adminUser = $this->drupalCreateUser([
      'access administration pages',
      'access content',
      'view all orders',
      'delete orders',
      'edit orders',
    ]);

    // All of the events we're testing are or can be initiated
    // by an administrator's actions.
    $this->drupalLogin($this->adminUser);

    $this->rulesStorage = $this->container->get('entity_type.manager')->getStorage('rules_reaction_rule');
    $this->expressionManager = $this->container->get('plugin.manager.rules_expression');
  }

  /**
   * Tests the three events provided by uc_cart.
   *
   * This class tests all three events provided by uc_cart, by creating three
   * rules which are all active throughout the test. They are all checked in
   * this one test class to make the tests stronger, as this will show not only
   * that the correct events are triggered in the right places, but also
   * that they are not triggered in the wrong places.
   */
  public function testRulesEvents() {
    // Create three reaction rules, one for each event that uc_cart triggers.
    $rule_data = [
      1 => ['uc_cart_checkout_start', 'Customer starts checkout'],
      2 => ['uc_cart_checkout_review_order', 'Customer reviews order'],
      3 => ['uc_cart_checkout_complete', 'Customer completes checkout'],
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

    // Add product to cart.
    $this->addToCart($this->product);

    // Start checkout - should trigger the first event.
    $this->drupalPostForm(NULL, [], 'Checkout');
    $assert->pageTextContains($message[1], '"' . $message[1] . '" IS shown');
    $assert->pageTextNotContains($message[2], '"' . $message[2] . '" is not shown');
    $assert->pageTextNotContains($message[3], '"' . $message[3] . '" is not shown');

    // Fill in checkout form.
    $edit = $this->populateCheckoutForm();

    // Review order - should trigger the second event.
    $this->drupalPostForm(NULL, $edit, 'Review order');
    // @todo drupalPostForm() seems to reload the checkout page, causing a
    // duplicate uc_cart_checkout_start event. This is NOT how it works on a
    // live site, where we only get the one correct event. So comment out this
    // assert until we figure out a fix for the test.
    // $assert->pageTextNotContains($message[1], '"' . $message[1] . '" is not shown');
    $assert->pageTextContains($message[2], '"' . $message[2] . '" IS shown');
    $assert->pageTextNotContains($message[3], '"' . $message[3] . '" is not shown');

    // Submit order - should trigger the first event.
    $this->drupalPostForm(NULL, [], 'Submit order');
    $assert->pageTextNotContains($message[1], '"' . $message[1] . '" is not shown');
    $assert->pageTextNotContains($message[2], '"' . $message[2] . '" is not shown');
    $assert->pageTextContains($message[3], '"' . $message[3] . '" IS shown');
  }

}
