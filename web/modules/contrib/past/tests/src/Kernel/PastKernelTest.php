<?php

namespace Drupal\Tests\past\Kernel;

use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Site\Settings;
use Drupal\Core\Utility\Error;
use Drupal\KernelTests\KernelTestBase;
use Drupal\past\Tests\PastEventTestTrait;

/**
 * Generic API tests using the database backend.
 *
 * @group past
 */
class PastKernelTest extends KernelTestBase {

  use PastEventTestTrait;

  /**
   * Modules required to run the tests.
   *
   * @var string[]
   */
  public static $modules = [
    'past',
    'past_db',
    'system',
    'views',
    'user',
    'options'
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->installEntitySchema('past_event');
    $this->installEntitySchema('user');
    $this->installConfig(['past', 'past_db']);
    $this->installSchema('past_db', ['past_event_argument', 'past_event_data']);
    $this->installSchema('system', 'sequences');
    \Drupal::moduleHandler()->loadInclude('past', 'install');
  }

  /**
   * Tests the functional part of the past requirements.
   */
  public function testRequirements() {
    $element = past_requirements('runtime');
    $this->assertEqual($element['past']['value'], 'past_db_create_event');
    $this->assertEqual($element['past']['description'], t('Past backend is configured correctly.'));
    $this->assertEqual($element['past']['severity'], REQUIREMENT_OK);
    $invalid_backend = 'any_value';
    $settings = Settings::getAll();
    $settings['past_backend'] = $invalid_backend;
    new Settings($settings);
    $element = past_requirements('runtime');
    $this->assertEqual($element['past']['value'], $invalid_backend);
    $this->assertEqual($element['past']['description'], t('Past backend missing (<em class="placeholder">@any_value</em>), install and configure a valid backend, like past_db.', ['@any_value' => $invalid_backend]));
    $this->assertEqual($element['past']['severity'], REQUIREMENT_ERROR);
  }

  /**
   * Tests the functional Past interface.
   */
  public function testSave() {
    past_event_save('past', 'test', 'A test log entry');
    $event = $this->getLastEventByMachinename('test');
    $this->assertEqual('past', $event->getModule());
    $this->assertEqual('test', $event->getMachineName());
    $this->assertEqual('A test log entry', $event->getMessage());
    $this->assertEqual(session_id(), $event->getSessionId());
    $this->assertEqual(REQUEST_TIME, $event->getTimestamp());
    $this->assertEqual(RfcLogLevel::INFO, $event->getSeverity());
    $this->assertEqual([], $event->getArguments());

    past_event_save('past', 'test1', 'Another test log entry');
    $event = $this->getLastEventByMachinename('test1');
    $this->assertEqual('Another test log entry', $event->getMessage());

    $test_string = $this->randomString();
    past_event_save('past', 'test_argument', 'A test log entry with arguments', ['test' => $test_string, 'test2' => 5]);
    $event = $this->getLastEventByMachinename('test_argument');
    $this->assertEqual(2, count($event->getArguments()));
    $this->assertEqual('string', $event->getArgument('test')->getType());
    $this->assertEqual($test_string, $event->getArgument('test')->getData());
    $this->assertEqual(5, $event->getArgument('test2')->getData());
    $this->assertEqual('test', $event->getArgument('test')->getKey());
    $this->assertEqual('string', $event->getArgument('test')->getType());
    $this->assertEqual('integer', $event->getArgument('test2')->getType());

    $this->assertNull($event->getArgument('does_not_exist'));

    $array_argument = [
      'key1' => $this->randomString(),
      'key2' => $this->randomString(),
    ];
    past_event_save('past', 'test_array', 'Array argument', ['array' => $array_argument]);
    $event = $this->getLastEventByMachinename('test_array');
    $this->assertEqual(1, count($event->getArguments()));
    $this->assertEqual($array_argument, $event->getArgument('array')->getData());
    $this->assertEqual('array', $event->getArgument('array')->getType());

    $user = $this->createUser();
    past_event_save('past', 'test_user', 'Object argument', ['user' => $user]);
    $event = $this->getLastEventByMachinename('test_user');
    $this->assertEqual($user->toArray(), $event->getArgument('user')->getData(),
      'The user entity argument is preserved by saving and loading.');
    $this->assertEqual('entity:user', $event->getArgument('user')->getType());

    $exception = new \Exception('An exception', 500);
    past_event_save('past', 'test_exception', 'An exception', ['exception' => $exception]);
    $event = $this->getLastEventByMachinename('test_exception');
    $expected = ['backtrace' => $exception->getTraceAsString()] + Error::decodeException($exception);
    $this->assertEqual($expected, $event->getArgument('exception')->getData(),
      'The exception argument is preserved by saving and loading.');
    // @todo: We still need to know that this was an exception.
    $this->assertEqual('array', $event->getArgument('exception')->getType());

    // Created an exception with 4 nested previous exceptions, the 4th will be
    // ignored.
    $ignored_exception = new \Exception('This exception will be ignored', 90);
    $previous_previous_previous_exception = new \Exception('Previous previous previous exception', 99, $ignored_exception);
    $previous_previous_exception = new \Exception('Previous previous exception', 100, $previous_previous_previous_exception);
    $previous_exception = new \Exception('Previous exception', 500, $previous_previous_exception);
    $exception = new \Exception('An exception', 500, $previous_exception);
    past_event_save('past', 'test_exception', 'An exception', ['exception' => $exception]);
    $event = $this->getLastEventByMachinename('test_exception');

    // Build up the expected data, each previous exception is logged one level
    // deeper.
    $expected = ['backtrace' => $exception->getTraceAsString()] + Error::decodeException($exception);
    $expected['previous'] = ['backtrace' => $previous_exception->getTraceAsString()] + Error::decodeException($previous_exception);
    $expected['previous']['previous'] = ['backtrace' => $previous_previous_exception->getTraceAsString()] + Error::decodeException($previous_previous_exception);
    $expected['previous']['previous']['previous'] = ['backtrace' => $previous_previous_previous_exception->getTraceAsString()] + Error::decodeException($previous_previous_previous_exception);
    $this->assertEqual($expected, $event->getArgument('exception')->getData(),
      'The nested exception argument is preserved by saving and loading.');

    past_event_save('past', 'test_timestamp', 'Event with a timestamp', [], ['timestamp' => REQUEST_TIME - 1]);
    $event = $this->getLastEventByMachinename('test_timestamp');
    $this->assertEqual(REQUEST_TIME - 1, $event->getTimestamp());

    // Test saving events with a severity threshold.
    // First set severity_threshold as RfcLogLevel::WARNING.
    $this->config('past.settings')
      ->set('severity_threshold', RfcLogLevel::WARNING)
      ->save();

    // Create an event with a lower severity value than severity_threshold.
    // This event will NOT be saved.
    $option = ['severity' => RfcLogLevel::INFO];
    $created = past_event_create('past_db', 'testEventLowerSeverity', NULL, $option);
    $this->assertEqual($created->save(), NULL);

    // Create an event with a higher severity value than severity_threshold.
    // This event will be saved.
    $option = ['severity' => RfcLogLevel::ERROR];
    $created = past_event_create('past_db', 'testEventHigherSeverity', NULL, $option);
    $this->assertEqual($created->save(), SAVED_NEW);

    // Create an event with same severity value than severity_threshold.
    // This event will be saved.
    $option = ['severity' => RfcLogLevel::WARNING];
    $created = past_event_create('past_db', 'testEventSameSeverity', NULL, $option);
    $this->assertEqual($created->save(), SAVED_NEW);

    // Test adding an exception argument to the current past event and raising
    // its severity.
    past_event_save('past', 'test_user', 'Object argument', ['user' => $user]);
    $event = $this->getLastEventByMachinename('test_user');

    // Severity set as RfcLogLevel::INFO by default.
    $this->assertEqual(1, count($event->getArguments()));
    $this->assertEqual(RfcLogLevel::INFO, $event->getSeverity());
    $this->assertFalse(array_key_exists('exception', $event->getArguments()));

    // Test that adding an exception, the event's severity should be set as
    // RfcLogLevel::ERROR by default.
    $exception = new \Exception('Add an exception', 500);
    $event->addException($exception);
    $event->save();

    $this->assertEqual(2, count($event->getArguments()));
    $this->assertEqual(RfcLogLevel::ERROR, $event->getSeverity());
    $this->assertTrue(array_key_exists('exception', $event->getArguments()));

    // Test that adding an exception with a higher event's severity than the
    // default one, like RfcLogLevel::ALERT, will be set.
    $exception = new \Exception('Add an exception', 500);
    $event->addException($exception, [], RfcLogLevel::ALERT);
    $event->save();

    $this->assertEqual(2, count($event->getArguments()));
    $this->assertEqual(RfcLogLevel::ALERT, $event->getSeverity());
    $this->assertTrue(array_key_exists('exception', $event->getArguments()));

    // Test that adding an exception with a lower event's severity than the
    // previous one, e.g. RfcLogLevel::WARNING, will NOT be set, but will keep
    // the last largest severity.
    $exception = new \Exception('Add an exception', 500);
    $event->addException($exception, [], RfcLogLevel::WARNING);
    $event->save();

    $this->assertEqual(2, count($event->getArguments()));
    $this->assertEqual(RfcLogLevel::ALERT, $event->getSeverity());
    $this->assertTrue(array_key_exists('exception', $event->getArguments()));
  }

  /**
   * Tests delete past event.
   */
  public function testDelete() {
    past_event_save('past', 'test', 'Message without arguments');
    $event = $this->getLastEventByMachinename('test');
    \Drupal::entityManager()
      ->getStorage('past_event')
      ->delete([$event]);
    $event = $this->getLastEventByMachinename('test');
    $this->assertNull($event);
  }

  /**
   * Tests the Past OO interface.
   */
  public function testObjectOrientedInterface() {
    $event = past_event_create('past', 'test_raw', 'Message with arguments');
    $array_argument = ['data' => ['sub' => 'value'], 'something' => 'else'];
    $argument = $event->addArgument('first', $array_argument);
    $argument->setRaw(['data' => ['sub' => 'value']]);
    $event->addArgument('second', 'simple');
    $event->save();

    $event = $this->getLastEventByMachinename('test_raw');
    $this->assertEqual($array_argument, $event->getArgument('first')->getData());
    $this->assertEqual('simple', $event->getArgument('second')->getData());

    // Test the exclude filter.
    $event = past_event_create('past', 'test_exclude', 'Exclude filter');
    $event->addArgument('array', $array_argument, ['exclude' => ['something']]);
    $event->save();
    $excluded_array = $array_argument;
    unset($excluded_array['something']);

    $event = $this->getLastEventByMachinename('test_exclude');
    $this->assertEqual(1, count($event->getArguments()));
    $this->assertEqual($excluded_array, $event->getArgument('array')->getData());
  }

  /**
   * Tests if the watchdog replacement works as expected.
   */
  public function testWatchdogReplacement() {
    $user = \Drupal::currentUser();
    $logger = \Drupal::logger('test_watchdog');

    // First enable watchdog logging.
    $this->config('past.settings')
      ->set('log_watchdog', 1)
      ->save();
    $machine_name = 'test_watchdog';

    \Drupal::request()->headers->set('referer', 'mock-referer');

    $msg = 'something';
    $logger->info($msg);
    $event = $this->getLastEventByMachinename($machine_name);
    $this->assertNotNull($event, 'Watchdog call caused an event.');
    $this->assertEqual('watchdog', $event->getModule());
    $this->assertEqual($msg, $event->getMessage());
    $this->assertEqual(RfcLogLevel::INFO, $event->getSeverity());
    $this->assertEqual(1, count($event->getArguments()));
    $this->assertNotNull($event->getArgument('watchdog_args'));
    $this->assertEqual($event->getLocation(), 'http://localhost/');
    $this->assertTrue(strpos($event->getReferer(), 'mock-referer')===0,
      'Contains mock-referer.');

    // Note that here we do not create a test user but use the user that has
    // triggered the test as this is the user captured in the $logger->info().
    $this->assertEqual($user->id(), $event->getUid());

    $msg = 'something new';
    $nice_url = 'http://www.md-systems.ch';
    $logger->notice($msg, ['link' => $nice_url]);
    $event = $this->getLastEventByMachinename($machine_name);
    $this->assertEqual('watchdog', $event->getModule());
    $this->assertEqual($msg, $event->getMessage());
    $this->assertEqual(RfcLogLevel::NOTICE, $event->getSeverity());
    // A notice generates a backtrace and there's an additional link
    // argument, so there are three arguments.
    $this->assertEqual(3, count($event->getArguments()));
    $this->assertNotNull($event->getArgument('watchdog_args'));
    $this->assertNotNull($event->getArgument('link'));
    $this->assertEqual($nice_url, $event->getArgument('link')->getData());

    // Now we disable watchdog logging.
    $this->config('past.settings')
      ->set('log_watchdog', 0)
      ->save();
    $logger->info('something Past will not see');
    $event = $this->getLastEventByMachinename($machine_name);
    // And still the previous message should be found.
    $this->assertEqual($msg, $event->getMessage());
  }

  /**
   * Tests the session id behavior.
   */
  public function testSessionIdBehavior() {
    // By default, the global session ID should be stored.
    past_event_save('past', 'test', 'A test log entry');
    $event = $this->getLastEventByMachinename('test');
    $this->assertEqual(session_id(), $event->getSessionId());

    // Global session ID should only be stored if enabled in config.
    $this->config('past.settings')
      ->set('log_session_id', 0)
      ->save();
    past_event_save('past', 'test1', 'Another test log entry');
    $event = $this->getLastEventByMachinename('test1');
    $this->assertEqual('', $event->getSessionId());

    // Explicitly set session ID should be stored in any case.
    $event = past_event_create('past', 'test2', 'And Another test log entry');
    $event->setSessionId('trace me');
    $event->save();
    $event = $this->getLastEventByMachinename('test2');
    $this->assertEqual('trace me', $event->getSessionId());

    // Explicitly set session ID should be used in favor of the global one.
    $this->config('past.settings')
      ->set('log_session_id', 1)
      ->save();
    $event = past_event_create('past', 'test3', 'And Yet Another test log entry');
    $event->setSessionId('trace me too');
    $event->save();
    $event = $this->getLastEventByMachinename('test3');
    $this->assertEqual('trace me too', $event->getSessionId());
  }

  /**
   * Test that watchdog logs of type 'php' don't produce notices.
   */
  public function testErrorArray() {
    $this->config('past.settings')
      ->set('log_watchdog', TRUE)
      ->save();
    \Drupal::logger('php')->notice('This is some test watchdog log of type php');
  }

  /**
   * Create a random user without permissions.
   *
   * @param array $values
   *   (optional) Any options to forward to entity_create().
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The created user.
   */
  protected function createUser(array $values = []) {
    $user = entity_create('user', $values + [
        'name' => $this->randomMachineName(),
        'status' => 1,
      ]);
    $user->enforceIsNew()->save();
    return $user;
  }

}
