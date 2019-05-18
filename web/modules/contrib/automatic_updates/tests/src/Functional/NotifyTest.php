<?php

namespace Drupal\Tests\automatic_updates\Functional;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Test\AssertMailTrait;
use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests notification emails for PSAs.
 *
 * @group automatic_updates
 */
class NotifyTest extends BrowserTestBase {
  use AssertMailTrait;
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'automatic_updates',
    'test_automatic_updates',
    'update',
  ];

  /**
   * A user with permission to administer site configuration.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Setup test PSA endpoint.
    $end_point = $this->buildUrl(Url::fromRoute('test_automatic_updates.json_test_controller'));
    $this->config('automatic_updates.settings')
      ->set('psa_endpoint', $end_point)
      ->save();
    // Setup a default destination email address.
    $this->config('update.settings')
      ->set('notification.emails', ['admin@example.com'])
      ->save();

    $this->user = $this->drupalCreateUser([
      'administer site configuration',
      'access administration pages',
    ]);
    $this->drupalLogin($this->user);
  }

  /**
   * Tests sending email notifications.
   */
  public function testSendMail() {
    // Test PSAs on admin pages.
    $this->drupalGet(Url::fromRoute('system.admin'));
    $this->assertSession()->pageTextContains('Critical Release - PSA-2019-02-19');

    // Email should be sent.
    $notify = $this->container->get('automatic_updates.psa_notify');
    $notify->send();
    $this->assertCount(1, $this->getMails());
    $this->assertMailString('subject', '3 urgent Drupal announcements require your attention', 1);
    $this->assertMailString('body', 'Critical Release - PSA-2019-02-19', 1);

    // No email should be sent if PSA's are disabled.
    $this->container->get('state')->set('system.test_mail_collector', []);
    $this->container->get('state')->delete('automatic_updates.last_check');
    $this->config('automatic_updates.settings')
      ->set('enable_psa', FALSE)
      ->save();
    $notify->send();
    $this->assertCount(0, $this->getMails());
  }

}
