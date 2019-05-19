<?php

namespace Drupal\simple_a_b\Form;

use Drupal\block_content\Entity\BlockContent;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Defines a form that adds/edits tests.
 */
class SimpleABTestForm extends FormBase {

  protected $fieldTestPrepend = 'test_field_';

  protected $fieldDataPrepend = 'data_field_';

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'simple_a_b_test';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param int $tid
   *   A tid used for edits.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $tid = -1) {

    // Try to load the tid.
    $loaded_test = static::loadTestData($tid);
    // This is used for if we are using the form in edit mode.
    $edit_mode = isset($loaded_test['name']) ? TRUE : FALSE;

    // If we have a tid & the data returned is empty,
    // stop the form and display an error message.
    if ($tid !== -1 && empty($loaded_test)) {

      drupal_set_message(t('No test could be found'), 'error');

      return $form;
    }

    if (empty($form_state->getValue($this->fieldTestPrepend . 'type'))) {
      $test_type = $this->simpleAbIsset($loaded_test['type']);
    }
    else {
      $test_type = $form_state->getValue($this->fieldTestPrepend . 'type');
    }

    // Test details.
    $form['test'] = [
      '#type' => 'details',
      '#title' => t('Test information'),
      '#description' => t('Administrative information.'),
      '#open' => TRUE,
    ];

    // Test name.
    $form['test'][$this->fieldTestPrepend . 'name'] = [
      '#type' => 'textfield',
      '#title' => t('Name'),
      '#description' => t('Administrative name'),
      '#default_value' => $this->simpleAbIsset($loaded_test['name']),
      '#required' => TRUE,
    ];

    // Test description.
    $form['test'][$this->fieldTestPrepend . 'description'] = [
      '#type' => 'textfield',
      '#title' => t('Description'),
      '#default_value' => $this->simpleAbIsset($loaded_test['description']),
      '#description' => t('Administrative description'),
    ];

    $entityTypes = static::getTypes();

    // Test type.
    $form['test'][$this->fieldTestPrepend . 'type'] = [
      '#type' => 'select',
      '#title' => t('Type'),
      '#default_value' => $test_type,
      '#options' => $entityTypes['options'],
      '#description' => $entityTypes['description'],
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::loadCorrectEntityAutoComplete',
        'effect' => 'fade',
        'event' => 'change  ',
        'wrapper' => 'test-field-eid-container',
        'progress' => [
          'type' => 'throbber',
          'message' => 'loading',
        ],
      ],
    ];

    // The eid container.
    $form['test'][$this->fieldTestPrepend . 'eid_container'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'test-field-eid-container'],
    ];

    // Test entity id.
    $form['test'][$this->fieldTestPrepend . 'eid_container'][$this->fieldTestPrepend . 'eid'] = [
      '#type' => 'entity_autocomplete',
      '#title' => t('Entity'),
      '#target_type' => static::getEntityType($test_type),
      '#description' => static::getEntityDescription($test_type),
      '#default_value' => BlockContent::load($this->simpleAbIsset($loaded_test['eid'], 0)),
      '#disabled' => static::getEntityDisabledState($test_type),
      '#required' => TRUE,
    ];

    // Test enabled status.
    $form['test'][$this->fieldTestPrepend . 'enabled'] = [
      '#type' => 'radios',
      '#title' => t('Enabled'),
      '#description' => t('Enable or disable this test'),
      '#default_value' => $this->simpleAbIsset($loaded_test['enabled'], 0),
      '#options' => [
        1 => t('Yes'),
        0 => t('No'),
      ],
      '#required' => TRUE,
    ];

    // Data information.
    $form['variations'] = [
      '#type' => 'details',
      '#title' => t('Variations'),
      '#description' => t('Each variation that will be tested against the original, minimum of 1 variation is required.'),
      '#open' => TRUE,
    ];

    // Variation content field.
    $form['variations'][$this->fieldDataPrepend . 'content'] = [
      '#type' => 'text_format',
      '#format' => 'full_html',
      '#title' => t('Replacement content'),
      '#description' => t('This will be the content that replaces the original content'),
      '#default_value' => $this->simpleAbIsset($loaded_test['content']['value']),
    ];

    // $form['extra-tabs'] = [
    // '#type' => 'vertical_tabs',
    // '#default_tab' => 'edit-publication',
    // ];
    //
    // $form['conditions'] = [
    // '#type' => 'details',
    // '#title' => $this->t('Conditions'),
    // '#group' => 'extra-tabs',
    // ];
    //
    // $form['reports'] = [
    // '#type' => 'details',
    // '#title' => $this->t('Reporting'),
    // '#group' => 'extra-tabs',
    // ];
    //
    // $form['settings'] = [
    // '#type' => 'details',
    // '#title' => $this->t('Settings'),
    // '#group' => 'extra-tabs',
    // ];.
    // Place to hold the actions.
    $form['actions'] = ['#type' => 'actions'];

    // Submit button.
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $edit_mode ? t('Update') : t('Add'),
      '#attributes' => ['class' => ['button--primary']],
    ];

    // $form['actions']['preview'] = [
    // '#type' => 'submit',
    // '#value' => t('Preview'),
    // ];.
    // If edit mode enabled.
    if ($edit_mode) {

      // If we are in edit mode show up the delete button.
      $form['actions']['delete'] = [
        '#markup' => "<a href='/admin/config/user-interface/simple-a-b/" . $tid . "/delete' class='button button--danger'>" . t('Delete') . "</a>",
        '#allowed_tags' => ['a'],
      ];

      // Hidden field for the tid.
      $form[$this->fieldTestPrepend . 'tid'] = [
        '#type' => 'hidden',
        '#value' => $this->simpleAbIsset($loaded_test['tid']),
      ];

      // Hidden field for the did.
      $form[$this->fieldDataPrepend . 'did'] = [
        '#type' => 'hidden',
        '#value' => $this->simpleAbIsset($loaded_test['did']),
      ];
    }

    // Hidden flag to check of edit mode.
    $form['edit_mode'] = [
      '#type' => 'hidden',
      '#value' => $edit_mode ? 'true' : 'false',
    ];

    return $form;
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $test_data = [];
    $data_data = [];
    $edit_mode = FALSE;

    // Loop thought all the get values.
    // Pulling out only the ones that have been set as values in the form above.
    foreach ($form_state->getValues() as $key => $value) {

      // Find all the rest of the form data.
      if (strpos($key, $this->fieldTestPrepend) !== FALSE) {
        $key = str_replace($this->fieldTestPrepend, '', $key);
        $test_data[$key] = $value;
      }

      if (strpos($key, $this->fieldDataPrepend) !== FALSE) {
        $key = str_replace($this->fieldDataPrepend, '', $key);
        $data_data[$key] = $value;
      }

      // Setup edit mode.
      if ($key === 'edit_mode') {
        $edit_mode = ($value === 'true') ? TRUE : FALSE;
      }
    }

    // If we are not trying to edit, we will try and create!
    if (!$edit_mode) {
      // Try to create a new test in the database.
      $tid = \Drupal::service('simple_a_b.storage.test')
        ->create($test_data, $data_data);

      if ($tid === -1) {
        // If we don't get back a positive tid, display the error message.
        drupal_set_message(t('Error creating new test'), 'error');
      }
      else {
        // Otherwise display positive message.
        drupal_set_message(t('New test "@name" has been created', ['@name' => $test_data['name']]), 'status');

        // Redirect back to viewing all tests.
        $url = Url::fromRoute('simple_a_b.view_tests');
        $form_state->setRedirectUrl($url);
      }
    }
    // Else we can update.
    else {
      // Set tid and remove it from the $data array.
      $tid = $test_data['tid'];
      unset($test_data['tid']);

      $did = $data_data['did'];
      $data_data['tid'] = $tid;
      unset($data_data['did']);

      // Try to update the existing test.
      $update = \Drupal::service('simple_a_b.storage.test')
        ->update($tid, $did, $test_data, $data_data);

      // If status is not true then error.
      if ($update != TRUE) {
        drupal_set_message(t('Error updating test'), 'error');
      }
      else {
        // Otherwise display positive message.
        drupal_set_message(t('"@name" has been updated', ['@name' => $test_data['name']]), 'status');

        // Redirect back to viewing all tests.
        $url = Url::fromRoute('simple_a_b.view_tests');
        $form_state->setRedirectUrl($url);
      }
    }
  }

  /**
   * Loads in the correct entity selector based upon the type selected.
   *
   * @param array $form
   *   Array form object.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Array form state object.
   *
   * @return mixed
   *   Returns an entity collector.
   */
  public function loadCorrectEntityAutoComplete(array &$form, FormStateInterface $form_state) {
    return $form['test'][$this->fieldTestPrepend . 'eid_container'];
  }

  /**
   * Load a tests information used for amending edits.
   *
   * @param int $tid
   *   Optional int to load test id.
   *
   * @return array
   *   Loads information about a test.
   */
  protected static function loadTestData($tid = -1) {
    $output = [];

    // If there is no tid, then simply return empty array.
    if ($tid === -1) {
      return $output;
    }

    // Run a fetch looking up the test id.
    $tests = \Drupal::service('simple_a_b.storage.data')->fetch($tid);

    // If we find any tests,
    // set it to the output after converting it to an array.
    if (count($tests) > 0) {
      // There should only be one found.
      $output = (array) $tests;
    }

    // Return the array.
    return $output;
  }

  /**
   * Using the plugin manger looks for any test types.
   *
   * @return array
   *   Return a list of the entity types.
   */
  protected static function getTypes() {
    $output = [];
    $options = [];

    // Default of none.
    $options['_none'] = t('- none -');

    $manager = \Drupal::service('plugin.manager.simpleab.type');
    $plugins = $manager->getDefinitions();

    // If we have some plugin's, loop them to create a list.
    if (!empty($plugins)) {
      foreach ($plugins as $test) {
        $instance = $manager->createInstance($test['id']);
        $options[$instance->getId()] = $instance->getName();
      }
    }

    // Add the options to the array.
    $output['options'] = $options;

    // Count options less than one give link to enable modules.
    if (count($options) > 1) {
      $output['description'] = t('What kind of entity to run the a/b test');
    }
    else {
      $module_path = '/admin/modules';
      $output['description'] = t('No entity types could be found. Please <a href="@simple-ab-modules">enable</a> at least one.', ['@simple-ab-modules' => $module_path]);
    }

    return $output;
  }

  /**
   * Returns the selected entity type.
   *
   * @param string $type
   *   Request for the type of entity.
   *
   * @return string
   *   returns back the entity type for the collection field
   */
  protected static function getEntityType($type) {
    $manager = \Drupal::service('plugin.manager.simpleab.type');
    $plugins = $manager->getDefinitions();

    // Loop thought the plugins.
    if (!empty($plugins)) {
      foreach ($plugins as $test) {
        $instance = $manager->createInstance($test['id']);
        if ($type === $instance->getId()) {
          return $instance->getEntityType();
        }
      }
    }

    // Returns user as default - this should be disabled anyway.
    return 'user';
  }

  /**
   * Returns the entity description.
   *
   * @param string $type
   *   Request for the entity Description.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   Returns some translatable string.
   */
  protected static function getEntityDescription($type) {
    $manager = \Drupal::service('plugin.manager.simpleab.type');
    $plugins = $manager->getDefinitions();

    // Loop thought the plugins.
    if (!empty($plugins)) {
      foreach ($plugins as $test) {
        $instance = $manager->createInstance($test['id']);
        if ($type === $instance->getId()) {
          // Returns custom description.
          return $instance->getEntityDescription();
        }
      }
    }

    // Returns default description.
    return t('No type is selected, please select one');
  }

  /**
   * Returns if the entity field is disabled or not.
   *
   * @param string $type
   *   The current entity stype.
   *
   * @return bool
   *   Returns if the entity is disabled
   */
  protected static function getEntityDisabledState($type) {

    if ($type === "_none" || $type === "") {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * A simple wrapper for isset to make it shorter to test.
   *
   * @param string $value
   *   The value to check if isset.
   * @param string $default_response
   *   A default response if not set.
   *
   * @return string
   *   returns a value or default response
   */
  private function simpleAbIsset(&$value, $default_response = '') {
    return isset($value) ? $value : $default_response;
  }

}
