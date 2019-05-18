<?php

namespace Drupal\past_db\Tests;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Session\AccountInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\past_db\Entity\PastEvent;
use Drupal\user\Entity\User;
use Drupal\views\Views;

/**
 * Tests for database backend of the Past module.
 *
 * @group past
 */
class PastDBTest extends PastDBTestBase {

  public static $modules = [
    'views',
    'past',
    'past_db',
    'field_ui',
    'views_ui',
    'block',
    'entity_reference',
  ];

  /**
   * A user with admin permissions.
   *
   * @var AccountInterface
   */
  protected $admin;

  /**
   * A user with the 'view past reports' permission.
   *
   * @var AccountInterface
   */
  protected $viewUser;

  /**
   * Creates an administrator user and sample events.
   */
  public function setUp() {
    parent::setUp();
    $this->admin = $this->drupalCreateUser([
      'administer past',
      'administer past_event display',
      'administer past_event fields',
    ]);
    $this->drupalLogin($this->admin);
    $this->viewUser = $this->drupalCreateUser([
      'view past reports',
      'access site reports',
      'administer views',
    ]);
    $this->createEvents();
    $this->drupalPlaceBlock('local_tasks_block');
  }

  /**
   * Tests event bundles.
   *
   * @todo Move to kernel test
   */
  public function testEventBundles() {
    $event_type = past_event_type_create('test_event', 'Test event');
    $event_type->save();

    $event_type = past_event_get_types('test_event');
    $this->assertEqual($event_type->label, 'Test event');
    $this->assertEqual($event_type->id, 'test_event');

    $event = past_event_create('past', 'test_event', 'test message');
    $event->type = 'test_event';
    $event->save();

    $events = $this->loadEvents();
    /** @var PastEvent $event */
    $event = array_pop($events);

    $this->assertEqual($event->bundle(), 'test_event');

    // Count the amount of all events.
    $events = count($this->loadEvents());
    // Change the timestamp of three of the events.
    $event = $this->loadEvent(40);
    $event->setTimestamp(10);
    $event->save();
    $event = $this->loadEvent(50);
    $event->setTimestamp(10);
    $event->save();
    $event = $this->loadEvent(60);
    $event->setTimestamp(10);
    $event->save();

    // Add a new argument, then save again.
    $event = $this->loadEvent(40);
    $event->addArgument('new_argument', 'Data');
    $event->save();

    // Create a new event, add an argument, save, add another argument and save
    // again.
    $event = past_event_create('past', 'resave', 'Resave test');
    $event->addArgument('first', 'First argument');
    $event->setTimestamp(10);
    $event->save();
    $event->addArgument('second', 'Second argument');
    $event->save();

    // Select argument_ids for events that are meant to be deleted to ensure
    // that data is deleted correctly.
    $connection = \Drupal::database();
    $ids = [40, 50, 60, $event->id()];
    $arguments_ids = $connection->query('SELECT argument_id FROM {past_event_argument} where event_id IN (:ids[])', [':ids[]' => $ids])->fetchCol();

    // Two times 3 arguments, once two and once 4 means 12 arguments to delete.
    $this->assertEqual(count($arguments_ids), 12);

    // Run cron and check that the amount of events has reduced.
    past_db_cron();
    $this->assertEqual($events - 3, count($this->loadEvents()));

    $leftover_argument_count = $connection->query('SELECT count(*) FROM {past_event_argument} where event_id IN (:ids[])', [':ids[]' => $ids])->fetchField();
    $this->assertEqual($leftover_argument_count, 0);

    $leftover_data_count = $connection->query('SELECT count(*) FROM {past_event_data} where argument_id IN (:arguments[])', [':arguments[]' => $arguments_ids])->fetchField();
    $this->assertEqual($leftover_data_count, 0);
  }

  /**
   * Tests event extra fields display.
   */
  public function testEventExtraFields() {
    // Check for default bundle.
    $this->drupalGet('admin/config/development/past-types');
    $this->assertText('Default', 'Default bundle was found.');

    // Check for extra fields display on default bundle.
    $this->drupalGet('admin/config/development/past-types/manage/past_event/display');
    $this->assertText(t('Message'));
    $this->assertText(t('Module'));
    $this->assertText(t('Machine name'));
    $this->assertText(t('Event time'));
    $this->assertText(t('User'));
    $this->assertText(t('Arguments'));

    // Add new bundle.
    $edit = [
      'label' => 'Test bundle',
      'id' => 'test_bundle',
    ];
    $this->drupalPostForm('admin/config/development/past-types/add', $edit, t('Save'));
    $this->assertText(t('Machine name: @name', ['@name' => $edit['id']]), 'Created bundle was found.');

    // Check for extra fields display on newly created bundle.
    $this->drupalGet('admin/config/development/past-types/manage/' . $edit['id'] . '/display');
    $this->assertText(t('Message'));
    $this->assertText(t('Module'));
    $this->assertText(t('Machine name'));
    $this->assertText(t('Event time'));
    $this->assertText(t('User'));
    $this->assertText(t('Arguments'));

    // Create event of newly created type.
    $values = [
      'bundle' => $edit['id'],
      'message' => 'testmessage',
      'module' => 'testmodule',
      'machine_name' => 'testmachinename',
    ];
    /* @var PastEvent $event */
    $event = entity_create('past_event', $values);
    $event->save();

    $this->drupalLogin($this->viewUser);
    $this->drupalGet('admin/reports/past/' . $event->id());
    $this->assertText($values['message']);
    $this->assertText($values['module']);
    $this->assertText($values['machine_name']);
  }

  /**
   * Test fieldability.
   */
  public function testFieldability() {
    // Add new bundle.
    $bundle = 'test_bundle';
    $edit = [
      'label' => 'Test bundle',
      'id' => $bundle,
    ];
    $this->drupalPostForm('admin/config/development/past-types/add', $edit, t('Save'));

    // Create an entity reference field on the bundle.
    $field_instance = $this->addField($bundle);
    // Check if the field shows up in field config of the bundle.
    $this->drupalGet('admin/config/development/past-types/manage/' . $bundle . '/fields');
    $this->assertText($field_instance->label());
    $this->assertText($field_instance->getName());
    $this->assertText(t('Entity reference'));

    // Create an event that we can reference to.
    $referenced_event_message = 'Referenced Event Test message';
    $referenced_event = past_event_create('past_db', 'test_referenced_event', $referenced_event_message);
    $referenced_event->save();

    // Create an event of the bundle.
    $values = [
      'message' => 'testmessage',
      'module' => 'testmodule',
      'machine_name' => 'testmachinename',
      'type' => $bundle,
      $field_instance->getName() => $referenced_event->id(),
    ];
    /* @var PastEvent $event */
    $event = entity_create('past_event', $values);
    $event->save();

    // Check whether the bundle was saved correct.
    $event = entity_load('past_event', $event->id());
    $this->assertEqual($event->type->target_id, $bundle, 'Created event uses test bundle.');

    $this->drupalLogin($this->viewUser);
    // Check if the created fields shows up on the event display.
    $this->drupalGet('admin/reports/past/' . $event->id());
    // Check field label display.
    $this->assertText($field_instance->label());
    // Check field value display.
    $this->assertText($referenced_event_message);
  }

  /**
   * Tests Past Event views.
   */
  public function testViews() {
    $this->drupalLogin($this->viewUser);
    // Load past event views.
    $past_event_view = Views::getView('past_event_log');
    $past_event_extended_view = Views::getView('past_event_log_key_ext_search');
    $past_event_view_title = $past_event_view->getTitle();
    $past_event_extended_view_title = $past_event_extended_view->getTitle();

    // Go to Reports.
    $this->drupalGet('admin/reports');
    $this->assertText(t('Reports of the past events.'));
    $this->clickLink($past_event_view_title);
    // Assert there are two tabs.
    $this->assertLink($past_event_extended_view_title);
    $this->assertLink($past_event_view_title);

    // Delete the past event view.
    $this->drupalPostForm('admin/structure/views/view/past_event_log/delete', [], t('Delete'));
    $this->drupalGet('admin/reports/past');
    $this->assertResponse(404);

    $this->drupalGet('admin/reports');
    // Assert that extended view's title is in the menu.
    $this->assertNoLink($past_event_view_title);
    $this->clickLink($past_event_extended_view_title);
    $this->assertNoLink($past_event_view_title);
    $this->assertNoLink($past_event_extended_view_title);

    // Delete the past event extended search view.
    $this->drupalPostForm('admin/structure/views/view/past_event_log_key_ext_search/delete', [], t('Delete'));
    $this->drupalGet('admin/reports/past/extended');
    $this->assertResponse(404);

    // Assert there are no past event related menu tabs.
    $this->drupalGet('admin/reports');
    $this->assertNoLink($past_event_view_title);
    $this->assertNoLink($past_event_extended_view_title);
  }

  /**
   * Tests the Past event log UI.
   */
  public function testAdminUI() {
    $this->drupalLogin($this->viewUser);
    // Open the event log.
    $this->drupalGet('admin/reports/past');

    // Check for some messages.
    $this->assertText($this->event_desc . 100);
    $this->assertText($this->event_desc . 99);
    $this->assertText($this->event_desc . 98);
    $this->assertText($this->event_desc . 51);

    // Check severities.
    $this->assertText($this->severities[RfcLogLevel::DEBUG]);
    $this->assertText($this->severities[RfcLogLevel::INFO]);
    $this->assertText($this->severities[RfcLogLevel::WARNING]);

    // Test if we have correct classes for severities.
    $class_names = past_db_severity_css_classes_map();
    $i = 0;
    /* @var SimpleXMLElement $row */
    foreach ($this->xpath('//table[contains(@class, @views-table)]/tbody/tr') as $row) {
      // Testing first 10 should be enough.
      if ($i > 9) {
        break;
      }
      $event_id = trim($row->td);
      $event = $this->events[$event_id];
      $class_name = $class_names[$event->getSeverity()];
      $attributes = $row->attributes();
      $this->assertTrue(strpos($attributes['class'], $class_name) !== FALSE);
      $i++;
    }

    // Check machine name.
    $this->assertText($this->machine_name);

    // Check for the exposed filter fields.
    $this->assertFieldByName('module', '');
    $this->assertFieldByName('severity', 'All');
    $this->assertFieldByName('machine_name', '');
    $this->assertFieldByName('message', '');

    // Check paging.
    $this->assertText('next ›');
    $this->assertText('last »');

    // Open the 2nd page.
    $options = [
      'query' => [
        'module' => '',
        'message' => '',
        'page' => 1,
      ],
    ];
    $this->drupalGet('admin/reports/past', $options);

    // Check for some messages.
    $this->assertText($this->event_desc . 50);
    $this->assertText($this->event_desc . 49);
    $this->assertText($this->event_desc . 1);

    // Check paging.
    $this->assertText('‹ previous');
    $this->assertText('« first');

    // Go to the first detail page.
    $this->drupalGet('admin/reports/past/1');

    $this->assertText($this->machine_name);
    $this->assertText($this->event_desc . 1);
    $this->assertText('Referer');
    $this->assertLink('http://example.com/test-referer');
    $this->assertText('Location');
    $this->assertLink('http://example.com/this-url-gets-heavy-long/testtesttesttesttesttesttesttesttesttesttesttesttesttesttesttest-testtesttesttesttesttesttesttesttesttesttesttesttesttesttesttest-testtesttesttesttesttesttesttesttesttesttesttesttesttesttesttest-testtesttesttes…');
    $this->assertText('arg1');
    $this->assertText('arg2');
    $this->assertText('arg3');
    $this->assertText('First Argument');

    // Check events with a float argument.
    $event = past_event_save('past_db', 'float_test', 'Float test', ['float' => 3.14], ['session_id' => 'test_session_id']);
    $this->drupalGet('admin/reports/past/' . $event->id());
    $this->assertText('Float test');
    $this->assertText('3.14');

    // Check the actor dropbutton.
    $this->assertLink($this->viewUser->getDisplayName());

    // Check filters in Past event log.
    $this->drupalGet('admin/reports/past', [
      'query' => [
        'message' => $this->event_desc . 1,
      ],
    ]);
    $this->assertFalse(empty($this->xpath('//tbody/tr[12]/td[5]/a[contains(., "' . $this->event_desc . 1 . '")]')), 'Filtered by message.');
    $this->assertTrue(empty($this->xpath('//tbody/tr[12]/td[5]/a[contains(., "' . $this->event_desc . 2 . '")]')), 'Filtered by message.');

    // Check dropbutton and user filter.
    /** @var PastEvent $loaded */
    $loaded = PastEvent::load(1);
    /** @var AccountInterface $account */
    $account = User::load($loaded->getUid());
    $this->clickLink('Trace: ' . $account->getDisplayName());
    $this->assertUrl('admin/reports/past', [
      'query' => [
        'uid' => $account->getDisplayName(),
      ],
    ]);
    $this->assertFieldByXPath('//*[@id="edit-uid"]/@value', $account->getDisplayName(), 'User filter set correctly.');
    $this->assertText($account->getDisplayName());
    $this->assertNoText($this->viewUser->getDisplayName());

    // Check dropbutton and session filter.
    $this->clickLink('Trace session: ' . Unicode::truncate($loaded->getSessionId(), 10, FALSE, TRUE));
    $this->assertUrl('admin/reports/past', [
      'query' => [
        'session_id' => $loaded->getSessionId(),
      ],
    ]);
    $this->assertFieldByXPath('//*[@id="edit-session-id"]/@value', $loaded->getSessionId(), 'Session filter set correctly.');
    $this->assertRaw($loaded->getSessionId());
    $this->assertNoRaw($event->getSessionId());

    // Check filters in Past event log (extended Search).
    $this->drupalGet('admin/reports/past/extended', [
      'query' => [
        'name_argument' => 'float',
        'value_data' => '3.14',
      ],
    ]);
    $this->assertFalse(empty($this->xpath('//tbody/tr[1]/td[1][contains(., "101")]')), 'Filtered by message.');
    $this->assertTrue(empty($this->xpath('//tbody/tr[1]/td[1][contains(., "100")]')), 'Filtered by message.');

    // Check dropbutton and user filter.
    $this->clickLink('Trace: ' . $this->viewUser->getDisplayName());
    $this->assertUrl('admin/reports/past/extended', [
      'query' => [
        'uid' => $this->viewUser->getDisplayName(),
      ],
    ]);
    $this->assertFieldByXPath('//*[@id="edit-uid"]/@value', $this->viewUser->getDisplayName(), 'User filter set correctly.');
    $this->assertLink($this->viewUser->getDisplayName());
    $this->assertNoText($account->getDisplayName());

    $this->drupalLogout();

    // Check permissions for detail page.
    $this->drupalGet('admin/reports/past/1');
    $this->assertText(t('You are not authorized to access this page'));
    // Check permissions for event log.
    $this->drupalGet('admin/reports/past');
    $this->assertText(t('You are not authorized to access this page'));
  }

  /**
   * Creates an entityreference field and adds an instance of it to a bundle.
   *
   * @param string $bundle
   *   The bundle name.
   *
   * @return \Drupal\Core\Field\FieldConfigInterface
   *   The definition of the field instance.
   */
  protected function addField($bundle) {
    $field_storage = FieldStorageConfig::create([
      'entity_type' => 'past_event',
      'field_name' => 'field_fieldtest',
      'type' => 'entity_reference',
      'settings' => [
        'target_type' => 'past_event',
      ],
    ]);
    $field_storage->save();
    $field_instance = FieldConfig::create([
      'label' => 'test entity reference',
      'field_storage' => $field_storage,
      'bundle' => $bundle,
    ]);
    $field_instance->save();
    entity_get_display('past_event', $bundle, 'default')
      ->setComponent('field_fieldtest', [
        'type' => 'entity_reference_label',
      ])
      ->save();
    return $field_instance;
  }
}
