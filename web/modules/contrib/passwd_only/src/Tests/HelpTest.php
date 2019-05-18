<?php

namespace Drupal\passwd_only\Tests;

/**
 * Test the help pages.
 *
 * @group passwd_only
 */
class HelpTest extends PasswdOnlyWebTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['help', 'block', 'passwd_only'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp(['help', 'block', 'passwd_only']);
    $this->user = $this->drupalCreateUser([
      'administer blocks',
      'access administration pages',
    ]);
    $this->drupalLogin($this->user);
  }

  /**
   * Test the help pages.
   */
  public function testHelp() {
    // Help overview page.
    $this->drupalGet('admin/help');
    $this->assertResponse(200);
    $this->assertText('Password Only Login');

    // Help page.
    $this->drupalGet('admin/help/passwd_only');
    $this->assertResponse(200);
    $this->assertText('First of all you have to select a Drupal user account');

    // Link 1.
    $this->clickLink('Password Only Login page');
    $this->assertResponse(200);

    // Link 2.
    $this->drupalGet('admin/help/passwd_only');
    $this->clickLink('blocks administration page');
    $this->assertResponse(200);
  }

}
