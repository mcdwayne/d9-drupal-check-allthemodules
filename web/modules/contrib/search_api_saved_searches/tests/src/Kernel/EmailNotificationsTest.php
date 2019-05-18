<?php

namespace Drupal\Tests\search_api_saved_searches\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\search_api\Entity\Index;
use Drupal\search_api_saved_searches\Entity\SavedSearch;
use Drupal\search_api_saved_searches\Plugin\search_api_saved_searches\notification\Email;
use Drupal\Tests\search_api\Functional\ExampleContentTrait;
use Drupal\user\Entity\User;

/**
 * Tests the functionality of the "E-mail" notifications plugin.
 *
 * @group search_api_saved_searches
 * @coversDefaultClass \Drupal\search_api_saved_searches\Plugin\search_api_saved_searches\notification\Email
 */
class EmailNotificationsTest extends KernelTestBase {

  use ExampleContentTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
    'options',
    'search_api',
    'search_api_saved_searches',
    'system',
    'user',
  ];

  /**
   * The notifications plugin to test.
   *
   * @var \Drupal\search_api_saved_searches\Plugin\search_api_saved_searches\notification\Email
   */
  protected $plugin;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('entity_test_mulrev_changed');
    $this->installEntitySchema('search_api_saved_search');
    $this->installConfig(['search_api_saved_searches', 'user']);

    // Insert the anonymous user into the database.
    User::create([
      'uid' => 0,
      'name' => '',
    ])->save();

    $this->plugin = new Email([], 'email', []);
  }

  /**
   * Tests whether the plugin returns the correct field definitions used.
   *
   * @covers ::getFieldDefinitions
   */
  public function testFieldDefinitions() {
    // Make sure the correct definition is returned from the plugin.
    $fields = $this->plugin->getFieldDefinitions();
    $this->assertEquals(['mail'], array_keys($fields));
    $this->assertEquals('E-mail', $fields['mail']->getLabel());
    $this->assertFalse($fields['mail']->isBaseField());

    // Make sure the mail can be stored in a saved search correctly.
    $mail = 'test@example.net';
    $search = SavedSearch::create([
      'uid' => 0,
      'type' => 'default',
      'label' => 'Test search',
      'mail' => $mail,
    ]);
    $search->save();

    $search = SavedSearch::load($search->id());
    $this->assertEquals($mail, $search->get('mail')->value);

    // Make sure the saved search bundle field definitions include the mail.
    $fields = \Drupal::getContainer()
      ->get('entity_field.manager')
      ->getFieldDefinitions('search_api_saved_search', 'default');
    $this->assertArrayHasKey('mail', $fields);
    $this->assertEquals('E-mail', $fields['mail']->getLabel());
    $this->assertFalse($fields['mail']->isBaseField());
  }

  /**
   * Tests whether sending notifications works correctly.
   *
   * @covers ::notify
   * @covers ::getNewResultsMail
   */
  public function testNotifications() {
    $title = '[site:name]: [search-api-saved-search-results:count] new result(s) for saved search "[search-api-saved-search:label]"';
    $body = 'Hi [user:display-name],

Your saved search "[search-api-saved-search:label]" has [search-api-saved-search-results:count] new result(s):

[search-api-saved-search-results:links]
[foo:bar]

-- The [site:name] team';
    $this->plugin->setConfiguration([
      'notification' => [
        'title' => $title,
        'body' => $body,
      ],
    ]);

    $search_label = 'Test search';
    $search_mail = 'foo@example.com';
    $search = SavedSearch::create([
      'uid' => 0,
      'type' => 'default',
      'label' => $search_label,
      'mail' => $search_mail,
    ]);
    $index = Index::create([
      'datasource_settings' => [
        'entity:entity_test_mulrev_changed' => [],
      ],
    ]);
    $results = $index->query()->getResults();
    $result_count = 3;
    $results->setResultCount($result_count);

    $result_items = [];
    $result_links = [];
    $fields_helper = \Drupal::getContainer()->get('search_api.fields_helper');
    $datasource = $index->getDatasource('entity:entity_test_mulrev_changed');
    $result_labels = [
      'Busking',
      'My Darling',
      'Miguel the Matador',
    ];
    foreach ($result_labels as $i => $label) {
      $entity = $this->addTestEntity($i + 1, [
        'name' => $label,
      ]);
      $url = $entity->url('canonical', ['absolute' => TRUE]);
      $result_links[] = "- $label\n  $url";
      $result_items[] = $fields_helper->createItemFromObject($index, $entity->getTypedData(), NULL, $datasource);
    }
    $results->setResultItems($result_items);

    // Use the state system collector mail backend.
    $this->config('system.mail')
      ->set('interface.default', 'test_mail_collector')
      ->save();
    // Set the expected "From" mail address and site name.
    $from_email = 'admin@example.net';
    $site_name = 'Saved Searches Test';
    $this->config('system.site')
      ->set('name', $site_name)
      ->set('mail', $from_email)
      ->save();
    $user_name = 'Chuck Norris';
    $this->config('user.settings')
      ->set('anonymous', $user_name)
      ->save();

    $this->plugin->notify($search, $results);

    $captured_emails = \Drupal::state()->get('system.test_mail_collector');
    $mail = end($captured_emails);
    $this->assertEquals("$site_name <$from_email>", $mail['headers']['From'], 'Message is sent from the site email account.');
    $this->assertEquals($search_mail, $mail['to'], 'Message sent to correct address.');

    $replacements = [
      '[user:display-name]' => $user_name,
      '[site:name]' => $site_name,
      '[search-api-saved-search:label]' => $search_label,
      '[search-api-saved-search-results:count]' => $result_count,
      '[search-api-saved-search-results:links]' => implode("\n", $result_links),
      '[foo:bar]' => '',
    ];
    $title = strtr($title, $replacements);
    $body = strtr($body, $replacements);
    $this->assertEquals($title, $mail['subject']);
    // \Drupal\Core\Mail\MailFormatHelper::wrapMail() will prefix each line with
    // a leading space with another space. For some reason, this is also called
    // twice, so we end up with four spaces instead of two in front of the
    // result URLs. In case the double-calling is ever fixed in Core, we just
    // replace anything more than two spaces with two.
    $actual_body = trim($mail['body']);
    $actual_body = preg_replace('/ {3,}/', '  ', $actual_body);
    $this->assertEquals($body, $actual_body);
  }

}
