<?php

namespace Drupal\Tests\uc_order\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\uc_order\Traits\OrderTestTrait;

/**
 * Tests the four events that uc_order provides for use in Rules module.
 */
abstract class OrderRulesTestBase extends BrowserTestBase {
  use OrderTestTrait;

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
  public static $modules = ['uc_order', 'rules'];

  /**
   * Don't check for or validate config schema.
   *
   * @var bool
   */
  protected $strictConfigSchema = FALSE;

  /**
   * A user with administration rights.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

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
      'unconditionally delete orders',
      'edit orders',
    ]);

    // All of the events we're testing are or can be initiated
    // by an administrator's actions.
    $this->drupalLogin($this->adminUser);

    $this->rulesStorage = $this->container->get('entity_type.manager')->getStorage('rules_reaction_rule');
    $this->expressionManager = $this->container->get('plugin.manager.rules_expression');
  }

}
