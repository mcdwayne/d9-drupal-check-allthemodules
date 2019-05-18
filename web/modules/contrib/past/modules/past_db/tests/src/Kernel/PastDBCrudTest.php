<?php

namespace Drupal\Tests\past_db\Kernel;

use Drupal\Core\Entity\Entity;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\past_db\Entity\PastEvent;
use Drupal\past_db\Entity\PastEventType;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests saving and loading past events and event types.
 *
 * @group past
 */
class PastDBCrudTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var string[]
   */
  public static $modules = [
    'past',
    'past_db',
    'user',
    'field',
    'options',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->installEntitySchema('past_event');
    $this->installSchema('past_db', ['past_event_argument', 'past_event_data']);
    $this->installEntitySchema('user');
  }

  /**
   * Tests saving and loading event type.
   */
  public function testEventType() {
    // Minimal event type.
    PastEventType::create([
      'id' => 'minimal',
    ])->save();
    $event_type = PastEventType::load('minimal');
    $this->assertNull($event_type->label());

    // Full event type.
    PastEventType::create([
      'id' => 'full',
      'label' => 'Full event type',
      'weight' => 5,
    ])->save();
    $event_type = PastEventType::load('full');
    $this->assertEqual($event_type->label(), 'Full event type');
    $this->assertEqual($event_type->weight, 5);
  }

  /**
   * Tests saving an event.
   */
  public function testEvent() {
    // Minimal event - test default values.
    $created = past_event_create('past_db', 'testEvent1');
    $created->save();
    /** @var PastEvent $loaded */
    $loaded = PastEvent::load($created->id());
    $this->assertEqual($loaded->getModule(), 'past_db');
    $this->assertEqual($loaded->getMachineName(), 'testEvent1');
    $this->assertEqual($loaded->bundle(), 'past_event');
    $this->assertNull($loaded->getSessionId());
    $this->assertIdentical($loaded->getLocation(), 'http://localhost/');
    $this->assertNull($loaded->getMessage());
    $this->assertEqual($loaded->getSeverity(), RfcLogLevel::INFO);
    $this->assertNotNull($loaded->getTimestamp());
    $this->assertEqual($loaded->getUid(), 0);

    // Full event - test defined values.
    $values = [
      'session_id' => $this->randomMachineName(),
      'severity' => RfcLogLevel::ERROR,
      'timestamp' => 1337,
      'uid' => 2,
      // @todo Can we set current user in a KernelTest?
    ];
    $message = $this->randomString(40);
    $created = past_event_save('past_db', 'testEvent2', $message, [], $values);
    $loaded = PastEvent::load($created->id());
    $this->assertEqual($loaded->getModule(), 'past_db');
    $this->assertEqual($loaded->getMachineName(), 'testEvent2');
    $this->assertEqual($loaded->bundle(), 'past_event');
    $this->assertEqual($loaded->getSessionId(), $values['session_id']);
    $this->assertEqual($loaded->getMessage(), $message);
    $this->assertEqual($loaded->getSeverity(), $values['severity']);
    $this->assertEqual($loaded->getTimestamp(), $values['timestamp']);
    $this->assertEqual($loaded->getUid(), $values['uid']);
  }

  /**
   * Tests deleting an event.
   */
  public function testDeleteEvent() {
    $event1 = past_event_save('past_db', 'testEvent1', 'test1', [1 => 'arg1']);
    $event2 = past_event_save('past_db', 'testEvent2', 'test2', [2 => 'arg2']);
    $event3 = past_event_save('past_db', 'testEvent3', 'test3', [3 => 'arg3']);

    // Delete one event.
    entity_delete_multiple('past_event', [$event1->id()]);

    // Check if event was deleted.
    $this->assertFalse((bool) entity_load_unchanged('past_event', $event1->id()));
    $this->assertTrue((bool) entity_load_unchanged('past_event', $event2->id()));

    // Check if arguments of this event were deleted.
    $arguments = db_query('SELECT COUNT(*) FROM {past_event_argument} WHERE event_id = :event_id', [':event_id' => $event1->id()])->fetchField();
    $this->assertEqual($arguments, '0');
    $arguments = db_query('SELECT COUNT(*) FROM {past_event_argument}')->fetchField();
    $this->assertEqual($arguments, '2');

    // Check if data of this event was deleted.
    $data = db_query('SELECT COUNT(*) FROM {past_event_data} WHERE argument_id = :argument_id', [':argument_id' => 1])->fetchField();
    $this->assertEqual($data, '0');
    $data = db_query('SELECT COUNT(*) FROM {past_event_data}')->fetchField();
    $this->assertEqual($data, '2');

    // Delete multiple.
    entity_delete_multiple('past_event', [$event2->id(), $event3->id()]);

    // Check if arguments were deleted.
    $arguments = db_query('SELECT COUNT(*) FROM {past_event_argument}')->fetchField();
    $this->assertEqual($arguments, '0');

    // Check if data was deleted.
    $data = db_query('SELECT COUNT(*) FROM {past_event_data}')->fetchField();
    $this->assertEqual($data, '0');
  }

  /**
   * Tests saving an event with an event type.
   */
  public function testUseEventType() {
    // Create a type.
    $type = PastEventType::create([
      'id' => $this->randomMachineName(),
    ]);
    $type->save();

    // Create an event of the type.
    $created = past_event_create('past_db', 'testUseEventType');
    $created->type = $type->id();
    $created->save();

    // Assert the bundle property is set.
    $loaded = PastEvent::load($created->id());
    $this->assertEqual($loaded->bundle(), $type->id());
  }

  /**
   * Tests fieldability of event types.
   */
  public function testFieldability() {
    // Create a type.
    $type = PastEventType::create([
      'id' => $this->randomMachineName(),
    ]);
    $type->save();

    // Attach a field to the type.
    $field_name = 'field_test';
    $field_storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'type' => 'string',
      'entity_type' => 'past_event',
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'entity_type' => 'past_event',
      'bundle' => $type->id(),
    ]);
    $field->save();

    // Create an event using the field.
    $field_value = $this->randomString();
    $created = past_event_create('past_db', 'testFieldability', NULL, ['type' => $type->id()]);
    $created->set($field_name, $field_value);
    $created->save();

    // Assert the field value is retrieved.
    $loaded = PastEvent::load($created->id());
    $this->assertEqual($loaded->get($field_name)->value, $field_value);
  }

  /**
   * Tests saving and loading an event with an argument.
   */
  public function testArgument() {
    // Scalar arguments.
    $this->assertArgumentPersists($this->randomString(), 'string');
    $this->assertArgumentPersists(rand(), 'int');
    $this->assertArgumentPersists(TRUE, 'bool');
    $this->assertArgumentPersists(3.14, 'float');

    // Array as argument.
    $this->assertArgumentPersists([$this->randomMachineName() => $this->randomString()], 'array');

    // Object as argument.
    $this->assertArgumentPersists($this->randomObject(), 'object');

    // @todo Recursive array as argument.

    // @todo Recursive object as argument.

    // Entity as argument.
    $this->assertArgumentPersists(past_event_create('past_db', 'testArgument'), 'entity');

    // NULL as argument.
    $this->assertArgumentPersists(NULL, 'null');
  }

  /**
   * Asserts that an argument is equal before saving and after loading.
   *
   * @param mixed $data
   *   The data to save for the argument.
   * @param string $name
   *   The key for the argument.
   */
  protected function assertArgumentPersists($data, $name) {
    /** @var PastEvent $created */
    $created = past_event_create('past_db', 'assertArgumentPersists');
    $created->addArgument($name, $data);
    $created->save();

    /** @var PastEvent $loaded */
    $loaded = PastEvent::load($created->id());

    // Assert argument and data were loaded.
    if (!$this->assertNotNull($loaded->getArgument($name), "The loaded $name argument is not null")
      || !$this->assertNotNull($loaded->getArgument($name)->getData(), "The loaded $name argument data is not null")) {
      return;
    }

    // Entities are saved with toArray() applied.
    if ($data instanceof Entity) {
      $data = $data->toArray();
    }

    $loaded_data = $loaded->getArgument($name)->getData();
    if (!$this->assertEqual(gettype($loaded_data), gettype($data))) {
      return;
    }

    // Assert and maybe debug.
    if (!is_array($data)) {
      if (!$this->assertEqual($loaded_data, $data, "The $name argument is correctly saved and retrieved")) {
        debug($data, "Original data");
        debug($loaded_data, "Loaded data");
      };
    }
    else {
      foreach (array_keys($data) as $key) {
        if (!$this->assertEqual($loaded_data[$key], $data[$key], "The $name argument's $key item is correctly saved and retrieved")) {
          debug($data[$key], "Original data $key");
          debug($loaded_data[$key], "Loaded data $key");
        };
      }
    }
  }

}
