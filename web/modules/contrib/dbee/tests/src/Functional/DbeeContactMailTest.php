<?php

namespace Drupal\Tests\dbee\Functional;

use Drupal\Core\Test\AssertMailTrait;

/**
 * Sending/receiving emails with the Contact module.
 *
 * Verify if sending mail via the contact form is not altered.
 *
 * @group dbee
 */
class DbeeContactMailTest extends DbeeWebTestBase {

  use AssertMailTrait;

  /**
   * Sender user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $senderUser;

  /**
   * Recipient user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $recipientUser;

  /**
   * Modules to enable Do not enable the dbee module yet.
   *
   * @var array
   */
  protected static $modules = ['contact', 'dbee'];

  /**
   * Create users with appropriate permissions.
   *
   * {@inheritdoc}
   */
  public function setUp() {
    // Enable any modules required for the test.
    parent::setUp();
    $this->recipientUser = $this->drupalCreateUser();
    $this->senderUser = $this->drupalCreateUser(['access user contact forms']);
  }

  /**
   * Test sending email from user contact page.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testContactMail() {
    $this->drupalLogin($this->senderUser);
    $contact_uri = "user/{$this->recipientUser->id()}/contact";
    $this->drupalGet($contact_uri);
    $session = $this->assertSession();
    // Sender's email address is available on the contact form page.
    $session->pageTextContains($this->senderUser->getEmail());
    $edit = [
      'subject[0][value]' => $this->randomString(20),
      'message[0][value]' => $this->randomString(128),
    ];
    $this->drupalPostForm($contact_uri, $edit, 'Send message');
    $session->responseContains('Your message has been sent.');
    $this->assertMail('to', $this->recipientUser->getEmail(), 'The recipient email address is valid');
    $this->assertMail('reply-to', $this->senderUser->getEmail(), 'The sender email address is valid');
  }

}
