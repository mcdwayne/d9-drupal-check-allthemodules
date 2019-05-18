<?php

namespace Drupal\Tests\bulk_invite\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Core\Test\AssertMailTrait;

/**
 * Tests that the invitations are sent.
 *
 * @group bulk_invite
 */
class SendInvitationsTest extends BrowserTestBase {

  use AssertMailTrait;

  /**
   * Admin User.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;


  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'bulk_invite',
  ];

  /**
   * Mails to test.
   *
   * @var array
   */
  protected $testMails = [
    'mail1@testmail.com' => ['mail' => 'mail1@testmail.com', 'name' => 'mail1'],
    'User <firstuser@mail.com>' => ['mail' => 'firstuser@mail.com', 'name' => 'User'],
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser([
      'administer bulk invite',
    ]);
  }

  /**
   * Tests Donation by check as logged in User.
   */
  public function testSettings() {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('/admin/config/people/bulk_invites');
    $this->assertSession()->fieldExists('Paste email addresses below');
    $mails = implode("\n", array_keys($this->testMails));
    $this->getSession()->getPage()->fillField('Paste email addresses below', $mails);
    // firstuser@mail.com (username: firstuser)
    $this->drupalPostForm(NULL, [], t('Send'));
    $this->assertSession()->pageTextContains('The invitations were sent to the following mails:');
    foreach ($this->testMails as $key => $mail) {
      $this->assertSession()->pageTextContains("{$mail['mail']} (username: {$mail['name']})");
    }
    $mails = $this->getMails();
    $this->assertTrue(count($mails) === 2);
    $this->drupalLogout();
  }

}

