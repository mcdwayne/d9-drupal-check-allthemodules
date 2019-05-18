<?php

namespace Drupal\abjs\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Datetime\Time;

/**
 * Class for build form test.
 */
class AbjsTestForm extends FormBase {

  /**
   * Provides a class for obtaining system time.
   *
   * @var \Drupal\Component\Datetime\Time
   */
  protected $time;

  /**
   * Provides database connection service.
   *
   * @var \Drupal\Core\Database\Database
   */
  protected $database;

  /**
   * Class constructor.
   */
  public function __construct(Database $database, Time $time) {
    $this->database = $database;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'abjs_test';
  }

  /**
   * Building the form.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The state of forms.
   * @param int $tid
   *   The ID of the item.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $tid = NULL) {
    $form = [];
    $test_name_default = "";
    $test_active_default = 0;
    if (!empty($tid)) {
      // Retrieve the test to prefill the edit form.
      $test_result = $this->database->getConnection()
        ->query('SELECT name, active FROM {abjs_test} WHERE tid = :tid', [':tid' => $tid]);
      $test = $test_result->fetchObject();
      if (empty($test)) {
        $this->messenger()->addMessage($this->t('The requested test does not exist.'), 'error');
        return $form;
      }
      $test_name_default = $test->name;
      $test_active_default = $test->active;
      $form['tid'] = ['#type' => 'value', '#value' => $tid];
    }
    // Because we have many fields with the same values, we have to set
    // #tree to be able to access them.
    $form['#tree'] = TRUE;
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Test Name'),
      '#default_value' => $test_name_default,
      '#size' => 30,
      '#maxlength' => 50,
      '#required' => TRUE,
    ];

    // Make select list of conditions.
    $conditions = $this->database->getConnection()
      ->query("SELECT cid, name FROM {abjs_condition} ORDER BY cid ASC, created DESC");
    $options_array = [0 => $this->t('Select Condition')];
    foreach ($conditions as $condition) {
      $options_array[$condition->cid] = $condition->name . ' (c_' . $condition->cid . ')';
    }

    // Group conditions together, and allow for adding and removing conditions
    // via AJAX incide this fieldset.
    $form['conditions_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Conditions'),
      // Set up the wrapper so that AJAX will be able to replace the fieldset.
      '#prefix' => '<div id="conditions-fieldset-wrapper">',
      '#suffix' => '</div>',
      '#description' => $this->t('Select Conditions for which the test will apply. All conditions must must be satisfied for the test to apply'),
    ];

    $existing_conditions_count = 0;
    if (!$form_state->has('num_conditions')) {
      // On initial load, get the number of condition select fields (1 for add
      // form, query the number for edit form).
      $form_state->set('num_conditions', 1);
      if (!empty($tid)) {
        $existing_conditions = $this->database->getConnection()
          ->query("SELECT cid FROM {abjs_test_condition} WHERE tid = :tid", [':tid' => $tid])
          ->fetchAll();
        if (!empty($existing_conditions)) {
          $existing_conditions_count = count($existing_conditions);
          $form_state->set('num_conditions', $existing_conditions_count);
        }
      }
    }

    // Prefill all the condition select fields that exist.
    for ($i = 0; $i < $existing_conditions_count; $i++) {
      $form['conditions_fieldset']['conditions'][$i] = [
        '#type' => 'select',
        '#title' => $this->t('Select Condition'),
        '#options' => $options_array,
        '#default_value' => $existing_conditions[$i]->cid,
        '#required' => TRUE,
      ];
    }

    // Add number of sesgment fields determined by use of Ajax Add and
    // remove buttons.
    for ($i = $existing_conditions_count; $i < $form_state->get('num_conditions'); $i++) {
      $form['conditions_fieldset']['conditions'][$i] = [
        '#type' => 'select',
        '#title' => $this->t('Select Condition'),
        '#options' => $options_array,
        '#default_value' => 0,
        '#required' => TRUE,
      ];
    }

    // Ajax add button.
    $form['conditions_fieldset']['add_condition'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add'),
      '#name' => 'add-condition',
      '#submit' => ['::abjsAjaxAddCondition'],
      '#ajax' => [
        'callback' => '::abjsAjaxConditionsCallback',
        'wrapper' => 'conditions-fieldset-wrapper',
      ],
      '#limit_validation_errors' => [],
    ];

    // Ajax Remove button.
    if ($form_state->get('num_conditions') > 1) {
      $form['conditions_fieldset']['remove_condition'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove'),
        '#name' => 'remove-condition',
        '#submit' => ['::abjsAjaxRemoveCondition'],
        '#ajax' => [
          'callback' => '::abjsAjaxConditionsCallback',
          'wrapper' => 'conditions-fieldset-wrapper',
        ],
        '#limit_validation_errors' => [],
      ];
    }

    // Now do the same for experiences.
    // Make select list of experiences.
    $experiences = $this->database->getConnection()
      ->query("SELECT eid, name FROM {abjs_experience} ORDER BY changed DESC, created DESC");
    $options_array = [0 => $this->t('Select Experience')];
    foreach ($experiences as $experience) {
      $options_array[$experience->eid] = $experience->name . ' (e_' . $experience->eid . ')';
    }

    // Group experiences together, and allow for adding and removing experiences
    // via AJAX incide this fieldset.
    $form['experiences_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Experiences'),
      // Set up the wrapper so that AJAX will be able to replace the fieldset.
      '#prefix' => '<div id="experiences-fieldset-wrapper">',
      '#suffix' => '</div>',
      '#description' => $this->t('Select one or more Experiences for the test, and assign fractions to each Experience (e.g. 1/2, 1/3, 0, 1, 0.5, .95, etc...). You cannot use the same Experience ID twice in the same test, so you must duplicate an Experience to use it twice.'),
    ];
    $existing_experiences_count = 0;
    if (!$form_state->has('num_experiences')) {
      // On initial load, get the number of experience select fields (1 for add
      // form, query the number for edit form).
      $form_state->set('num_experiences', 1);
      if (!empty($tid)) {
        $existing_experiences = $this->database->getConnection()
          ->query("SELECT eid, fraction FROM {abjs_test_experience} WHERE tid = :tid",
            [':tid' => $tid]
          )->fetchAll();
        if (!empty($existing_experiences)) {
          $existing_experiences_count = count($existing_experiences);
          $form_state->set('num_experiences', $existing_experiences_count);
        }
      }
    }

    // Prefill all the experience select fields that exist, including fractions
    // for each experience.
    for ($i = 0; $i < $existing_experiences_count; $i++) {
      $form['experiences_fieldset']['experiences'][$i]['experience'] = [
        '#type' => 'select',
        '#title' => $this->t('Experience %i', ['%i' => $i + 1]),
        '#options' => $options_array,
        '#default_value' => $existing_experiences[$i]->eid,
        '#required' => TRUE,
      ];
      $form['experiences_fieldset']['experiences'][$i]['fraction'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Experience %i Fraction', ['%i' => $i + 1]),
        '#default_value' => $existing_experiences[$i]->fraction,
        '#size' => 5,
        '#maxlength' => 10,
        '#required' => TRUE,
      ];
    }

    // Add number of experience fields determined by use of Ajax Add and remove
    // buttons.
    for ($i = $existing_experiences_count; $i < $form_state->get('num_experiences'); $i++) {
      $form['experiences_fieldset']['experiences'][$i]['experience'] = [
        '#type' => 'select',
        '#title' => $this->t('Experience %i', ['%i' => $i + 1]),
        '#options' => $options_array,
        '#default_value' => 0,
        '#required' => TRUE,
      ];
      $form['experiences_fieldset']['experiences'][$i]['fraction'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Experience %i Fraction', ['%i' => $i + 1]),
        '#default_value' => '',
        '#size' => 5,
        '#maxlength' => 10,
        '#required' => TRUE,
      ];
    }

    // Ajax add button.
    $form['experiences_fieldset']['add_experience'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add'),
      '#name' => 'add-experience',
      '#submit' => ['::abjsAjaxAddExperience'],
      '#ajax' => [
        'callback' => '::abjsAjaxExperiencesCallback',
        'wrapper' => 'experiences-fieldset-wrapper',
      ],
      '#limit_validation_errors' => [],
    ];

    // Ajax Remove button.
    if ($form_state->get('num_experiences') > 1) {
      $form['experiences_fieldset']['remove_experience'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove'),
        '#name' => 'remove-experience',
        '#submit' => ['::abjsAjaxRemoveExperience'],
        '#ajax' => [
          'callback' => '::abjsAjaxExperiencesCallback',
          'wrapper' => 'experiences-fieldset-wrapper',
        ],
        '#limit_validation_errors' => [],
      ];
    }

    // Add selector for activating/deactivating test.
    $form['active'] = [
      '#type' => 'select',
      '#title' => $this->t('Status'),
      '#options' => [
        0 => $this->t('Inactive'),
        1 => $this->t('Active'),
      ],
      '#default_value' => $test_active_default,
    ];

    // Save test.
    $form['actions']['save'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#weight' => 5,
      '#validate' => ['::validateTest'],
      '#submit' => ['::saveTest'],
      '#attributes' => ['class' => ["button button-action button--primary"]],
    ];

    // Cancel test and return to admin tests page.
    $form['actions']['cancel'] = [
      '#type' => 'submit',
      '#value' => $this->t('Cancel'),
      '#weight' => 10,
      '#submit' => ['::cancelTest'],
      '#limit_validation_errors' => [],
    ];

    // Delete test.
    if (!empty($tid)) {
      $form['actions']['delete'] = [
        '#type' => 'submit',
        '#value' => $this->t('Delete'),
        '#weight' => 15,
        '#submit' => ['::deleteTest'],
      ];
    }
    return $form;
  }

  /**
   * Adds condition select fields to AbjsTestForm.
   */
  public function abjsAjaxAddCondition(array $form, FormStateInterface $form_state) {
    $form_state->set('num_conditions', $form_state->get('num_conditions') + 1);
    $form_state->setRebuild();
  }

  /**
   * Assigns Ajax changes to conditions fieldset in AbjsTestForm.
   */
  public function abjsAjaxConditionsCallback(array $form, FormStateInterface $form_state) {
    return $form['conditions_fieldset'];
  }

  /**
   * Removes condition select fields to AbjsTestForm.
   */
  public function abjsAjaxRemoveCondition(array $form, FormStateInterface $form_state) {
    if ($form_state->get('num_conditions') > 1) {
      $form_state->set('num_conditions', $form_state->get('num_conditions') - 1);
    }
    $form_state->setRebuild();
  }

  /**
   * Adds experience select fields and fractions to AbjsTestForm.
   */
  public function abjsAjaxAddExperience(array $form, FormStateInterface $form_state) {
    $form_state->set('num_experiences', $form_state->get('num_experiences') + 1);
    $form_state->setRebuild();
  }

  /**
   * Assigns Ajax changes to experiences fieldset in AbjsTestForm.
   */
  public function abjsAjaxExperiencesCallback(array $form, FormStateInterface $form_state) {
    return $form['experiences_fieldset'];
  }

  /**
   * Removes experience select fields and fractions to AbjsTestForm.
   */
  public function abjsAjaxRemoveExperience(array $form, FormStateInterface $form_state) {
    if ($form_state->get('num_experiences') > 1) {
      $form_state->set('num_experiences', $form_state->get('num_experiences') - 1);
    }
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

  /**
   * Validate the form.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The state of forms.
   */
  public function validateTest(array &$form, FormStateInterface $form_state) {
    for ($i = 0; $i < count($form_state->getValue(['experiences_fieldset', 'experiences'])); $i++) {
      if (!preg_match('#^[0-9./]+$#', $form_state->getValue(['experiences_fieldset', 'experiences'])[$i]['fraction'])) {
        $form_state->setErrorByName("experiences_fieldset][experiences][$i][fraction", $this->t('Invalid character used in Experience @i Fraction. Only numbers, decimals, and slashes are allowed. Other characters, including spaces, are not allowed.', ['@i' => $i + 1]));
      }
    }
  }

  /**
   * Save data.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The state of forms.
   */
  public function saveTest(array &$form, FormStateInterface $form_state) {
    $user = $this->currentUser();
    if ($form_state->hasValue('tid')) {
      // This is an existing test, so update instead of insert.
      $tid = $form_state->getValue('tid');
      $this->database->getConnection()->update('abjs_test')
        ->fields([
          'name' => $form_state->getValue('name'),
          'active' => $form_state->getValue('active'),
          'changed' => $this->time->getRequestTime(),
          'changed_by' => $user->id(),
        ])->condition('tid', $tid, '=')
        ->execute();

      // Delete all entries in the test-condition and test-experience tables to
      // make life easy. Re-insert them later in this function.
      $this->database->getConnection()->delete('abjs_test_condition')
        ->condition('tid', $tid)
        ->execute();
      $this->database->getConnection()->delete('abjs_test_experience')
        ->condition('tid', $tid)
        ->execute();
    }
    else {
      // This is a new test, so insert it.
      $tid = $this->database->getConnection()->insert('abjs_test')
        ->fields([
          'name' => $form_state->getValue('name'),
          'active' => $form_state->getValue('active'),
          'created' => $this->time->getRequestTime(),
          'created_by' => $user->id(),
          'changed' => $this->time->getRequestTime(),
          'changed_by' => $user->id(),
        ])->execute();
    }

    // Whether new or existing test, insert conditions and experiences for this
    // test into the test-condition and test-experience tables. If this is an
    // existing test, an earlier step deleted all the existing rows for this
    // test in these tables. Using db_merge instead of db_insert in case
    // multiple of the same condition or experience were entered, in which case
    // this will collapse them to one.
    foreach ($form_state->getValue(['conditions_fieldset', 'conditions']) as $cid) {
      if ($cid > 0) {
        $this->database->getConnection()->merge('abjs_test_condition')
          ->key(['tid' => $tid, 'cid' => $cid])
          ->fields(['tid' => $tid, 'cid' => $cid])->execute();
      }
    }
    foreach ($form_state->getValue(['experiences_fieldset', 'experiences']) as $experience) {
      if (isset($experience['experience']) && $experience['experience'] > 0) {
        $this->database->getConnection()->merge('abjs_test_experience')
          ->key(['tid' => $tid, 'eid' => $experience['experience']])
          ->fields([
            'tid' => $tid,
            'eid' => $experience['experience'],
            'fraction' => $experience['fraction'],
          ])->execute();
      }
    }
    $msg = $form_state->hasValue('tid') ? $this->t("Successfully updated test") : $this->t("Successfully saved new test");
    $this->messenger()->addMessage($msg);
    $form_state->setRedirect('abjs.test_admin');
  }

  /**
   * Cancel the action.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The state of forms.
   */
  public function cancelTest(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('abjs.test_admin');
  }

  /**
   * Delete item.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The state of forms.
   */
  public function deleteTest(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('abjs.test_delete_confirm_form', [
      'tid' => $form_state->getValue('tid'),
    ]);
  }

}
