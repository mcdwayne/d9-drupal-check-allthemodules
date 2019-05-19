<?php

namespace Drupal\Tests\whitelabel\Functional;

/**
 * Tests behavior for pages when user permissions change.
 *
 * @group whitelabel
 */
class WhiteLabelPermissionRevokeTest extends WhiteLabelTestBase {

  /**
   * Holds the site's default name (Drupal).
   *
   * @var string
   */
  private $defaultName;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'block',
    'whitelabel_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->defaultName = $this->config('system.site')->get('name');
  }

  /**
   * Test if white label is not applied once owner's permissions are revoked.
   */
  public function testOwnerPermissionsRevoked() {
    // Make sure test user can see white labels.
    $viewer = $this->drupalCreateUser(['view white label pages']);
    $this->drupalLogin($viewer);

    // Create a less privileged user and make him WL owner.
    $user = $this->drupalCreateUser();
    $this->whiteLabel
      ->setOwner($user)
      ->save();
    $this->setCurrentWhiteLabel($this->whiteLabel);

    // Assert that white label is not applied.
    $this->drupalGet('<front>');
    $this->assertSession()->elementTextContains('css', 'title', $this->defaultName);

    // Create a role to show white labels and assign to user.
    $role = $this->drupalCreateRole(['serve white label pages']);
    $user->addRole($role);
    $user->save();

    // Assert that white label is applied.
    $this->drupalGet('<front>');
    $this->assertSession()->elementTextContains('css', 'title', $this->whiteLabel->getName());

    // Remove the role again.
    $user->removeRole($role);
    $user->save();

    // Assert white label is again not applied.
    $this->drupalGet('<front>');
    $this->assertSession()->elementTextContains('css', 'title', $this->defaultName);
  }

  /**
   * Test if white label is not applied once viewer's permissions are revoked.
   */
  public function testViewerPermissionsRevoked() {
    $viewer = $this->drupalCreateUser();
    $this->drupalLogin($viewer);

    $role = $this->drupalCreateRole(['view white label pages']);

    $this->setCurrentWhiteLabel($this->whiteLabel);

    // No white label.
    $this->drupalGet('<front>');
    // Check page title.
    $this->assertSession()->elementTextContains('css', 'title', $this->defaultName);

    $viewer->addRole($role);
    $viewer->save();

    // White label 1.
    $this->drupalGet('<front>');
    // Check page title.
    $this->assertSession()->elementTextContains('css', 'title', $this->whiteLabel->getName());

    $viewer->removeRole($role);
    $viewer->save();

    // No white label.
    $this->drupalGet('<front>');
    // Check page title.
    $this->assertSession()->elementTextContains('css', 'title', $this->defaultName);
  }

}
