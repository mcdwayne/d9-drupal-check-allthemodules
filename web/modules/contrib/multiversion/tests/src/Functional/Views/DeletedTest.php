<?php

namespace Drupal\Tests\multiversion\Functional\Views;

/**
 * Tests the _deleted field handler.
 *
 * @group multiversion
 */
class DeletedTest extends MultiversionTestBase {

  protected $strictConfigSchema = FALSE;

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_deleted', 'test_not_deleted'];

  /**
   * Tests the _deleted filter when _deleted == 1.
   */
  public function testDeleted() {
    $admin_user = $this->drupalCreateUser(['bypass node access']);
    $uid = $admin_user->id();
    $this->drupalLogin($admin_user);

    // Create four nodes and delete two of them.
    $node1 = $this->drupalCreateNode(['uid' => $uid]);
    $node2 = $this->drupalCreateNode(['uid' => $uid]);
    $node3 = $this->drupalCreateNode(['uid' => $uid]);
    $node3->delete();
    $node4 = $this->drupalCreateNode(['uid' => $uid]);
    $node4->delete();

    $this->drupalGet('test_deleted');
    $this->assertNoText($node1->label());
    $this->assertNoText($node2->label());
    $this->assertText($node3->label());
    $this->assertText($node4->label());
  }

  /**
   * Tests the _deleted filter when _deleted == 0.
   */
  public function testNotDeleted() {
    $admin_user = $this->drupalCreateUser(['bypass node access']);
    $uid = $admin_user->id();
    $this->drupalLogin($admin_user);

    // Create four nodes and delete two of them.
    $node1 = $this->drupalCreateNode(['uid' => $uid]);
    $node2 = $this->drupalCreateNode(['uid' => $uid]);
    $node3 = $this->drupalCreateNode(['uid' => $uid]);
    $node3->delete();
    $node4 = $this->drupalCreateNode(['uid' => $uid]);
    $node4->delete();

    $this->drupalGet('test_not_deleted');
    $this->assertText($node1->label());
    $this->assertText($node2->label());
    $this->assertNoText($node3->label());
    $this->assertNoText($node4->label());
  }

}
