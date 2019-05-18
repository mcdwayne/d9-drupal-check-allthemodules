<?php

namespace Drupal\past\Tests;

use Drupal\Core\Logger\RfcLogLevel;
use Drupal\simpletest\WebTestBase;

/**
 * Generic API web tests using the database backend.
 *
 * @group past
 */
class PastWebTest extends WebTestBase {

  use PastEventTestTrait;

  /**
   * Modules required to run the tests.
   *
   * @var string[]
   */
  public static $modules = [
    'past',
    'past_db',
    'past_testhidden',
  ];

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    // Empty the logfile, our fatal errors are expected.
    $filename = DRUPAL_ROOT . '/' . $this->siteDirectory . '/error.log';
    file_put_contents($filename, '');
    parent::tearDown();
  }

  /**
   * Tests the disabled exception handler.
   */
  public function testExceptionHandler() {
    // Create user to test logged uid.
    $account = $this->drupalCreateUser();
    $this->drupalLogin($account);

    // Let's produce an exception, the exception handler is enabled by default.
    $this->drupalGet('past_trigger_error/Exception');
    $this->assertText(t('The website encountered an unexpected error. Please try again later.'));
    $this->assertText('Exception: This is an exception.');

    // Now we should have a log event, assert it.
    $event = $this->getLastEventByMachinename('unhandled_exception');
    $this->assertEqual('past', $event->getModule());
    $this->assertEqual('unhandled_exception', $event->getMachineName());
    $this->assertEqual(RfcLogLevel::ERROR, $event->getSeverity());
    $this->assertEqual(1, count($event->getArguments()));

    $this->drupalGet('test');
    // Test for not displaying 403 and 404 logs.
    $event_404 = $this->getLastEventByMachinename('unhandled_exception');
    $this->assertEqual($event->id(), $event_404->id(), 'No 403 and 404 logs were displayed');

    $data = $event->getArgument('exception')->getData();
    $this->assertTrue(array_key_exists('backtrace', $data));
    $this->assertEqual($account->id(), $event->getUid());

    // Disable exception handling and re-throw the exception.
    $this->config('past.settings')
      ->set('exception_handling', 0)
      ->save();
    $this->drupalGet('past_trigger_error/Exception');
    $this->assertText(t('The website encountered an unexpected error. Please try again later.'));
    $this->assertText('Exception: This is an exception.');

    // No new exception should have been logged.
    $event_2 = $this->getLastEventByMachinename('unhandled_exception');
    $this->assertEqual($event->id(), $event_2->id(), 'No new event was logged');
  }

  /**
   * Tests the shutdown function.
   */
  public function testShutdownFunction() {
    // Enable hook_watchdog capture.
    $this->config('past.settings')
      ->set('log_watchdog', 1)
      ->save();

    // Create user to test logged uid.
    $account = $this->drupalCreateUser();
    $this->drupalLogin($account);

    // Let's trigger an error, the error handler is disabled by default.
    $this->drupalGet('past_trigger_error/E_ERROR');

    // Now we have a log event, assert it.
    // In PHP 5, this is logged as a fatal error. In PHP 7, this is catched by
    // the standard exception handler and logged through watchdog.
    // @todo: Improve our exception handling to catch this with a backtrace in
    //   PHP 7.
    if (version_compare(PHP_VERSION, '7.0.0') >= 0) {
      $event = $this->getLastEventByMachinename('php');
      $this->assertEqual('watchdog', $event->getModule());
      $this->assertEqual(RfcLogLevel::ERROR, $event->getSeverity());
      $this->assertEqual(1, count($event->getArguments()));
      $this->assertTrue(strpos($event->getMessage(), 'Error: Cannot use object of type stdClass as array') !== FALSE);
    }
    else {
      $event = $this->getLastEventByMachinename('fatal_error');
      $this->assertEqual('past', $event->getModule());
      $this->assertEqual(RfcLogLevel::CRITICAL, $event->getSeverity());
      $this->assertEqual(1, count($event->getArguments()));
      $this->assertEqual('Cannot use object of type stdClass as array', $event->getMessage());
      $data = $event->getArgument('error')->getData();
      $this->assertEqual($data['type'], E_ERROR);
      $this->assertEqual($account->id(), $event->getUid());
    }
  }

  /**
   * Tests triggering PHP errors.
   *
   * @todo We leave out E_PARSE as we can't handle it and it would make our code unclean.
   * We do not test E_USER_* cases. They are not PHP errors.
   */
  public function testErrors() {
    // Enable hook_watchdog capture.
    $this->config('past.settings')
      ->set('log_watchdog', 1)
      ->save();

    $this->drupalGet('past_trigger_error/E_COMPILE_ERROR');
    $event = $this->getLastEventByMachinename('php');
    $this->assertTextContains($event->getMessage(), 'Warning: require_once');

    $this->drupalGet('past_trigger_error/E_COMPILE_WARNING');
    $event = $this->getLastEventByMachinename('php');
    $this->assertTextContains($event->getMessage(), 'Warning: include_once');

    $this->drupalGet('past_trigger_error/E_DEPRECATED');
    $event = $this->getLastEventByMachinename('php');
    if (version_compare(PHP_VERSION, '7.0.0') >= 0) {
      $this->assertTextContains($event->getMessage(), 'Error: Call to undefined function call_user_method()');
    }
    else {
      $this->assertTextContains($event->getMessage(), 'Deprecated function: Function call_user_method() is deprecated');
    }

    $this->drupalGet('past_trigger_error/E_NOTICE');
    $event = $this->getLastEventByMachinename('php');
    $this->assertTextContains($event->getMessage(), 'Notice: Undefined variable');

    $this->drupalGet('past_trigger_error/E_RECOVERABLE_ERROR');
    $event = $this->getLastEventByMachinename('php');
    if (version_compare(PHP_VERSION, '7.0.0') >= 0) {
      $this->assertTextContains($event->getMessage(), 'TypeError: Argument 1 passed to my_func() must be of the type array, null given');
    }
    else {
      $this->assertTextContains($event->getMessage(), 'Recoverable fatal error');
    }

    $this->drupalGet('past_trigger_error/E_WARNING');
    $event = $this->getLastEventByMachinename('php');
    $this->assertTextContains($event->getMessage(), 'Warning: fopen');

    $this->drupalGet('past_trigger_error/E_STRICT');
    $event = $this->getLastEventByMachinename('php');
    $this->assertTextContains($event->getMessage(), 'Non-static method Strict::test() should not be called statically');
    // Make sure that the page is rendered correctly.
    $this->assertText('hello, world');

    /*
     * Test is unreliable, fix in https://www.drupal.org/node/2533554.
     *
    $this->drupalGet('past_trigger_error/E_STRICT_parse');
    $event = $this->getLastEventByMachinename('php');
    $this->assertTextContains($event->getMessage(), 'Strict warning: Declaration of ChildClass::myMethod() should be compatible with ParentClass::myMethod($args)');
    // Make sure that the page is rendered correctly.
    $this->assertText('hello, world');
    $this->assertText('Strict warning: Declaration of');
     */
  }

  /**
   * Tests the settings form.
   */
  protected function testSettings() {
    // Create user.
    $account = $this->drupalCreateUser([
      'administer past',
    ]);
    $this->drupalLogin($account);
    $edit = [
      'log_watchdog' => TRUE,
    ];
    $this->drupalPostForm('admin/config/development/past', $edit, t('Save configuration'));
    $this->assertFieldChecked("edit-backtrace-include-severity-0", 'Default config set correctly');
    $this->assertNoFieldChecked("edit-backtrace-include-severity-6", 'Default config set correctly');
    $edit = [
      'backtrace_include[severity_0]' => FALSE,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));
    $this->assertNoFieldChecked("edit-backtrace-include-severity-0", 'Emergency set to false');
    $edit = [
      'backtrace_include[severity_0]' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));
    $this->assertFieldChecked("edit-backtrace-include-severity-0", 'Emergency set to true');
  }

  /**
   * Asserts if $text contains $chunk.
   *
   * @param string $text
   * @param string $chunk
   */
  function assertTextContains($text, $chunk) {
    $this->assert(strpos($text, $chunk) !== FALSE,
      t('@text contains @chunk.', [
        '@text' => $text,
        '@chunk' => $chunk,
      ]));
  }
  /**
   * Asserts if $text starts with $chunk.
   *
   * @param string $text
   * @param string $chunk
   */
  function assertTextStartsWith($text, $chunk) {
    $this->assert(strpos($text, $chunk) === 0,
      t('@text starts with @chunk.', [
        '@text' => $text,
        '@chunk' => $chunk
      ]));
  }

  /**
   * Overrides DrupalWebTestCase::curlHeaderCallback().
   *
   * Does not report errors from the client site as exceptions as we are
   * expecting them and they're required for our test.
   *
   * @param $curlHandler
   *   The cURL handler.
   * @param $header
   *   An header.
   */
  protected function curlHeaderCallback($curlHandler, $header) {
    // Header fields can be extended over multiple lines by preceding each
    // extra line with at least one SP or HT. They should be joined on receive.
    // Details are in RFC2616 section 4.
    if ($header[0] == ' ' || $header[0] == "\t") {
      // Normalize whitespace between chucks.
      $this->headers[] = array_pop($this->headers) . ' ' . trim($header);
    }
    else {
      $this->headers[] = $header;
    }

    // Save cookies.
    if (preg_match('/^Set-Cookie: ([^=]+)=(.+)/', $header, $matches)) {
      $name = $matches[1];
      $parts = array_map('trim', explode(';', $matches[2]));
      $value = array_shift($parts);
      $this->cookies[$name] = ['value' => $value, 'secure' => in_array('secure', $parts)];
      if ($name == $this->sessionName) {
        if ($value != 'deleted') {
          $this->sessionId = $value;
        }
        else {
          $this->sessionId = NULL;
        }
      }
    }

    // This is required by cURL.
    return strlen($header);
  }

  /**
   * Tests the actor dropbutton in the past page handling with deleted user.
   */
  public function testGetActorDropbutton() {
    // Create a new user and create past event.
    $user = $this->drupalCreateUser();
    $event = past_event_create('past', 'test', 'Test Log Entry', [
      'uid' => $user->id(),
    ]);
    $event->save();
    // Delete the user and create/login with another user.
    $user->delete();
    $user2 = $this->drupalCreateUser([
      'view past reports',
      'access site reports',
    ]);
    $this->drupalLogin($user2);
    // Check that a fatal error is not encountered when visiting the past page.
    $this->drupalGet('admin/reports/past');
    $this->assertResponse(200, 'Received expected HTTP status code 200.');
    // Check the link redirect to a non-fatal error page.
    $this->clickLinkPartialName('Session: ');
    $this->assertResponse(200, 'Received expected HTTP status code 200.');
  }

}
