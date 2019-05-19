<?php

namespace Drupal\Tests\uc_product\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\rules\Context\ContextConfig;
use Drupal\Tests\uc_product\Traits\ProductTestTrait;

/**
 * Tests the one event that uc_product provides for use in Rules module.
 *
 * @group ubercart
 */
class ProductRulesEventsTest extends BrowserTestBase {
  use ProductTestTrait;

  /**
   * Additional modules required.
   *
   * @var string[]
   */
  public static $modules = ['uc_product', 'rules', 'views'];

  /**
   * Don't check for or validate config schema.
   *
   * @var bool
   */
  protected $strictConfigSchema = FALSE;

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
      'administer content types',
    ]);

    // All of the events we're testing are or can be initiated
    // by an administrator's actions.
    $this->drupalLogin($this->adminUser);

    $this->rulesStorage = $this->container->get('entity_type.manager')->getStorage('rules_reaction_rule');
    $this->expressionManager = $this->container->get('plugin.manager.rules_expression');
  }

  /**
   * Tests the event provided by uc_product.
   */
  public function testRulesEvents() {
    // Create a reaction rules for each event that uc_product triggers.
    $rule_data = [
      1 => ['uc_product_load', 'A product is being loaded'],
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

    $product1 = $this->createProduct();
    /** @var \Drupal\node\Entity\NodeType $class */
    $class = $this->createProductClass();
    $product2 = $this->createProduct(['type' => $class->id()]);

    // View product - should trigger the event.
    $this->drupalGet('node/' . $product1->id());
    $assert->pageTextContains($message[1], '"' . $message[1] . '" IS shown');

    // View product class - should also trigger the event.
    $this->drupalGet('node/' . $product2->id());
    $assert->pageTextContains($message[1], '"' . $message[1] . '" IS shown');
  }

}
