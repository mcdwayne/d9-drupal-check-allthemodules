<?php

namespace Drupal\passwd_only\Tests;

/**
 * Test the password only login block.
 *
 * @group passwd_only
 */
class BlockTest extends PasswdOnlyWebTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['block', 'passwd_only'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->user = $this->drupalCreateUser([
      'administer blocks',
      'access administration pages',
    ]);
    $this->drupalLogin($this->user);
  }

  /**
   * Test the password only login block.
   */
  public function testBlock() {
    // No block is shown.
    $this->drupalGet('');
    $this->assertNoText('Password only user login');
    $this->assertNoText('First create or set an user account, you want to use with the password only login module. Go to the admin page of the password only login module.');

    // Add the block.
    $block = $this->placeBlock('passwd_only_block', [
      'label' => 'Password only user login',
      'region' => 'header',
    ]);

    // Block is shown.
    $this->drupalGet('');
    $this->assertText('Password only user login');
    $this->assertText('First create or set an user account, you want to use with the password only login module. Go to the admin page of the password only login module.');

    // Show rendered form.
    $this->configureModule();
    $this->drupalLogout();
    $this->drupalGet('');
    $this->assertText('Some description text.');
  }

}
