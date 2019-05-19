<?php

namespace Drupal\trance\Tests;

use Drupal\user\RoleInterface;

/**
 * Tests basic trance_access functionality.
 *
 * @group trance
 */
abstract class TranceAccessTest extends TranceTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Clear permissions for authenticated users.
    $this->config('user.role.' . RoleInterface::AUTHENTICATED_ID)->set('permissions', [])->save();
  }

  /**
   * Runs basic tests for trance_access function.
   */
  public function testTranceAccess() {
    // Ensures user without 'access content' permission can do nothing.
    $web_user1 = $this->drupalCreateUser([
      $this->getPermission('add'),
      $this->getPermission('update'),
      $this->getPermission('delete'),
    ]);
    $trance1 = $this->drupalCreateTrance(['type' => 'trance_test']);
    $this->assertTranceCreateAccess($trance1->bundle(), FALSE, $web_user1);
    $this->assertTranceAccess([
      'view' => FALSE,
      'update' => FALSE,
      'delete' => FALSE,
    ], $trance1, $web_user1);

    // Ensures user with 'bypass trance access' permission can do everything.
    $web_user2 = $this->drupalCreateUser([$this->getPermission('bypass')]);
    $trance2 = $this->drupalCreateTrance(['type' => 'trance_test']);
    $this->assertTranceCreateAccess($trance2->bundle(), TRUE, $web_user2);
    $this->assertTranceAccess([
      'view' => TRUE,
      'update' => TRUE,
      'delete' => TRUE,
    ], $trance2, $web_user2);

    // User cannot 'view unpublished content'.
    $web_user3 = $this->drupalCreateUser([$this->getPermission('view')]);
    $trance3 = $this->drupalCreateTrance([
      'status' => 0,
      'uid' => $web_user3->id(),
    ]);
    $this->assertTranceAccess(['view' => FALSE], $trance3, $web_user3);

    // User cannot create content without permission.
    $this->assertTranceCreateAccess($trance3->bundle(), FALSE, $web_user3);

    // Tests the default access provided for a published trance.
    $trance5 = $this->drupalCreateTrance();
    $this->assertTranceAccess([
      'view' => TRUE,
      'update' => FALSE,
      'delete' => FALSE,
    ], $trance5, $web_user3);

    // Tests the "edit" and "delete" permissions.
    $web_user6 = $this->drupalCreateUser([
      $this->getPermission('view'),
      $this->getPermission('update'),
      $this->getPermission('delete'),
    ]);
    $trance6 = $this->drupalCreateTrance([
      'type' => 'trance_test',
    ]);
    $this->assertTranceAccess([
      'view' => TRUE,
      'update' => TRUE,
      'delete' => TRUE,
    ], $trance6, $web_user6);
  }

}
