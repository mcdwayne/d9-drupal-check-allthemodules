<?php

namespace Drupal\past_form\Tests;

use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\past\PastEventInterface;
use Drupal\past\Tests\PastEventTestTrait;
use Drupal\past_db\Entity\PastEvent;
use Drupal\simpletest\WebTestBase;

/**
 * Generic Form tests using the database backend.
 *
 * @group past
 */
class PastFormTest extends WebTestBase {

  use PastEventTestTrait;

  /**
   * Past form settings configuration.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityInterface
   */
  protected $config;

  /**
   * Expected Event.
   *
   * @var \Drupal\past\PastEventInterface
   */
  protected $eventToBe;

  /**
   * A user with the 'view past reports' permission.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $viewUser;

  public static $modules = [
    'past',
    'past_db',
    'past_form',
    'past_testhidden',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->viewUser = $this->drupalCreateUser([
      'view past reports',
      'access site reports',
    ]);
    $this->config = \Drupal::configFactory()->getEditable('past_form.settings');
    $this->config->set('past_form_log_form_ids', ['*'])->save();
  }

  /**
   * Tests an empty submit handler array.
   */
  public function testFormEmptyArray() {
    // Given a simple form with one button with empty #submit array.
    // Submit the global submit button.
    // Check if the default form handler is executed.
    // Check the logs if the submission is captured.
    $edit = [];
    $form_id = 'past_testhidden_form_empty_submit_array';
    $button_value = 'Submit';
    $this->drupalGet($form_id);
    $this->assertText('form handler called by ' . $form_id);
    $this->drupalPostForm(NULL, $edit, $button_value);
    $this->assertNoRaw('global submit handler called by ' . $form_id);

    $event = $this->getLastEventByMachineName('submit');
    $this->eventToBe = $this->getEventToBe('submit', $form_id, $button_value);
    $this->assertSameEvent($event, $this->eventToBe, $form_id, $button_value);
  }

  /**
   * Tests the exclusion of views exposed form submission.
   */
  public function testViewsExposedForm() {
    // Check that views_exposed_filter is not recorded.
    $admin = $this->drupalCreateUser(['view past reports']);
    $this->drupalLogin($admin);

    // The login event is logged.
    $event = $this->getLastEventByMachineName('submit');
    $this->assertTrue("Form submitted: user_login_form, Log in" == $event->getMessage(), 'user_login submit was not logged.');

    // First check the fake exclude for views with exposed filters.
    $this->drupalGet('admin/reports/past');

    // The last event is still the login.
    $event = $this->getLastEventByMachineName('submit');
    $this->assertTrue("Form submitted: user_login_form, Log in" == $event->getMessage(), 'views_exposed_form submit was not logged.');

    // Additional submits after the page load submit.
    $this->drupalPostForm('admin/reports/past', ['module' => 'watchdog'], t('Apply'));
    $event = $this->getLastEventByMachineName('submit');
    $this->assertTrue("Form submitted: user_login_form, Log in" == $event->getMessage(), 'views_exposed_form submit was not logged.');
    // @todo This should add the submission. Wrong currently!
  }

  /**
   * Tests a form without submit handler.
   */
  public function testFormNoSubmitHandler() {
    // Given a simple form with one button with default #submit function entry.
    // Same as above.
    $edit = [];
    $form_id = 'past_testhidden_form_default_submit_handler';
    $button_value = 'Submit';
    $this->drupalGet($form_id);
    $this->assertRaw('form handler called by ' . $form_id);
    $this->drupalPostForm(NULL, $edit, $button_value);
    $this->assertText('global submit handler called by ' . $form_id);

    $event = $this->getLastEventByMachineName('submit');
    $this->eventToBe = $this->getEventToBe('submit', $form_id, $button_value);
    $values_to_be = [
      'sample_property' => 'sample value',
    ];
    $this->assertSameEvent($event, $this->eventToBe, $form_id, $button_value, $values_to_be);
  }

  /**
   * Tests a forms with global/custom submit hanlder.
   */
  public function testFormCustomSubmithandler() {
    // Given a simple form with one button with special #submit function..
    // Submit a form with global submit handler.
    // Check if the form handler is executed.
    // Check the logs if the submission is captured.
    $edit = [];
    $form_id = 'past_testhidden_form_custom_submit_handler';
    $button_value = 'Submit';
    $this->drupalGet($form_id);
    $this->assertRaw('form handler called by ' . $form_id);
    $this->drupalPostForm(NULL, $edit, $button_value);
    $this->assertText('custom submit handler called by ' . $form_id);

    $event = $this->getLastEventByMachineName('submit');
    $this->eventToBe = $this->getEventToBe('submit', $form_id, $button_value);
    $values_to_be = [
      'sample_property' => 'sample value',
    ];
    $this->assertSameEvent($event, $this->eventToBe, $form_id, $button_value, $values_to_be);

    $edit = [];
    $form_id = 'past_testhidden_form_mixed_submit_handlers';
    $this->drupalGet($form_id);
    $this->assertRaw('form handler called by ' . $form_id);
    $this->drupalPostForm(NULL, $edit, $button_value);
    $this->assertNoRaw('global submit handler called by ' . $form_id);
    $this->assertText('submit handler called by ' . $form_id);

    $event = $this->getLastEventByMachineName('submit');
    $this->eventToBe = $this->getEventToBe('submit', $form_id, $button_value);
    $values_to_be = [
      'sample_property' => 'sample value',
    ];
    $this->assertSameEvent($event, $this->eventToBe, $form_id, $button_value, $values_to_be);
  }

  /**
   * Tests a form with several submits.
   */
  public function testFormThreeButtons() {
    // Given a simple form with three buttons without specific handlers
    // Submit each button.
    // Check the logs if the submissions where captured.
    // Check the logs which button was pressed each.
    $edit = [];
    $form_id = 'past_testhidden_form_three_buttons';
    $this->drupalGet($form_id);
    $this->assertRaw('form handler called by ' . $form_id);
    $button_value = 'Button 1';
    $this->drupalPostForm(NULL, $edit, $button_value);

    $event = $this->getLastEventByMachineName('submit');
    $this->eventToBe = $this->getEventToBe('submit', $form_id, $button_value);
    $values_to_be = [
      'sample_property' => 'sample value',
      'submit_one' => 'Button 1',
      'submit_two' => 'Button 2',
      'submit_three' => 'Button 3',
    ];
    $this->assertSameEvent($event, $this->eventToBe, $form_id, $button_value, $values_to_be);

    $button_value = 'Button 2';
    $this->drupalPostForm(NULL, $edit, $button_value);

    $event = $this->getLastEventByMachineName('submit');
    $this->eventToBe = $this->getEventToBe('submit', $form_id, $button_value);
    $values_to_be = [
      'sample_property' => 'sample value',
      'submit_one' => 'Button 1',
      'submit_two' => 'Button 2',
      'submit_three' => 'Button 3',
    ];
    $this->assertSameEvent($event, $this->eventToBe, $form_id, $button_value, $values_to_be);

    $button_value = 'Button 3';
    $this->drupalPostForm(NULL, $edit, $button_value);

    $event = $this->getLastEventByMachineName('submit');
    $this->eventToBe = $this->getEventToBe('submit', $form_id, $button_value);
    $values_to_be = [
      'sample_property' => 'sample value',
      'submit_one' => 'Button 1',
      'submit_two' => 'Button 2',
      'submit_three' => 'Button 3',
    ];
    $this->assertSameEvent($event, $this->eventToBe, $form_id, $button_value, $values_to_be);
  }

  /**
   * Tests a form with several submits having submit handlers.
   */
  public function testFormThreeButtonsWithHandlers() {
    // Given a simple form with three buttons.
    // Each of the buttons has an own #submit handler.
    // Submit each button.
    // Check if each specific handler is executed.
    // Check the global handler was not executed.
    // Check the logs if the submissions where captured.
    $edit = [];
    $form_id = 'past_testhidden_form_three_buttons_with_submit_handlers';
    $this->drupalGet($form_id);
    $this->assertRaw('form handler called by ' . $form_id);
    $button_value = 'Button 1';
    $this->drupalPostForm(NULL, $edit, $button_value);
    $this->assertNoRaw('global submit handler called by ' . $form_id);
    $this->assertText('custom submit handler ' . $button_value . ' called by ' . $form_id);

    $event = $this->getLastEventByMachineName('submit');
    $this->eventToBe = $this->getEventToBe('submit', $form_id, $button_value);
    $values_to_be = [
      'sample_property' => 'sample value',
      'submit_one' => 'Button 1',
      'submit_two' => 'Button 2',
      'submit_three' => 'Button 3',
    ];
    $this->assertSameEvent($event, $this->eventToBe, $form_id, $button_value, $values_to_be);

    $button_value = 'Button 2';
    $this->drupalPostForm(NULL, $edit, $button_value);
    $this->assertNoRaw('global submit handler called by ' . $form_id);
    $this->assertText('custom submit handler ' . $button_value . ' called by ' . $form_id);

    $event = $this->getLastEventByMachineName('submit');
    $this->eventToBe = $this->getEventToBe('submit', $form_id, $button_value);
    $values_to_be = [
      'sample_property' => 'sample value',
      'submit_one' => 'Button 1',
      'submit_two' => 'Button 2',
      'submit_three' => 'Button 3',
    ];
    $this->assertSameEvent($event, $this->eventToBe, $form_id, $button_value, $values_to_be);

    $button_value = 'Button 3';
    $this->drupalPostForm(NULL, $edit, $button_value);
    $this->assertNoRaw('global submit handler called by ' . $form_id);
    $this->assertText('custom submit handler ' . $button_value . ' called by ' . $form_id);

    $event = $this->getLastEventByMachineName('submit');
    $this->eventToBe = $this->getEventToBe('submit', $form_id, $button_value);
    $values_to_be = [
      'sample_property' => 'sample value',
      'submit_one' => 'Button 1',
      'submit_two' => 'Button 2',
      'submit_three' => 'Button 3',
    ];
    $this->assertSameEvent($event, $this->eventToBe, $form_id, $button_value, $values_to_be);
  }

  /**
   * Tests a form with type=button submits having submit handlers.
   */
  public function testFormUseButtonAsSubmit() {
    // Capture #type=button submissions.
    $edit = [];
    $form_id = 'past_testhidden_form_normal_button';
    $button_value = 'Button';
    $this->drupalGet($form_id);
    $this->assertRaw('form handler called by ' . $form_id);
    $this->drupalPostForm(NULL, $edit, $button_value);
    $this->assertNoRaw('global submit handler called by ' . $form_id);
    $this->assertNoRaw('custom submit handler called by ' . $form_id);

    $this->assertNull($this->getLastEventByMachineName('submit'));

    // Is logged if we use '#executes_submit_callback' => TRUE.
    $button_value = 'Submittable';
    $this->drupalPostForm(NULL, $edit, $button_value);
    $this->assertNoRaw('global submit handler called by ' . $form_id);
    $this->assertText('custom submit handler called by ' . $form_id);
    $event = $this->getLastEventByMachineName('submit');
    $this->eventToBe = $this->getEventToBe('submit', $form_id, $button_value);
    $values_to_be = [
      'sample_property' => 'sample value',
      'submittable' => 'Submittable',
      'button' => 'Button',
    ];
    $this->assertSameEvent($event, $this->eventToBe, $form_id, $button_value, $values_to_be);
  }

  /**
   * Tests a form validation.
   */
  public function testFormValidation() {
    // Enable validation logging.
    $this->config->set('past_form_log_validations', TRUE)->save();
    // Capture case for a failing validations.
    // Capture validation error message in the past log.
    $edit = ['sample_property' => ''];
    $form_id = 'past_testhidden_form_custom_submit_handler';
    $this->drupalGet($form_id);
    $this->assertRaw('form handler called by ' . $form_id);
    $this->drupalPostForm(NULL, $edit, 'Submit');
    $this->assertNoRaw('global submit handler called by ' . $form_id);
    $this->assertFieldByXPath('//input[contains(@class, "error")]', FALSE, 'Error input form element class found.');

    $this->assertNull($this->getLastEventByMachineName('submit'));

    // Enable validation logging.
    $this->config->set('past_form_log_validations', 1)->save();

    $edit = ['sample_property' => ''];
    $form_id = 'past_testhidden_form_multi_validation';
    $button_value = 'Submit';
    $this->drupalGet($form_id);
    $this->assertRaw('form handler called by ' . $form_id);
    $this->drupalPostForm(NULL, $edit, $button_value);
    $this->assertNoRaw('global submit handler called by ' . $form_id);
    $this->assertFieldByXPath('//input[contains(@class, "error")]', FALSE, 'Error input form element class found.');

    $event = $this->getLastEventByMachineName('validate');
    $this->eventToBe = $this->getEventToBe('validate', $form_id, $button_value, 'Form validation error:');
    $errors_to_be = [
      'sample_property' => [
        'message' => 'Sample Property field is required.',
        'submitted' => '',
      ],
      'another_sample_property' => [
        'message' => 'Another Sample Property field is required.',
        'submitted' => 0,
      ],
      'sample_select' => [
        'message' => 'Sample Select: says, don\'t be a maybe ..',
        'submitted' => '2',
      ],
    ];
    $this->assertSameEvent($event, $this->eventToBe, $form_id, $button_value, [], $errors_to_be);

    $edit = [];
    $form_id = 'past_testhidden_form_custom_validation_only';
    $button_value = 'Submit';
    $this->drupalGet($form_id);
    $this->assertRaw('form handler called by ' . $form_id);
    $this->drupalPostForm(NULL, $edit, $button_value);
    $this->assertNoRaw('global submit handler called by ' . $form_id);
    $this->assertFieldByXPath('//select[contains(@class, "error")]/@id', 'edit-sample-select', 'Error select form element class found.');

    $event = $this->getLastEventByMachineName('validate');
    $this->eventToBe = $this->getEventToBe('validate', $form_id, $button_value, 'Form validation error:');
    $errors_to_be = [
      'sample_select' => [
        'message' => 'Sample Select: says, don\'t be a maybe ..',
        'submitted' => '2',
      ],
    ];
    // @todo wrong!
    $this->assertSameEvent($event, $this->eventToBe, $form_id, $button_value, [], $errors_to_be);

    // Test validation logging of nested form elements.
    $form_id = 'past_testhidden_form_nested';
    $button_value = t('Submit');
    $this->drupalGet($form_id);
    $edit = ['wrapper[field_1]' => 'wrong value'];
    $this->drupalPostForm(NULL, $edit, $button_value);
    // Check for correct validation error messages and CSS class on field.
    $this->assertText(t("Field 1 doesn't contain the right value"), 'Validation error message gets displayed.');
    $this->assertFieldByXPath('//input[contains(@class, "error")]/@id', 'edit-wrapper-field-1', 'Error class was found on textfield 1.');
    // Load latest validation log record and create an artificial that contains
    // exactly what a correct logging is supposed to contain.
    $event = $this->getLastEventByMachineName('validate');
    $this->eventToBe = $this->getEventToBe('validate', $form_id, $button_value, 'Form validation error:');
    $errors_to_be = [
      'wrapper][field_1' => [
        'message' => t("Field 1 doesn't contain the right value"),
        'submitted' => $edit['wrapper[field_1]'],
      ],
    ];
    // Compare artificial event with logged event.
    $this->assertSameEvent($event, $this->eventToBe, $form_id, $button_value, [], $errors_to_be, 'Validation of nested elements was logged correctly.');

    // Test if submission is logged as expected.
    $edit = [
      'wrapper[field_1]' => 'correct value',
      'wrapper[field_2]' => 'some other value',
    ];
    $this->drupalPostForm(NULL, $edit, $button_value);
    $event = $this->getLastEventByMachineName('submit');
    $this->eventToBe = $this->getEventToBe('submit', $form_id, $button_value);
    $values_to_be = [
      'wrapper' => [
        'field_1' => $edit['wrapper[field_1]'],
        'field_2' => $edit['wrapper[field_2]'],
      ],
    ];
    $this->assertSameEvent($event, $this->eventToBe, $form_id, $button_value, $values_to_be, [], 'Submission of nested elements was logged correctly');
  }

  /**
   * Tests a multi step form.
   */
  public function testFormMultistep() {
    // Capture multistep submissions.
    $edit = [];
    $form_id = 'past_testhidden_form_multistep';
    $button_value = 'Next';
    $step = 1;
    $this->drupalGet($form_id);
    $this->assertRaw('form handler step ' . $step . ' called by ' . $form_id);
    $this->drupalPostForm(NULL, $edit, $button_value);
    $this->assertText('global submit handler step ' . $step . ' called by ' . $form_id);
    $step++;
    $this->assertRaw('form handler step ' . $step . ' called by ' . $form_id);

    $event = $this->getLastEventByMachineName('submit');
    $this->eventToBe = $this->getEventToBe('submit', $form_id, $button_value);
    $values_to_be = [
      'sample_property' => 'sample value',
      'next' => 'Next',
    ];

    $this->assertSameEvent($event, $this->eventToBe, $form_id, $button_value, $values_to_be);

    $this->drupalPostForm(NULL, $edit, $button_value);
    $this->assertText('global submit handler step ' . $step . ' called by ' . $form_id);
    $step++;
    $this->assertRaw('form handler step ' . $step . ' called by ' . $form_id);

    $event = $this->getLastEventByMachineName('submit');
    $this->eventToBe = $this->getEventToBe('submit', $form_id, $button_value);
    $values_to_be = [
      'sample_property_2' => 'sample value 2',
      'next' => 'Next',
      'back' => 'Back',
    ];

    $this->assertSameEvent($event, $this->eventToBe, $form_id, $button_value, $values_to_be);

    $button_value = 'Back';
    $this->drupalPostForm(NULL, $edit, $button_value);
    $this->assertText('global submit handler step ' . $step . ' called by ' . $form_id);
    $step--;
    $this->assertRaw('form handler step ' . $step . ' called by ' . $form_id);

    $event = $this->getLastEventByMachineName('submit');
    $this->eventToBe = $this->getEventToBe('submit', $form_id, $button_value);
    $values_to_be = [
      'back' => 'Back',
    ];

    $this->assertSameEvent($event, $this->eventToBe, $form_id, $button_value, $values_to_be);

    $button_value = 'Next';
    $this->drupalPostForm(NULL, $edit, $button_value);
    $this->assertText('global submit handler step ' . $step . ' called by ' . $form_id);
    $step++;
    $this->assertRaw('form handler step ' . $step . ' called by ' . $form_id);

    $event = $this->getLastEventByMachineName('submit');
    $this->eventToBe = $this->getEventToBe('submit', $form_id, $button_value);
    $values_to_be = [
      'back' => 'Back',
    ];

    $this->assertSameEvent($event, $this->eventToBe, $form_id, $button_value, $values_to_be);

    $button_value = 'Submit';
    $this->drupalPostForm(NULL, $edit, $button_value);
    $this->assertText('global submit handler step ' . $step . ' called by ' . $form_id);

    $event = $this->getLastEventByMachineName('submit');
    $this->eventToBe = $this->getEventToBe('submit', $form_id, $button_value);
    $values_to_be = [
      'sample_property_3' => 'sample value 3',
      'submit' => $button_value,
    ];

    $this->assertSameEvent($event, $this->eventToBe, $form_id, $button_value, $values_to_be);
  }

  /**
   * Tests an ajax form.
   */
  public function testFormAjax() {
    // Capture ajax submissions.
    $edit = [];
    $form_id = 'past_testhidden_form_simple_ajax';
    $button_value = 'Submit';
    $this->drupalGet($form_id);
    $this->assertRaw('form handler called by ' . $form_id);
    $values = $this->drupalPostAjaxForm(NULL, $edit, ['op' => $button_value], NULL, [], [], str_replace('_', '-', $form_id));

    $got_global_handler = FALSE;
    $got_custom_handler = FALSE;
    $got_ajax = FALSE;
    foreach ($values as $value) {
      foreach ($value as $key => $val) {
        if ($key == 'data') {
          if (strrpos($val, 'global submit handler called by ' . $form_id) !== FALSE) {
            $got_global_handler = TRUE;
          }
          elseif (strrpos($val, 'custom submit handler called by ' . $form_id) !== FALSE) {
            $got_custom_handler = TRUE;
          }
          elseif (strrpos($val, 'ajax called by ' . $form_id . ' with sample_property containing: sample value') !== FALSE) {
            $got_ajax = TRUE;
          }
        }
      }
    }
    $this->assertFalse($got_global_handler, 'Global submit handler for ' . $form_id . ' was called.');
    $this->assertTrue($got_custom_handler, 'Custom submit handler for ' . $form_id . ' was called.');
    $this->assertTrue($got_ajax, 'Expected AJAX value is in JSON response.');

    $event = $this->getLastEventByMachineName('submit');
    $this->eventToBe = $this->getEventToBe('submit', $form_id, $button_value);
    $values_to_be = [
      'sample_property' => 'sample value',
    ];
    $this->assertSameEvent($event, $this->eventToBe, $form_id, $button_value, $values_to_be);
  }

  /**
   * Tests the include filter.
   */
  public function testFormIdFilter() {
    $edit = [];
    $form_id = 'past_testhidden_form_empty_submit_array';
    $button_value = 'Submit';

    // Test exclusion.
    $this->config->set('past_form_log_form_ids', [''])->save();
    $this->drupalGet($form_id);
    $this->drupalPostForm(NULL, $edit, $button_value);

    // Event shouldn't be logged.
    $this->assertNull($this->getLastEventByMachineName('submit'));

    // Test inclusion.
    $this->config->set('past_form_log_form_ids', [$form_id])->save();
    $this->drupalGet($form_id);
    $this->drupalPostForm(NULL, $edit, $button_value);

    $event = $this->getLastEventByMachineName('submit');
    $this->eventToBe = $this->getEventToBe('submit', $form_id, $button_value);
    $this->assertSameEvent($event, $this->eventToBe, $form_id, $button_value);

    $form_id = 'past_testhidden_form_custom_submit_handler';
    $this->drupalGet($form_id);
    $this->drupalPostForm(NULL, $edit, $button_value);

    // This new event should not be logged, so still the old event should be
    // fetched.
    $event = $this->getLastEventByMachineName('submit');
    $this->assertSameEvent($event, $this->eventToBe, 'past_testhidden_form_empty_submit_array', $button_value);

    // Test inclusion of more then one form_id.
    $this->config
      ->set('past_form_log_form_ids', array_merge($this->config->get('past_form_log_form_ids'), [$form_id]))
      ->save();
    $this->drupalGet($form_id);
    $this->drupalPostForm(NULL, $edit, $button_value);

    // Now the new event should be found.
    $event = $this->getLastEventByMachineName('submit');
    $this->eventToBe = $this->getEventToBe('submit', $form_id, $button_value);
    $this->assertSameEvent($event, $this->eventToBe, $form_id, $button_value);

    // Test wildcard.
    $this->config->set('past_form_log_form_ids', ['*testhidden_form_*'])->save();
    $form_id = 'past_testhidden_form_empty_submit_array';
    $this->drupalGet($form_id);
    $this->drupalPostForm(NULL, $edit, $button_value);

    // Again the first form_id should be found.
    $event = $this->getLastEventByMachineName('submit');
    $this->eventToBe = $this->getEventToBe('submit', $form_id, $button_value);
    $this->assertSameEvent($event, $this->eventToBe, $form_id, $button_value);

    $form_id = 'past_testhidden_form_custom_submit_handler';
    $this->drupalGet($form_id);
    $this->drupalPostForm(NULL, $edit, $button_value);

    // And also the new one.
    $event = $this->getLastEventByMachineName('submit');
    $this->eventToBe = $this->getEventToBe('submit', $form_id, $button_value);
    $this->assertSameEvent($event, $this->eventToBe, $form_id, $button_value);

    // Check if user registration is not logged.
    $this->drupalGet('user/register');
    $register_edit = [
      'name' => $this->randomMachineName(),
      'mail' => $this->randomMachineName() . '@example.com',
    ];
    $register_button_value = t('Create new account');
    $this->drupalPostForm(NULL, $register_edit, $register_button_value);

    // Load last logged submission and check whether it's not the user register
    // submission.
    $event = $this->getLastEventByMachineName('submit');
    $this->assertSameEvent($event, $this->eventToBe, $form_id, $button_value, $edit);

    // Test wildcard that will match all forms.
    $this->config->set('past_form_log_form_ids', ['*'])->save();
    $form_id = 'past_testhidden_form_empty_submit_array';
    $this->drupalGet($form_id);
    $this->drupalPostForm(NULL, $edit, $button_value);

    // Again the first form_id should be found.
    $event = $this->getLastEventByMachineName('submit');
    $this->eventToBe = $this->getEventToBe('submit', $form_id, $button_value);
    $this->assertSameEvent($event, $this->eventToBe, $form_id, $button_value);

    $form_id = 'past_testhidden_form_custom_submit_handler';
    $this->drupalGet($form_id);
    $this->drupalPostForm(NULL, $edit, $button_value);

    // And also the new one.
    $event = $this->getLastEventByMachineName('submit');
    $this->eventToBe = $this->getEventToBe('submit', $form_id, $button_value);
    $this->assertSameEvent($event, $this->eventToBe, $form_id, $button_value);

    // Check if user registration is logged.
    $form_id = 'user_register_form';
    $register_edit = [
      'name' => $this->randomMachineName(),
      'mail' => $this->randomMachineName() . '@example.com',
    ];
    $this->drupalGet('user/register');
    $this->drupalPostForm(NULL, $register_edit, $register_button_value);

    // Check if event was logged.
    $event = $this->getLastEventByMachineName('submit');
    $this->eventToBe = $this->getEventToBe('submit', $form_id, $register_button_value);
    $this->assertSameEvent($event, $this->eventToBe, $form_id, $register_button_value, $register_edit);

    // Test that additional spaces and special characters don't confuse the form
    // ID check.
    $form_id = 'past_testhidden_form_empty_submit_array';
    $this->config->set('past_form_log_form_ids', [$form_id, 'other_form'])->save();
    $this->drupalGet($form_id);
    $this->drupalPostForm(NULL, $edit, $button_value);

    $event = $this->getLastEventByMachineName('submit');
    $this->eventToBe = $this->getEventToBe('submit', $form_id, $button_value);
    $this->assertSameEvent($event, $this->eventToBe, $form_id, $button_value);
  }

  /**
   * Tests the Past event log from UI.
   */
  public function testAdminUi() {
    $this->drupalLogin($this->viewUser);
    $this->createFormEvents();

    // Check that just the form events are displayed.
    $this->drupalGet('admin/reports/past/form');
    $this->assertNoText('Event message #3');

    // Check filters in Past event log.
    $this->assertEqual(1, count($this->xpath('//tbody/tr/td[3][contains(., "form1")]')), 'Filtered by Form ID.');
    $this->assertEqual(4, count($this->xpath('//tbody/tr/td[4][contains(., "operation0")]')), 'Filtered by Operation.');
    $this->drupalGet('admin/reports/past/form', [
      'query' => [
        'argument_data' => 'form2',
      ],
    ]);
    $this->assertEqual(1, count($this->xpath('//tbody/tr/td[3][contains(., "form2")]')), 'Filtered by Form ID.');
    $this->assertEqual(0, count($this->xpath('//tbody/tr/td[3][contains(., "form1")]')), 'Filtered by Form ID.');

    $this->drupalGet('admin/reports/past/form', [
      'query' => [
        'argument_data_1' => 'operation1',
      ],
    ]);
    $this->assertEqual(4, count($this->xpath('//tbody/tr/td[4][contains(., "operation1")]')), 'Filtered by Operation.');
    $this->assertEqual(0, count($this->xpath('//tbody/tr/td[4][contains(., "operation0")]')), 'Filtered by Operation.');

  }

  /**
   * Returns a standard past_form event.
   *
   * @param string $machine_name
   *   The machine name of the event.
   * @param string $form_id
   *   The machine name of the form.
   * @param string $button_value
   *   The string shown on the submit button.
   * @param string $message
   *   (optional) A description of the event.
   *
   * @return PastEvent
   *   The event created.
   */
  public function getEventToBe($machine_name, $form_id, $button_value, $message = 'Form submitted:') {
    $event = past_event_create('past_form', $machine_name, $message . ' ' . $form_id . ', ' . $button_value);
    $event->setSeverity(RfcLogLevel::DEBUG);
    return $event;
  }

  /**
   * Compares two past_form events.
   *
   * @param PastEventInterface $first
   *   The first comparison object.
   * @param PastEventInterface $second
   *   The second comparison object.
   * @param string $form_id
   *   The machine name of the form.
   * @param string $button_value
   *   The string shown on the submit button.
   * @param array $values
   *   (optional) Values to check on 'values' argument.
   * @param array $errors
   *   (optional) Errors to check on 'errors' argument.
   * @param string|null $message
   *   (optional) A description of the event.
   */
  public function assertSameEvent(PastEventInterface $first, PastEventInterface $second, $form_id, $button_value, array $values = [], array $errors = [], $message = NULL) {
    if (!empty($first)) {
      $mismatch = [];

      if ($first->getModule() != $second->getModule()) {
        $mismatch[] = 'module';
      }
      if ($first->getMachineName() != $second->getMachineName()) {
        $mismatch[] = 'machine_name';
      }
      if ($first->getMessage() != $second->getMessage()) {
        $mismatch[] = 'message';
      }
      if ($first->getSeverity() != $second->getSeverity()) {
        $mismatch[] = 'severity';
      }
      if ($first->getArgument('form_id')->getData() != $form_id) {
        $mismatch[] = 'form_id';
      }
      if ($first->getArgument('operation')->getData() != $button_value) {
        $mismatch[] = 'operation';
      }

      if (!empty($values)) {
        $arg_values = $first->getArgument('values')->getData();
        foreach ($values as $k => $v) {
          if (!isset($arg_values[$k]) || $arg_values[$k] != $v) {
            if (is_array($v)) {
              $mismatch[] = 'values: ' . implode(', ', $v);
            }
            else {
              $mismatch[] = 'value: ' . $v;
            }
          }
        }
      }

      if (!empty($errors)) {
        $arg_errors = $first->getArgument('errors')->getData();
        foreach ($errors as $k => $v) {
          if ($v['message'] instanceof TranslatableMarkup) {
            if (!isset($arg_errors[$k]) || $arg_errors[$k]['message']->render() != $v['message']->render()) {
              $mismatch[] = 'error value: ' . $v['message']->render();
            }
          }
          else {
            if (!isset($arg_errors[$k]) || $arg_errors[$k]['message'] != $v['message']) {
              $mismatch[] = 'error value: ' . $v['message'];
            }
          }
        }
      }

      if (!empty($mismatch)) {
        $this->fail('The following properties do not match: ' . implode(', ', $mismatch));
      }

      $this->assertTrue(empty($mismatch), ($message ? $message : t('Event @first is equal to @second.', ['@first' => $first->getMessage(), '@second' => $second->getMessage()])));
    }
    else {
      $this->pass('Event was not checked for equality.');
    }
  }

  /**
   * Creates some sample form events.
   */
  protected function createFormEvents($count = 10) {
    // Prepare some logs.
    $events = [];
    for ($i = 1; $i < $count; $i++) {
      if ($i % 10 == 3) {
        $event = past_event_create('no_past_form_event', $this->randomMachineName(), 'Event message #' . $i);
      }
      else {
        $event = past_event_create('past_form', $this->randomMachineName(), 'Form message #' . $i);
      }
      $event->setReferer('http://example.com/test-referer');
      $event->setLocation('http://example.com/test-location');
      $event->addArgument('form_id', 'form' . ($i % 10));
      $event->addArgument('operation', 'operation' . ($i % 2));
      $event->setSeverity(RfcLogLevel::DEBUG);
      $event->save();
      $events[] = $event;
    }
    return $events;
  }

}
