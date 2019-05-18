<?php

namespace Drupal\Tests\search_api_saved_searches\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\search_api_saved_searches\Controller\SavedSearchController;
use Drupal\search_api_saved_searches\Entity\SavedSearch;
use Drupal\search_api_saved_searches\Entity\SavedSearchAccessControlHandler;
use Drupal\search_api_saved_searches\Entity\SavedSearchType;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Tests whether activation mails are sent correctly.
 *
 * @group search_api_saved_searches
 *
 * @see \Drupal\search_api_saved_searches\Plugin\search_api_saved_searches\notification\Email
 */
class EmailActivationTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'options',
    'search_api',
    'search_api_saved_searches',
    'system',
    'user',
  ];

  /**
   * The users used for this test.
   *
   * 0 is the anonymous user, 1 is an admin user, 2 is a normal registered user.
   *
   * @var \Drupal\user\Entity\User[]
   */
  protected $users = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('search_api_saved_search');
    $this->installConfig(['search_api_saved_searches', 'user']);
    $this->installSchema('system', 'sequences');

    // Create user accounts.
    $this->users[0] = User::create([
      'uid' => 0,
      'name' => '',
    ]);
    $this->users[0]->save();
    $this->users[1] = $this->createUser([SavedSearchAccessControlHandler::ADMIN_PERMISSION]);
    $this->users[2] = $this->createUser();

    // Use the state system collector mail backend.
    $this->config('system.mail')
      ->set('interface.default', 'test_mail_collector')
      ->save();

    // Set some more site settings used in the test.
    $this->config('system.site')
      ->set('name', 'Saved Searches Test')
      ->set('mail', 'admin@example.net')
      ->save();
    $this->config('user.settings')
      ->set('anonymous', 'Chuck Norris')
      ->save();

    // Add proper activation mail title and body to the default saved search
    // type.
    $title = '[user:display-name], activate your saved search "[search-api-saved-search:label]" at [site:name]';
    $body = <<<END
[user:display-name],

A saved search on [site:name] with this e-mail address was created.
To activate this saved search, click the following link:

[search-api-saved-search:activate-url]
[foo:bar]

--  [site:name] team
END;
    $type = SavedSearchType::load('default');
    $type->getNotificationPlugin('email')->setConfiguration([
      'activate' => [
        'send' => TRUE,
        'title' => $title,
        'body' => $body,
      ],
    ]);
    $type->save();

    // Report all log messages as errors.
    $logger = new TestLogger('');
    $this->container->set('logger.factory', $logger);
    $this->container->set('logger.channel.search_api_saved_searches', $logger);
  }

  /**
   * Tests whether activation mails are sent correctly.
   *
   * @param int $current_user
   *   The index in $this->users of the user to set as the current user.
   * @param int $owner
   *   The index in $this->users of the user to set as the owner of the created
   *   saved search.
   * @param string|null $mail_address
   *   The mail address to set for the saved search, or NULL to use the mail
   *   address of the owner.
   * @param bool $status
   *   The status to set for the saved search.
   * @param bool $expected_status
   *   The expected status of the saved search after saving.
   * @param bool $mail_expected
   *   Whether an activation mail is expected to be sent.
   *
   * @dataProvider activationMailDataProvider
   */
  public function testActivationMail($current_user, $owner, $mail_address, $status, $expected_status, $mail_expected) {
    $current_user = $this->users[$current_user];
    $owner = $this->users[$owner];

    $this->setCurrentUser($current_user);

    if ($mail_address === NULL) {
      $mail_address = $owner->getEmail();
    }

    $search = SavedSearch::create([
      'type' => 'default',
      'status' => $status,
      'uid' => $owner->id(),
      'label' => 'Test search 1',
      'mail' => $mail_address,
    ]);
    $this->assertEquals(SAVED_NEW, $search->save());
    $this->sendMails();

    $this->assertEquals($expected_status, $search->get('status')->value);

    $activation_url = $search->toUrl('activate', ['absolute' => TRUE])
      ->toString();
    $this->assertNotEmpty(preg_match('#/saved-search/(\d+)/activate\?token=([^&]+)$#', $activation_url, $match));
    $this->assertEquals($search->id(), $match[1]);
    $this->assertEquals(urlencode($search->getAccessToken('activate')), $match[2]);

    $captured_emails = \Drupal::state()->get('system.test_mail_collector');
    if (!$mail_expected) {
      $this->assertEmpty($captured_emails);
      return;
    }

    $this->assertNotEmpty($captured_emails);
    $mail = end($captured_emails);

    $title = '[user:display-name], activate your saved search "[search-api-saved-search:label]" at [site:name]';
    $body = <<<END
[user:display-name],

A saved search on [site:name] with this e-mail address was created.
To activate this saved search, click the following link:

[search-api-saved-search:activate-url]
[foo:bar]

--  [site:name] team
END;

    $this->assertEquals("Saved Searches Test <admin@example.net>", $mail['headers']['From'], 'Message is sent from the site email account.');
    $this->assertEquals($mail_address, $mail['to'], 'Message sent to correct address.');

    $replacements = [
      '[user:display-name]' => $owner->getDisplayName(),
      '[site:name]' => 'Saved Searches Test',
      '[search-api-saved-search:label]' => 'Test search 1',
      '[search-api-saved-search:activate-url]' => $activation_url,
      '[foo:bar]' => '',
    ];
    $title = strtr($title, $replacements);
    $body = strtr($body, $replacements);
    $this->assertEquals($title, $mail['subject']);
    $this->assertEquals($body, trim($mail['body']));
  }

  /**
   * Provides data for testActivationMail().
   *
   * @return array
   *   Arrays of call arguments for testActivationMail().
   *
   * @see \Drupal\Tests\search_api_saved_searches\Kernel\EmailActivationTest::testActivationMail()
   */
  public function activationMailDataProvider() {
    return [
      'already disabled' => [
        0,
        0,
        'foo@example.net',
        FALSE,
        FALSE,
        FALSE,
      ],
      'admin-created' => [
        1,
        0,
        'foo@example.net',
        TRUE,
        TRUE,
        FALSE,
      ],
      'own mail' => [
        2,
        2,
        NULL,
        TRUE,
        TRUE,
        FALSE,
      ],
      'other mail' => [
        2,
        2,
        'foo@example.net',
        TRUE,
        FALSE,
        TRUE,
      ],
      'anonymous user' => [
        0,
        0,
        'foo@example.net',
        TRUE,
        FALSE,
        TRUE,
      ],
    ];
  }

  /**
   * Tests that updating of saved searches is handled correctly.
   *
   * An email should only be triggered when the saved search is already active
   * and its e-mail address changes.
   */
  public function testSavedSearchUpdate() {
    $search = SavedSearch::create([
      'type' => 'default',
      'label' => 'Test search 1',
      'mail' => 'foo@example.net',
    ]);
    $this->assertEquals(SAVED_NEW, $search->save());
    $this->sendMails();

    // Assert that the search was deactivated.
    $this->assertFalse($search->get('status')->value);

    // Assert an e-mail was sent but just save it here and empty the mail
    // storage.
    $captured_emails = \Drupal::state()->get('system.test_mail_collector');
    $this->assertNotEmpty($captured_emails);
    $first_mail = reset($captured_emails);
    \Drupal::state()->delete('system.test_mail_collector');

    // Changing the mail address of the saved search at this point shouldn't
    // trigger another mail (since the saved search isn't active yet).
    $result = $search->set('mail', 'bar@example.net')->save();
    $this->sendMails();
    $this->assertEquals(SAVED_UPDATED, $result);
    $captured_emails = \Drupal::state()->get('system.test_mail_collector');
    $this->assertEmpty($captured_emails);
    $this->assertFalse($search->get('status')->value);

    // Activate the saved search.
    $controller = new SavedSearchController();
    $response = $controller->activateSearch($search, $search->getAccessToken('activate'));
    $this->assertInstanceOf(RedirectResponse::class, $response);
    $this->assertTrue($search->get('status')->value);

    // Now that the saved search is active, the mail address can't be changed
    // without activating the saved search again. (Since the token stays the
    // same, this should produce exactly the same mail as the first time –
    // except for the recipient's mail address, of course.)
    $result = $search->set('mail', 'test@example.net')->save();
    $this->sendMails();
    $this->assertEquals(SAVED_UPDATED, $result);
    $captured_emails = \Drupal::state()->get('system.test_mail_collector');
    $this->assertNotEmpty($captured_emails);
    $first_mail['to'] = 'test@example.net';
    $this->assertEquals($first_mail, reset($captured_emails));
    $this->assertFalse($search->get('status')->value);
  }

  /**
   * Tests that activation will be skipped if the "E-mail" plugin is disabled.
   */
  public function testEmailPluginDisabled() {
    $type = SavedSearchType::load('default');
    $type->removeNotificationPlugin('email');
    $type->save();

    // Save a new search.
    $search = SavedSearch::create([
      'type' => 'default',
      'label' => 'Test search 1',
    ]);
    $this->assertEquals(SAVED_NEW, $search->save());
    $this->sendMails();

    // Saved search should be active, no mail should have been sent.
    $this->assertTrue($search->get('status')->value);
    $captured_emails = \Drupal::state()->get('system.test_mail_collector');
    $this->assertEmpty($captured_emails);
  }

  /**
   * Tests that activation will be skipped if the "Send" option is disabled.
   */
  public function testActivationEmailDisabled() {
    $type = SavedSearchType::load('default');
    $type->getNotificationPlugin('email')->setConfiguration([
      'activate' => [
        'send' => FALSE,
        'title' => 'Test',
        'body' => 'Test',
      ],
    ]);
    $type->save();

    // Save a new search.
    $search = SavedSearch::create([
      'type' => 'default',
      'label' => 'Test search 1',
      'mail' => 'foo@example.net',
    ]);
    $this->assertEquals(SAVED_NEW, $search->save());
    $this->sendMails();

    // Saved search should be active, no mail should have been sent.
    $this->assertTrue($search->get('status')->value);
    $captured_emails = \Drupal::state()->get('system.test_mail_collector');
    $this->assertEmpty($captured_emails);
  }

  /**
   * Sends all queued mails.
   */
  protected function sendMails() {
    $this->container->get('search_api_saved_searches.email_queue')->destruct();
  }

}
