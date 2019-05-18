<?php

namespace Drupal\akismet\Tests;


use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Html;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Session\AccountInterface;
use Drupal\akismet\Controller\FormController;
use Drupal\akismet\Entity\Form;
use Drupal\akismet\Entity\FormInterface;
use Drupal\akismet\Storage\ResponseDataStorage;
use Drupal\akismet\Utility\Logger;
use Drupal\simpletest\WebTestBase;


abstract class AkismetTestBase extends WebTestBase {

  use AkismetCommentTestTrait;

  /**
   * The text the user should see when they are blocked from submitting a form
   * because the Akismet servers are unreachable.
   */
  const FALLBACK_MESSAGE = 'The spam filter installed on this site is currently unavailable. Per site policy, we are unable to accept new submissions until that problem is resolved. Please try resubmitting the form in a couple of minutes.';

  /**
   * The text the user should see if there submission was determined to be spam.
   */
  const SPAM_MESSAGE = 'Your submission has triggered the spam filter and will not be accepted.';

  /**
   * The text the user should see if they did not fill out the CAPTCHA correctly.
   */
  const INCORRECT_MESSAGE = 'The word verification was not completed correctly. Please complete this new word verification and try again.';

  /**
   * The text the user should see if the textual analysis was unsure about the
   * content.
   */
  const UNSURE_MESSAGE = "To complete this form, please complete the word verification.";

  /**
   * The text the user should see if the textual analysis determined that there
   * was profane content.
   */
  const PROFANITY_MESSAGE = "Your submission has triggered the profanity filter and will not be accepted until the inappropriate language is removed.";

  /**
   * The fieldname for the Captcha input
   */
  const CAPTCHA_INPUT = 'akismet[captcha][captcha_input]';


  /**
   * Indicates if the default setup permissions and keys should be skipped.
   *
   * @var bool
   */
  public $disableDefaultSetup = FALSE;

  /**
   * An user with permissions to administer Akismet.
   *
   * @var \Drupal\user\Entity\User;
   */
  public $adminUser;

  /**
   * Tracks Akismet messages across tests.
   *
   * @var array
   */
  protected $messages = array();

  /**
   * The public key used during testing.
   */
  protected $publicKey;

  /**
   * The private key used during testing.
   */
  protected $privateKey;

  /**
   * Flag indicating whether to automatically create testing API keys.
   *
   * If testing_mode is enabled, Akismet module automatically uses the
   * AkismetDrupalTest client implementation. This implementation automatically
   * creates testing API keys when being instantiated (and ensures to re-create
   * testing API keys in case they vanish). The behavior is executed by default,
   * but depends on the 'akismet.testing_create_keys' state variable being TRUE.
   *
   * Some functional test cases verify that expected errors are displayed in
   * case no or invalid API keys are configured. For such test cases, set this
   * flag to FALSE to skip the automatic creation of testing keys.
   *
   * @see AkismetDrupalTest::$createKeys
   * @see AkismetDrupalTest::createKeys()
   */
  protected $createKeys = TRUE;

  /**
   * Indicates if the test local server should be used in place of the
   * Akismet API.
   */
  protected $useLocal = FALSE;

  /**
   * Tracks Akismet session response IDs.
   *
   * @var array
   */
  protected $responseIds = array();

  /**
   * Akismet configuration settings.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $akismetSettings;

  /**
   * An instance of the Drupal Akismet client.
   *
   * @var \Drupal\akismet\Client\DrupalClientInterface
   */
  protected $akismet;

  /**
   * The current log level to use on the site after testing.
   */
  protected $originalLogLevel;

  /**
   * Used to turn on/off watchdog assertions during a portion of a test.
   */
  protected $assertWatchdogErrors = TRUE;

  /**
   * Indicates if default Akismet testing setup should be created.
   */
  protected $setupAkismet = TRUE;

  /**
   * {@inheritDoc}
   */
  protected function setUp() {
    $this->messages = array();

    parent::setUp();

    if (!$this->setupAkismet) {
      return;
    }

    $state = [];

    // Set the spun up instance to use the local test class.
    if ($this->useLocal) {
      $state['akismet.testing_use_local'] = TRUE;
    }

    // Omit warnings and create keys only when the test asked for it.
    $state += array(
      'akismet.omit_warning' => TRUE,
    );
    \Drupal::state()->setMultiple($state);

    // Set log level
    $settings = \Drupal::configFactory()->getEditable('akismet.settings');
    $settings->set('log_level', RfcLogLevel::DEBUG);
    // Set test mode.
    $settings->set('test_mode.enabled', TRUE);
    $settings->save();

    if ($this->disableDefaultSetup) {
      return;
    }

    $permissions = [
      'access administration pages',
      'administer akismet',
      'administer nodes',
      'access content overview',
      'administer content types',
      'administer permissions',
      'administer users',
      'bypass node access',
      'access comments',
      'post comments',
      'skip comment approval',
      'administer comments',
      'administer account settings',
    ];
    $this->adminUser = $this->drupalCreateUser($permissions);

  }

  /**
   * {@inheritDoc}
   */
  protected function tearDown() {
    if ($this->assertWatchdogErrors) {
      $this->assertAkismetWatchdogMessages();
    }
    $this->akismet = NULL;

    parent::tearDown();
  }

  /**
   * Assert any watchdog messages based on their severity.
   *
   * This function can be (repeatedly) invoked to assert new watchdog messages.
   * All watchdog messages with a higher severity than RfcLogLevel::NOTICE are
   * considered as "severe".
   *
   * @param $max_severity
   *   (optional) A maximum watchdog severity level message constant that log
   *   messages must have to pass the assertion. All messages with a higher
   *   severity will fail. Defaults to RfcLogLevel::NOTICE. If a severity level
   *   higher than RfcLogLevel::NOTICE is passed, then at least one severe message
   *   is expected.
   */
  protected function assertAkismetWatchdogMessages($max_severity = RfcLogLevel::NOTICE) {
    // Ensure that all messages have been written before attempting to verify
    // them. Actions executed within the test class may lead to log messages,
    // but those get only logged when hook_exit() is triggered.
    // akismet.module may not be installed by a test and thus not loaded yet.
    //drupal_load('module', 'akismet');
    Logger::writeLog();
    $database = \Drupal::database();

    module_load_include('inc', 'dblog', 'dblog.admin');

    $this->messages = array();
    $query = $database->select('watchdog', 'w')
      ->fields('w')
      ->orderBy('w.timestamp', 'ASC');

    // The comparison logic applied in this function is a bit confusing, since
    // the values of watchdog severity level constants defined by RFC 3164 are
    // negated to their actual "severity level" meaning:
    // RfcLogLevel::EMERGENCY is 0, RfcLogLevel::NOTICE is 5, RfcLogLevel::DEBUG is 7.

    $fail_expected = ($max_severity < RfcLogLevel::NOTICE);
    $had_severe_message = FALSE;
    foreach ($query->execute() as $row) {
      $this->messages[$row->wid] = $row;
      // Only messages with a maximum severity of $max_severity or less severe
      // messages must pass. More severe messages need to fail. See note about
      // severity level constant values above.

      $output = $this->formatMessage($row);
      if ($row->severity >= $max_severity) {
        // Visually separate debug log messages from other messages.
        if ($row->severity == RfcLogLevel::DEBUG) {
          $this->error($output, 'User notice');
        }
        else {
          $this->pass(Html::escape($row->type) . ' (' . $row->severity .'): ' . $output, t('Watchdog'));
        }
      }
      else {
        $this->fail(Html::escape($row->type) . ' (' . $row->severity .'): ' . $output, t('Watchdog'));
      }
      // In case a severe message is expected, non-severe messages always pass,
      // since we would trigger a false positive test failure otherwise.
      // However, in order to actually assert the expectation, there must have
      // been at least one severe log message.
      $had_severe_message = ($had_severe_message || $row->severity < RfcLogLevel::NOTICE);
    }
    // Assert that there was a severe message, in case we expected one.
    if ($fail_expected && !$had_severe_message) {
      $this->fail(t('Severe log message was found.'), t('Watchdog'));
    }
    // Delete processed watchdog messages.
    if (!empty($this->messages)) {
      $seen_ids = array_keys($this->messages);

      $database->delete('watchdog')->condition('wid', $seen_ids, 'IN')->execute();
    }
  }

  /**
   * Wraps drupalGet() for additional watchdog message assertion.
   *
   * @param $options
   *   In addition to regular $options that are passed to url():
   *   - watchdog: (optional) Boolean whether to assert that only non-severe
   *     watchdog messages have been logged. Defaults to TRUE. Use FALSE to
   *     negate the watchdog message severity assertion.
   *
   * @see DrupalWebTestCase->drupalGet()
   * @see AkismetWebTestCase->assertAkismetWatchdogMessages()
   * @see AkismetWebTestCase->assertResponseID()
   */
  protected function drupalGet($path, array $options = array(), array $headers = array()) {
    $output = parent::drupalGet($path, $options, $headers);
    if ($this->assertWatchdogErrors) {
      $options += array('watchdog' => RfcLogLevel::NOTICE);
      $this->assertAkismetWatchdogMessages($options['watchdog']);
    }
    return $output;
  }

  /**
   * Wraps drupalPostForm() for additional watchdog message assertion.
   *
   * @param $options
   *   In addition to regular $options that are passed to url():
   *   - watchdog: (optional) Boolean whether to assert that only non-severe
   *     watchdog messages have been logged. Defaults to TRUE. Use FALSE to
   *     negate the watchdog message severity assertion.
   *
   * @see AkismetWebTestCase->assertAkismetWatchdogMessages()
   * @see AkismetWebTestCase->assertResponseID()
   * @see DrupalWebTestCase->drupalPostForm()
   */
  protected function drupalPostForm($path, $edit, $submit, array $options = array(), array $headers = array(), $form_html_id = NULL, $extra_post = NULL) {
    parent::drupalPostForm($path, $edit, $submit, $options, $headers, $form_html_id, $extra_post);
    if ($this->assertWatchdogErrors) {
      $options += array('watchdog' => RfcLogLevel::NOTICE);
      $this->assertAkismetWatchdogMessages($options['watchdog']);
    }
  }
  /**
   * Retrieve sent request parameter values from testing server implementation.
   *
   * @param $resource
   *   (optional) The resource name to retrieve submitted values from. Defaults
   *   to 'content'.
   * @param $retain
   *   (optional) Whether to retain the (last) record being read. Defaults to
   *   FALSE; i.e., the record being read is removed.
   *
   * @see AkismetTestBase::resetServerRecords()
   */
  protected function getServerRecord($resource = 'content', $retain = FALSE) {
    $function = 'akismet_test_server_' . $resource;

    // Ensure that we do not read obsolete/outdated data from variable_get()'s
    // static cache while variables might have been updated in the child site.
    //$this->refreshVariables();

    // Retrieve last recorded values.
    $state = \Drupal::state();
    $storage = $state->get($function, []);
    $return = ($retain ? end($storage) : array_shift($storage));
    $state->set($function, $storage);

    return $return;
  }

  /**
   * Resets recorded server values.
   *
   * @param $resource
   *   (optional) The resource name to reset records of. Defaults to 'content'.
   *
   * @see AkismetTestBase::getServerRecord()
   */
  protected function resetServerRecords($resource = 'content') {
    $function = 'akismet_test_server_' . $resource;

    // Delete the variable.
    \Drupal::state()->delete($function);
  }

  /**
   * Instantiate a Akismet client and make it available on $this->akismet;
   */
  protected function getClient($force = FALSE) {
    if ($force || !isset($this->akismet)) {
      if ($force) {
        $this->rebuildContainer();
      }
      $this->akismet = \Drupal::service('akismet.client');
    }
    return $this->akismet;
  }

  /**
   * Formats a database log message.
   *
   * This is copied from DbLogController and should be called from there instead.
   *
   * @param object $row
   *   The record from the watchdog table. The object properties are: wid, uid,
   *   severity, type, timestamp, message, variables, link, name.
   *
   * @return string|false
   *   The formatted log message or FALSE if the message or variables properties
   *   are not set.
   */
  public function formatMessage($row) {
    // Check for required properties.
    if (isset($row->message) && isset($row->variables)) {
      // Messages without variables or user specified text.
      if ($row->variables === 'N;') {
        $message = $row->message;
      }
      // Message to translate with injected variables.
      else {
        $message = new FormattableMarkup($row->message, unserialize($row->variables));
      }
    }
    else {
      $message = FALSE;
    }
    return $message;
  }

  /**
   * Saves a akismet_form entity to protect a given form with Akismet.
   *
   * @param string $form_id
   *   The form id to protect.
   * @param int $mode
   *   The protection mode defined in \Drupal\akismet\Entity\FormInterface.
   *   Defaults to AKISMET_MODE_ANALYSIS.
   * @param array $values
   *   (optional) An associative array of properties to additionally set on the
   *   akismet_form entity.
   *
   * @return int
   *   The save status, as returned by akismet_form_save().
   */
  protected function setProtection($form_id, $mode = FormInterface::AKISMET_MODE_ANALYSIS, $values = array()) {
    /* @var $akismet_form \Drupal\akismet\Entity\FormInterface */
    if (!$akismet_form = \Drupal::entityManager()->getStorage('akismet_form')->load($form_id)) {
      $akismet_form = Form::create();
      $akismet_form->initialize($form_id);
    }
    $akismet_form->setProtectionMode($mode);
    if ($values) {
      foreach ($values as $property => $value) {
        $akismet_form[$property] = $value;
      }
    }
    $status = $akismet_form->save();
    return $status;
  }

  /**
   * Assert that the privacy policy link is found on the current page.
   */
  protected function assertPrivacyLink() {
    $elements = $this->xpath('//div[contains(@class, "akismet-privacy")]');
    $this->assertTrue($elements, t('Privacy policy container found.'));
  }

  /**
   * Assert that the privacy policy link is not found on the current page.
   */
  protected function assertNoPrivacyLink() {
    $elements = $this->xpath('//div[contains(@class, "akismet-privacy")]');
    $this->assertFalse($elements, t('Privacy policy container not found.'));
  }

  /**
   * Assert that Akismet session data was stored for a submission.
   *
   * @param $entity
   *   The entity type to search for in {akismet}.
   * @param $id
   *   The entity id to search for in {akismet}.
   * @param $response_type
   *   (optional) The type of ID to assert; e.g., 'contentId', 'captchaId'.
   * @param $response_id
   *   (optional) The ID of $type to assert additionally.
   */
  protected function assertAkismetData($entity, $id, $response_type = '', $response_id = NULL) {
    $data = ResponseDataStorage::loadByEntity($entity, $id);
    $this->assertTrue($data->id, t('Akismet session data for %entity @id exists: <pre>@data</pre>', array(
      '%entity' => $entity,
      '@id' => $id,
      '@data' => var_export($data, TRUE),
    )));
    if (isset($response_id)) {
      $this->assertSame(t('Stored @type ID', array('@type' => $response_type)), $data->{$response_type}, $response_id);
    }
    return $data;
  }

  /**
   * Assert that no Akismet session data exists for a certain entity.
   */
  protected function assertNoAkismetData($entity, $id) {
    $data = ResponseDataStorage::loadByEntity($entity, $id);
    $this->assertFalse($data, t('No Akismet session data exists for %entity @id.', array('%entity' => $entity, '@id' => $id)));
  }

  /**
   * Test submitting a form with 'spam' values.
   *
   * @param $url
   *   The URL of the form, or NULL to use the current page.
   * @param $spam_fields
   *   An array of form field names to inject spam content into.
   * @param $edit
   *   An array of non-spam form values used in drupalPost().
   * @param $button
   *   The text of the form button to click in drupalPost().
   * @param $success_message
   *   An optional message to test does not appear after submission.
   */
  protected function assertSpamSubmit($url, array $spam_fields, array $edit = [], $button, $success_message = '') {
    $edit += array_fill_keys($spam_fields, 'spam');
    $this->drupalPostForm($url, $edit, $button);
    $this->assertText(self::SPAM_MESSAGE);
    if ($success_message) {
      $this->assertNoText($success_message);
    }
  }

  /**
   * Test submitting a form with 'ham' values.
   *
   * @param $url
   *   The URL of the form, or NULL to use the current page.
   * @param $ham_fields
   *   An array of form field names to inject ham content into.
   * @param $edit
   *   An array of non-spam form values used in drupalPost().
   * @param $button
   *   The text of the form button to click in drupalPost().
   * @param $success_message
   *   An optional message to test does appear after submission.
   */
  protected function assertHamSubmit($url, array $ham_fields, array $edit = [], $button, $success_message = '') {
    $edit += array_fill_keys($ham_fields, 'ham');
    $this->drupalPostForm($url, $edit, $button);
    $this->assertNoText(self::SPAM_MESSAGE);
    if ($success_message) {
      $this->assertText($success_message);
    }
  }

  /**
   * Test submitting a form with unsure values and resulting CAPTCHA submissions.
   *
   * @param $url
   *   The URL of the form, or NULL to use the current page.
   * @param $unsure_fields
   *   An array of form field names to inject unsure content into.
   * @param $edit
   *   An array of non-spam form values used in drupalPost().
   * @param $button
   *   The text of the form button to click in drupalPost().
   * @param $success_message
   *   An optional message to test does appear after sucessful form and CAPTCHA
   *   submission.
   */
  protected function assertUnsureSubmit($url, array $unsure_fields, array $edit = [], $button, $success_message = '') {
    $edit += array_fill_keys($unsure_fields, 'unsure');
    $this->drupalPostForm($url, $edit, $button);
    $this->assertText(self::UNSURE_MESSAGE);
    if ($success_message) {
      $this->assertNoText($success_message);
    }
  }

  /**
   * Asserts a successful akismet_test_form submission.
   *
   * @param $old_mid int
   *   (optional) The existing test record id to assert.
   */
  protected function assertTestSubmitData($old_mid = NULL) {
    $this->assertText('Successful form submission.');
    $mid = $this->getFieldValueByName('mid');
    if (isset($old_mid)) {
      $this->assertSame('Test record id', $mid, $old_mid);
    }
    else {
      $this->assertTrue($mid > 0, t('Test record id @id found.', array('@id' => $mid)));
    }
    return $mid;
  }

  /**
   * Asserts that two values belonging to the same variable are equal.
   *
   * Checks to see whether two values, which belong to the same variable name or
   * identifier, are equal and logs a readable assertion message.
   *
   * @param $name
   *   A name or identifier to use in the assertion message.
   * @param $first
   *   The first value to check.
   * @param $second
   *   The second value to check.
   *
   * @return bool
   *   TRUE if the assertion succeeded, FALSE otherwise.
   *
   * @see AkismetWebTestCase::assertNotSame()
   */
  protected function assertSame($name, $first, $second) {
    $message = t("@name: @first is equal to @second.", array(
      '@name' => $name,
      '@first' => var_export($first, TRUE),
      '@second' => var_export($second, TRUE),
    ));
    $this->assertEqual($first, $second, $message);
  }

  /**
   * Asserts that two values belonging to the same variable are not equal.
   *
   * Checks to see whether two values, which belong to the same variable name or
   * identifier, are not equal and logs a readable assertion message.
   *
   * @param $name
   *   A name or identifier to use in the assertion message.
   * @param $first
   *   The first value to check.
   * @param $second
   *   The second value to check.
   *
   * @return bool
   *   TRUE if the assertion succeeded, FALSE otherwise.
   *
   * @see AkismetWebTestCase::assertSame()
   */
  protected function assertNotSame($name, $first, $second) {
    $message = t("@name: '@first' is not equal to '@second'.", array(
      '@name' => $name,
      '@first' => var_export($first, TRUE),
      '@second' => var_export($second, TRUE),
    ));
    $this->assertNotEqual($first, $second, $message);
  }

  /**
   * Retrieve a field value by ID.
   */
  protected function getFieldValueByID($id) {
    $fields = $this->xpath($this->constructFieldXpath('id', $id));
    return (string) $fields[0]['value'];
  }

  /**
   * Retrieve a field value by name.
   */
  protected function getFieldValueByName($name) {
    $fields = $this->xpath($this->constructFieldXpath('name', $name));
    return (string) $fields[0]['value'];
  }

  /**
   * Configure Akismet protection for a given form.
   *
   * @param $form_id
   *   The form id to configure.
   * @param $mode
   *   The Akismet protection mode for the form.
   * @param $fields
   *   (optional) A list of form elements to enable for text analysis. If
   *   omitted and the form registers individual elements, all fields are
   *   enabled by default.
   * @param $edit
   *   (optional) An array of POST data to pass through to drupalPost() when
   *   configuring the form's protection.
   */
  protected function setProtectionUI($form_id, $mode = FormInterface::AKISMET_MODE_ANALYSIS, $fields = NULL, $edit = []) {
    // Always start from overview page, also to make debugging easier.
    $this->drupalGet('admin/config/content/akismet');
    // Determine whether the form is already protected.

    $exists = \Drupal::entityManager()->getStorage('akismet_form')->load($form_id);
    // Add a new form.
    if (!$exists) {
      $this->drupalGet('admin/config/content/akismet/add-form', ['query' => ['form_id' => $form_id]]);
      $save = t('Create Protected Akismet Form');
    }
    // Edit an existing form.
    else {
      $this->assertLinkByHref('admin/config/content/akismet/form/' . $form_id . '/edit');
      $this->drupalGet('admin/config/content/akismet/form/' . $form_id . '/edit');
      $save = t('Update Protected Akismet Form');
    }

    $edit += [
      'mode' => $mode,
    ];

    // Process the enabled fields.
    $form_list = FormController::getProtectableForms();
    $form_info = FormController::getProtectedFormDetails($form_id, $form_list[$form_id]['module']);
    if (!empty($form_info['elements'])) {
      $edit += [
        'checks[spam]' => TRUE,
      ];
    }
    foreach (array_keys($form_info['elements']) as $field) {
      if (!isset($fields) || in_array($field, $fields)) {
        // If the user specified all fields by default or to include this
        // field, set its checkbox value to TRUE.
        $edit['enabled_fields[' . rawurlencode($field) . ']'] = TRUE;
      }
      else {
        // Otherwise set the field's checkbox value to FALSE.
        $edit['enabled_fields[' . rawurlencode($field) . ']'] = FALSE;
      }
    }
    $this->drupalPostForm(NULL, $edit, $save);
    if (!$exists) {
      $this->assertText(t('The form protection has been added.'));
    }
    else {
      $this->assertText(t('The form protection has been updated.'));
    }
  }

  /**
   * Helper function to add permissions to the admin user.
   */
  protected function addPermissionsToAdmin(array $permissions) {
    $rid = current(array_diff(array_keys($this->adminUser->getRoles()), [AccountInterface::AUTHENTICATED_ROLE]));
    user_role_grant_permissions($rid, $permissions);
  }
}
