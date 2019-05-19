<?php

namespace Drupal\Tests\translators_content\Functional;

use Drupal\translators_content\TranslatorsContentTestsTrait;
use Drupal\translators_content\Plugin\TranslatorsAccessRulesInterface;
use Drupal\translators_content\Plugin\TranslatorsAccessRulesPluginManager;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * Class TranslatorsContentAccessRulesTest.
 *
 * @package Drupal\Tests\translators_content\Functional
 *
 * @group translators_content
 */
class TranslatorsContentAccessRulesTest extends BrowserTestBase {
  use TranslatorsContentTestsTrait;

  /**
   * {@inheritdoc}
   */
  public $profile = 'standard';
  /**
   * {@inheritdoc}
   */
  public static $modules = ['translators_content'];
  /**
   * Access rules manager.
   *
   * @var \Drupal\translators_content\Plugin\TranslatorsAccessRulesPluginManager
   */
  protected $accessRulesManager;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->accessRulesManager = $this->container
      ->get('plugin_manager.translators_content_access_rules');
  }

  /**
   * Test access rules manager existence.
   */
  public function testAccessRulesManagerExistence() {
    $this->assertTrue($this->container->has('plugin_manager.translators_content_access_rules'));
    $this->assertInstanceOf(TranslatorsAccessRulesPluginManager::class, $this->accessRulesManager);
    $this->assertTrue(method_exists($this->accessRulesManager, 'checkAccess'));
  }

  /**
   * Test access rules operation bottlenecks.
   */
  public function testAccessRulesOperationBottlenecks() {
    $this->assertEquals(1, $this->createTestNode());
    $entity = Node::load(1);
    $this->assertInstanceOf(NodeInterface::class, $entity);
    $definitions = $this->accessRulesManager->getDefinitions();
    $this->assertNotEmpty($definitions);
    $this->assertTrue(is_array($definitions));
    // Try to find at least one rule that allowing user to access.
    foreach ($definitions as $id => $definition) {
      $instance = $this->accessRulesManager->createInstance($id, $definition);
      $this->assertInstanceOf(TranslatorsAccessRulesInterface::class, $instance);
      $this->assertTrue(method_exists($instance, 'isAllowed'));
      $this->assertFalse($instance->isAllowed('qwerty', $entity));
    }
  }

  /**
   * Test admin access.
   */
  public function testAdminAccess() {
    $user = $this->createUser([], 'test_user', FALSE);
    $this->drupalLogin($user);

    $access = $this->accessRulesManager->checkAccess('create');
    $this->assertFalse($access->isAllowed());

    // Make user admin.
    $user->addRole('administrator');
    $user->save();

    $access = $this->accessRulesManager->checkAccess('create');
    $this->assertTrue($access->isAllowed());
  }

}
