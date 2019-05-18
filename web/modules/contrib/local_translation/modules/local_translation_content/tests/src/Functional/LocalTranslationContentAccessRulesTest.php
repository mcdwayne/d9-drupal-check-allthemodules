<?php

namespace Drupal\Tests\local_translation_content\Functional;

use Drupal\local_translation_content\LocalTranslationContentTestsTrait;
use Drupal\local_translation_content\Plugin\LocalTranslationAccessRulesInterface;
use Drupal\local_translation_content\Plugin\LocalTranslationAccessRulesPluginManager;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * Class LocalTranslationContentAccessRulesTest.
 *
 * @package Drupal\Tests\local_translation_content\Functional
 *
 * @group local_translation_content
 */
class LocalTranslationContentAccessRulesTest extends BrowserTestBase {
  use LocalTranslationContentTestsTrait;

  /**
   * {@inheritdoc}
   */
  public $profile = 'standard';
  /**
   * {@inheritdoc}
   */
  public static $modules = ['local_translation_content'];
  /**
   * Access rules manager.
   *
   * @var \Drupal\local_translation_content\Plugin\LocalTranslationAccessRulesPluginManager
   */
  protected $accessRulesManager;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->accessRulesManager = $this->container
      ->get('plugin_manager.local_translation_content_access_rules');
  }

  /**
   * Test access rules manager existence.
   */
  public function testAccessRulesManagerExistence() {
    $this->assertTrue($this->container->has('plugin_manager.local_translation_content_access_rules'));
    $this->assertInstanceOf(LocalTranslationAccessRulesPluginManager::class, $this->accessRulesManager);
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
      $this->assertInstanceOf(LocalTranslationAccessRulesInterface::class, $instance);
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
