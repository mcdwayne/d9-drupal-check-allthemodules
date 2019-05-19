<?php

namespace Drupal\Tests\testtools\Functional;

use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\BrowserTestBase;
use Drupal\testtools\AccountList;
use Drupal\testtools\PermissionMatrix;
use Drupal\testtools\PermissionMatrixInterface;
use Drupal\testtools\PermissionTestTrait;

/**
 * Tests PermissionTestTrait.
 */
class PermissionTestTraitTest extends BrowserTestBase {

  use PermissionTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'testtools',
    'node',
    'block',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('system_breadcrumb_block');
  }

  /**
   * {@inheritdoc}
   */
  protected function getPermissionMatrix(): PermissionMatrixInterface {
    /** @var \Drupal\node\NodeTypeInterface $type */
    $type = NodeType::create([
      'name' => $this->getRandomGenerator()->name(),
      'type' => $this->randomMachineName(),
      'description' => $this->getRandomGenerator()->paragraphs(1),
    ]);
    $type->save();

    /** @var \Drupal\node\NodeInterface $node */
    $node = Node::create([
      'type' => $type->id(),
      'title' => $this->getRandomGenerator()->name(),
    ]);
    $node->save();

    $node_route_parameters = ['node' => $node->id()];
    return (new PermissionMatrix(
      (new AccountList())
        ->addAnonymous()
        ->addRoot($this->rootUser)
    ))
      ->assertForbiddenFor($this->assertEntityAccess($node, 'view'))
      ->assert($this->assertEntityAccess($node, 'update', 'delete', 'create'), FALSE, TRUE)
      ->assertAllowedFor($this->assertRouteAccess('entity.node.edit_form', $node_route_parameters), 'root')
      ->assert($this->assertPageAccessible('entity.node.canonical', $node_route_parameters), TRUE, TRUE)
      ->assertForbiddenFor($this->assertLinkExistsOnPage('entity.node.canonical', $node_route_parameters, 'Edit'), 'anon')
      ->assert($this->assertEntityCreateAccess('user', 'user'), FALSE, TRUE)
      ->assert($this->assertEntityFieldAccess($node, 'uid', 'view'), TRUE, TRUE)
      ->assert($this->assertEntityFieldAccess($node, 'uid', 'edit', 'delete'), FALSE, TRUE);
  }

}
