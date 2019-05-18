<?php

namespace Drupal\Tests\replicate_actions\Functional;

use Drupal\Core\Cache\Cache;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\AssertHelperTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the UI functionality.
 *
 * @group replicate
 */
class ReplicateActionsTest extends BrowserTestBase {

  use AssertHelperTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'replicate',
    'replicate_ui',
    'replicate_actions',
    'node',
    'block',
  ];

  /**
   * The user's object.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * The nodes's object.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * Initial setup for testing.
   *
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->user = $this->drupalCreateUser([
      'bypass node access',
      'administer nodes',
      'replicate entities',
    ]);
    $node_type = NodeType::create([
      'type' => 'page',
    ]);
    $node_type->save();
    $this->node = Node::create([
      'title' => $this->randomMachineName(8),
      'type' => 'page',
    ]);
    $this->node->save();

    $this->placeBlock('local_tasks_block');
    $this->placeBlock('system_messages_block');
    \Drupal::configFactory()
      ->getEditable('replicate_ui.settings')
      ->set('entity_types', ['node'])
      ->save();
    \Drupal::service('router.builder')->rebuild();
    Cache::invalidateTags(['entity_types']);
  }

  /**
   * Test redirect and the node's status.
   */
  public function testFunctionality() {

    $this->drupalLogin($this->user);
    $node_storage = $this->container->get('entity.manager')->getStorage('node');

    // Verify the node published.
    $this->assertTrue($this->node->isPublished(), 'Node is published now.');

    $this->drupalGet($this->node->toUrl()
      ->toString(TRUE)
      ->getGeneratedUrl());

    $this->getSession()->getPage()->clickLink('Replicate');
    $this->assertEquals(200, $this->getSession()
      ->getDriver()
      ->getStatusCode());
    $this->getSession()->getPage()->pressButton('Replicate');

    // Verify the user was redirected to /node/*/edit.
    $this->assertSession()
      ->responseContains('Edit<span class="visually-hidden">(active tab)</span>');

    // Verify the new replicated node is unpublished.
    $node = $node_storage->load($this->node->id() + 1);
    $this->assertFalse($node->isPublished(), 'Node is unpublished now.');

  }

}
