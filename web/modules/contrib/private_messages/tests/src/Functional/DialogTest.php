<?php

namespace Drupal\Tests\private_messages\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Test class for Dialog functionality.
 *
 * @group private_messages
 */
class DialogTest extends BrowserTestBase {

  protected static $modules = ['private_messages'];

  protected $user;

  protected $recipient;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->user = $this->drupalCreateUser(['use private messages']);
    $this->recipient = $this->drupalCreateUser(['use private messages']);
    $this->drupalLogin($this->user);
  }

  /**
   * Try to access dialogs page.
   *
   * @test
   */
  public function aUserCanAccessDialogsPage() {
    $session = $this->assertSession();

    $this->drupalGet(Url::fromRoute('private_messages.dialog.all', ['user' => $this->user->id()]));
    $session->statusCodeEquals(200);
  }

  /**
   * Try to access dialogs page.
   *
   * @test
   */
  public function aUserCantAccessRecipientsDialogsPage() {
    $session = $this->assertSession();

    $this->drupalGet(Url::fromRoute('private_messages.dialog.all', ['user' => $this->recipient->id()]));
    $session->statusCodeEquals(403);
  }

}
