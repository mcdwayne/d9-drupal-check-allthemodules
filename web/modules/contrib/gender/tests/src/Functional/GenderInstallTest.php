<?php

namespace Drupal\Tests\gender\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Gender module install message.
 *
 * @group gender
 */
class GenderInstallTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'user',
    'help',
    'options',
    'field',
    'text',
    'filter',
  ];

  /**
   * The user object to use in testing.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    // Create and log in the user.
    $this->user = $this->drupalCreateUser([
      'administer modules',
    ]);
    $this->drupalLogin($this->user);
  }

  /**
   * Tests that a link to the help page is displayed after install.
   */
  public function testInstallMessageExists() {
    // Load the module page.
    $this->drupalGet("/admin/modules");
    // Install the module.
    $this->getSession()->getPage()->checkField('edit-modules-gender-enable');
    $this->getSession()->getPage()->findButton('Install')->click();
    // Verify that the install message appears.
    $this->assertPattern('/consult the .{0,100}help page/');
    $this->assertLinkByHref(\Drupal::url('help.page', ['name' => 'gender']));
  }

}
