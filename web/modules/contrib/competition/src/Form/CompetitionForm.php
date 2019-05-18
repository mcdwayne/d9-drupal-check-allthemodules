<?php

namespace Drupal\competition\Form;

use Drupal\competition\CompetitionInterface;
use Drupal\competition\CompetitionJudgingTrait;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Component\Render\PlainTextOutput;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class CompetitionForm.
 *
 * @package Drupal\competition\Form
 */
class CompetitionForm extends BundleEntityFormBase {

  use CompetitionJudgingTrait;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;
  /**
   * The config factory's competition.settings.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configSettings;

  /**
   * Constructs the NodeTypeForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entity_manager, ConfigFactory $config_factory) {
    $this->entityManager = $entity_manager;
    $this->configSettings = $config_factory
      ->get('competition.settings')
      ->get();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // Pass values to validate / submit functions
    // in a hierarchical array (for judging rounds).
    $form['#tree'] = TRUE;

    $competition = $this->entity;

    // Store the original entity in form state - allows accessing original
    // values in submit handler, by which point $this->entity has already been
    // overwritten with the current form state values.
    // @see ::save()
    $form_state->set('entity_original', $competition);

    // Get current entry limit settings for the competition.
    $limits = $competition->getEntryLimits();

    // Get long description items for the competition.
    $longtext = $competition->getLongtext();

    // Get judging settings for the competition.
    $judging = $competition->getJudging();

    // Get a list of managed fields for this competition.
    $field_definitions = $this->entityManager->getFieldDefinitions('competition_entry', $competition->id());
    $field_names = array_keys($field_definitions);
    $field_managed = array_filter($field_names, function ($name) {
      return (strpos($name, 'field_') === 0);
    });
    $field_managed = array_values($field_managed);

    $field_options = array();
    foreach ($field_managed as $name) {
      $field_options[$name] = $field_definitions[$name]->getLabel();
    }

    // Set form options.
    $form = array_merge($form, array(
      '#title' => ($this->operation == 'add' ? $this->t('Add competition') : $this->t('Edit %label competition', ['%label' => $competition->label()])),
      '#attached' => array(
        'library' => [
          'competition/competition',
        ],
      ),
    ));

    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#maxlength' => 255,
      '#default_value' => $competition->label(),
      '#description' => $this->t("The human-readable name of this competition. This name must be unique."),
      '#required' => TRUE,
    );

    $form['type'] = array(
      '#type' => 'machine_name',
      '#default_value' => $competition->id(),
      '#machine_name' => array(
        'exists' => '\Drupal\competition\Entity\Competition::load',
      ),
      '#disabled' => !$competition->isNew(),
    );

    $form['cycle'] = array(
      '#type' => 'select',
      '#title' => $this->t('Active cycle'),
      '#description' => $this->t('Select the active cycle for this competition. @competition_cycles.', array(
        '@competition_cycles' => Link::fromTextAndUrl($this->t('Learn more about cycles'), Url::fromRoute('entity.competition.settings', [], [
          'fragment' => 'edit-cycles',
        ]))->toString(),
      )),
      '#required' => TRUE,
      '#options' => $this->configSettings['cycles'],
      '#default_value' => $competition->getCycle(),
    );

    $form['status'] = array(
      '#type' => 'select',
      '#title' => $this->t('Status'),
      '#required' => TRUE,
      '#options' => array(
        CompetitionInterface::STATUS_OPEN => $this->t('Open'),
        CompetitionInterface::STATUS_CLOSED => $this->t('Closed'),
      ),
      '#default_value' => $competition->getStatus(),
      '#description' => $this->t('<strong>Note:</strong> If user registration is required for this competition, you may want to <a href=":url-account-settings">allow registration by administrators only</a> while the competition is closed.', [
        ':url-account-settings' => Url::fromRoute('entity.user.admin_form')->toString(),
      ]),
    );

    $form['additional_settings'] = array(
      '#type' => 'vertical_tabs',
    );

    $form['entry_limits'] = array(
      '#type' => 'details',
      '#title' => $this->t('Entry settings'),
      '#group' => 'additional_settings',
      '#open' => TRUE,
    );

    $form['entry_limits']['criteria'] = array(
      '#type' => 'details',
      '#title' => $this->t('Entry requirements'),
      '#description' => '<p>' . $this->t('Choose which criteria are required for a valid competition entry.') . '</p>',
      '#open' => TRUE,
    );

    $form['entry_limits']['criteria']['require_user'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Require that users register (or login) before submitting competition entries.'),
      '#description' => $this->t('IMPORTANT: remember to @account_settings if using this requirement.', [
        '@account_settings' => Link::fromTextAndUrl($this->t('allow visitors to create accounts'), Url::fromRoute('entity.user.admin_form', [], [
          'fragment' => 'edit-registration-cancellation',
        ]))->toString(),
      ]),
      '#default_value' => (!empty($limits->require_user) ? $limits->require_user : FALSE),
    );

    $form['entry_limits']['criteria']['email_as_username'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Use email address only, instead of a username, for user registration and login'),
      '#description' => $this->t('When enabled, users will register and log in using only an email address, instead of the standard Drupal username. (Behind the scenes, the email address is stored also as the account username.)'),
      '#default_value' => (isset($limits->email_as_username) ? $limits->email_as_username : TRUE),
    );

    $form['entry_limits']['criteria']['allow_partial_save'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Allow users to "Save for later"?'),
      '#description' => $this->t('This setting allows users to bypass entry validation until they choose to "Save and enter", finalizing their entry. By leaving this option unchecked, entries may not be edited after being submitted.'),
      '#default_value' => !empty($limits->allow_partial_save),
    );

    $form['entry_limits']['criteria']['fields'] = array(
      '#type' => 'select',
      '#title' => $this->t('Unique entry fields'),
      '#description' => $this->t('Select one or more fields. The values will be used to uniquely group multiple entries for the same user.<br><br>For example, choosing: <i>First name</i>, <i>Last name</i> and <i>Postal code</i> would group together all entries where the name and postal code values were identical across entries. Choose field combinations carefully &ndash; groups will count towards the specified entry limit.'),
      '#required' => FALSE,
      '#options' => $field_options,
      '#default_value' => (!empty($limits->fields) ? $limits->fields : NULL),
      '#multiple' => TRUE,
    );

    $form['entry_limits']['criteria']['inline'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array(
          'container-inline',
          'clearfix',
          'form-item',
        ),
      ),
      '#prefix' => '<strong class="js-form-required form-required">' . $this->t('Entry limit') . '</strong>',
      '#suffix' => $this->t('Note: a value of <i>0</i> indicates unlimited entries.'),
    );

    $form['entry_limits']['criteria']['inline']['count'] = array(
      '#type' => 'number',
      '#title' => $this->t('Limit entry per user'),
      '#title_display' => 'none',
      '#prefix' => $this->t('Limit user to'),
      '#suffix' => $this->t('entries per'),
      '#size' => 3,
      '#min' => 0,
      '#default_value' => (!empty($limits->count) ? $limits->count : 1),
      '#required' => TRUE,
    );

    $form['entry_limits']['criteria']['inline']['interval'] = array(
      '#type' => 'select',
      '#title' => $this->t('Limit entry interval'),
      '#title_display' => 'none',
      '#options' => $this->configSettings['intervals'],
      '#default_value' => (!empty($limits->interval) ? $limits->interval : 0),
      '#required' => TRUE,
    );

    $form['entry_limits']['reentry'] = array(
      '#type' => 'details',
      '#title' => $this->t('Re-Entry settings'),
      '#description' => '<p>' . $this->t('Define criteria for competition re-entry. This is most useful for sweepstakes that have a frequent entry interval.') . '</p>',
      '#open' => TRUE,
    );

    $form['entry_limits']['reentry']['field_reentry'] = array(
      '#type' => 'select',
      '#title' => $this->t('Unique entry field'),
      '#description' => $this->t("Choose a single field that will contain a unique value for each entry. The value will be used to find a user's previous entry.<br><br>For example choosing <i>Email</i> would allow a user to re-enter the competition at a later time, by entering only their email address. All other field values from the previous entry will be copied to the new entry.<br><br>The re-entry option will not be available if left blank."),
      '#options' => $field_options,
      '#required' => FALSE,
      '#empty_value' => '',
      '#default_value' => (!empty($limits->field_reentry) ? $limits->field_reentry : ''),
    );

    $form['entry_limits']['archive_settings'] = array(
      '#type' => 'details',
      '#title' => $this->t('Archive settings'),
      '#description' => '<p>' . $this->t('Select all competition cycles that are closed and should be archived. Typically, these will all be in the past.') . '</p>',
      '#open' => TRUE,
    );

    $cycles_archived = $competition->getCyclesArchived();
    $archives_description = $this->t('Archived competitions are listed on the archives page for reporting purposes.<br><br>Hold down CTRL or CMD to choose multiple options above.');
    if (!empty($competition->id())) {
      $archives_description = $this->t('Archived competitions are listed on the @archives_link for reporting purposes.<br><br>Hold down CTRL or CMD to choose multiple options above.', [
        '@archives_link' => Link::fromTextAndUrl($this->t('archives page'), Url::fromRoute('entity.competition_entry.archives', ['competition' => $competition->id(), 'cycle' => 'all'], []))->toString(),
      ]);
    }
    $form['entry_limits']['archive_settings']['cycles_archived'] = array(
      '#type' => 'select',
      '#title' => $this->t('Archived Cycles'),
      '#multiple' => TRUE,
      '#description' => $archives_description,
      '#options'  => $this->configSettings['cycles'],
      '#default_value' => $cycles_archived,
    );

    $form['longtext'] = array(
      '#type' => 'details',
      '#title' => $this->t('Display text'),
      '#group' => 'additional_settings',
      '#open' => TRUE,
    );

    $form['longtext']['description'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#description' => $this->t('This text will be displayed on the "Enter competition" page.'),
      '#default_value' => (!empty($longtext->description) ? $longtext->description : FALSE),
    );

    $form['longtext']['terms_of_use'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Terms of Use'),
      '#description' => $this->t('This text will be displayed on the "Enter competition" page.'),
      '#default_value' => (!empty($longtext->terms_of_use) ? $longtext->terms_of_use : FALSE),
    );

    $form['longtext']['confirmation'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Entry confirmation'),
      '#description' => $this->t('This text will be displayed when a user successfully enters the competition.'),
      '#default_value' => (!empty($longtext->confirmation) ? $longtext->confirmation : FALSE),
    );

    $form['longtext']['confirmation_email'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Entry confirmation email'),
      '#description' => $this->t('To send a confirmation email to the user account address upon finalized entry submission, enter email text here.<br/>
         If blank, NO email will be sent.<br/>
         Only plain text (no HTML) is supported.'),
      '#default_value' => (!empty($longtext->confirmation_email) ? $longtext->confirmation_email : FALSE),
    );

    // --- Judging settings.
    $num_rounds = $form_state->get('num_rounds');
    if (empty($num_rounds)) {
      $num_rounds = isset($judging->rounds) ? count($judging->rounds) : 0;
      $form_state->set('num_rounds', $num_rounds);
    }

    $judging_data_exists = $competition->hasJudgingData();

    $form['judging'] = array(
      '#type' => 'details',
      '#title' => $this->t('Judging'),
      '#group' => 'additional_settings',
      '#open' => TRUE,
    );

    // Some settings are configured elsewhere - but Drupal overwrites entire
    // config entity with this form's values. Preserve those settings here
    // via server-side-only value elements:
    // 1. Judges/rounds assignments.
    // @see CompetitionJudgesRoundsSetupForm
    $form['judging']['judges_rounds'] = array(
      '#type' => 'value',
      '#value' => !empty($judging->judges_rounds) ? $judging->judges_rounds : [],
    );

    // 2. Active round.
    // @see CompetitionJudgingRoundWorkflowForm
    $form['judging']['active_round'] = array(
      '#type' => 'value',
      '#value' => !empty($judging->active_round) ? $judging->active_round : NULL,
    );

    $form['judging']['enabled'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable judging for the active cycle.'),
      '#default_value' => !empty($judging->enabled),
    );

    $form['judging']['rounds'] = array(
      '#type' => 'details',
      '#title' => $this->t('Rounds'),
      '#description' => '<p>' . $this->t('Define criteria for scoring each round of judging.') . '</p>',
      '#open' => TRUE,
      '#states' => array(
        'invisible' => array(
          ':input[name="judging[enabled]"]' => array('checked' => FALSE),
        ),
      ),
      '#attributes' => ['id' => 'fieldset-rounds-wrapper'],
    );

    for ($i = 1; $i <= $num_rounds; $i++) {

      // Disallow any changes to this round config if it is currently under
      // active judging or it is a previous round. Changing round config once
      // judging data exists for that round could easily create bad data.
      // (Generally presume that during judging, site admins will only advance
      // the active round forward, rather than ever setting it back to an
      // earlier round - which would then make rounds with judging data
      // editable here - although this is not enforced in code.)
      $disable_judging_edit = (!empty($judging->active_round) ?
        $i <= $judging->active_round
        : $judging_data_exists);

      $round = (!empty($judging->rounds[$i]) ? $judging->rounds[$i] : array());

      $form['judging']['rounds'][$i] = array(
        '#type' => 'fieldset',
        '#title' => $this->t('Round @n', [
          '@n' => $i,
        ]),
      );

      if ($disable_judging_edit) {
        if (!empty($judging->active_round)) {
          if ($i == $judging->active_round) {
            $message = $this->t("Round configuration may not be updated because this round of judging is currently active.");
          }
          else {
            $message = $this->t("Round configuration may not be updated because this round of judging has already been completed.");
          }
        }
        else {
          // This shouldn't really happen; it's a safety fallback for now.
          $message = $this->t('Round configuration may not be updated because judging data already exists. However, <strong>no round is set active</strong> - the furthest round with judging data should be <a href=":url_judging_setup">set active</a>.', [
            ':url_judging_setup' => Url::fromRoute('entity.competition_entry.judging', [
              'competition' => $competition->id(),
              'callback' => 'setup',
            ])->toString(),
          ]);
        }

        $form['judging']['rounds'][$i]['disabled'] = array(
          '#markup' => '<div class="messages messages--warning">' . $message . '</div>',
        );
      }

      $round_type = (!empty($round['round_type']) ? $round['round_type'] : NULL);

      $form['judging']['rounds'][$i]['round_type'] = array(
        '#type' => 'radios',
        '#title' => $this->t("Round type"),
        '#description' => $this->t("Select how judges will score this round."),
        '#options' => [
          'pass_fail' => $this->t("Pass/Fail"),
          'criteria' => $this->t("Weighted Criteria"),
        ],
        '#default_value' => $round_type,
        '#required' => TRUE,
        '#disabled' => $disable_judging_edit,
      );

      // Define #states arrays to show further form elements per round type.
      // Note: the 'required' state only toggles the 'required' attribute on the
      // inputs - not Drupal #required value and validation thereof.
      $selector_round_type = 'input[name="judging[rounds][' . $i . '][round_type]"]';
      $states_pass_fail = [
        'visible' => [
          $selector_round_type => ['value' => 'pass_fail'],
        ],
        'required' => [
          $selector_round_type => ['value' => 'pass_fail'],
        ],
      ];
      $states_criteria = [
        'visible' => [
          $selector_round_type => ['value' => 'criteria'],
        ],
        'required' => [
          $selector_round_type => ['value' => 'criteria'],
        ],
      ];
      // Show and require for either round type.
      // To create an OR condition group, wrap each condition in its own array.
      $states_both = [
        'visible' => [
          [
            $selector_round_type => ['value' => 'pass_fail'],
          ],
          // -OR-.
          [
            $selector_round_type => ['value' => 'criteria'],
          ],
        ],
        'required' => [
          [
            [
              $selector_round_type => ['value' => 'pass_fail'],
            ],
            // -OR-.
            [
              $selector_round_type => ['value' => 'criteria'],
            ],
          ],
        ],
      ];

      $form['judging']['rounds'][$i]['required_scores'] = array(
        '#type' => 'number',
        '#title' => $this->t('Number of required judging scores'),
        '#description' => $this->t("<p>If using weighted judging criteria, this is the number of users that are required to score an entry before it is eligible for advancement to the next round of judging. A user can only score an entry once.</p>"),
        '#default_value' => (!empty($round['required_scores']) ? $round['required_scores'] : ''),
        '#min' => 1,
        '#disabled' => $disable_judging_edit,
        '#states' => $states_both,
      );

      // Pass/Fail fields:
      // TODO: pass == all pass, or at least one pass? (make configurable here?)
      $form['judging']['rounds'][$i]['pass_fail_description'] = array(
        '#type' => 'item',
        '#markup' => '<p>' . $this->t("<em>In a Pass/Fail round, judges will simply mark each entry as Pass or Fail. No further scoring is done.</em>") . '</p>',
        '#states' => $states_pass_fail,
      );

      // Weighted Criteria fields:
      // These are conditionally required via custom validation.
      // @see ::validateForm()
      //
      // TODO: for contrib, ultimately would be more flexible to include '0'
      // in the scoring range.
      $form['judging']['rounds'][$i]['criterion_options'] = array(
        '#type' => 'number',
        '#title' => $this->t("Score options per criterion"),
        '#description' => $this->t("Set the number of options for scoring on a single criterion.<br/>Example: set this value to '4' to allow judges to choose a score of <strong>1, 2, 3, or 4</strong> for each criterion."),
        '#default_value' => ($round_type == 'criteria' && !empty($round['criterion_options']) ? (float) $round['criterion_options'] : ''),
        // There must be at least two options (1 - 2)
        '#min' => 2,
        // No max is technically required, but a scale of 1 - 100 is probably
        // sufficient...
        '#max' => 100,
        '#step' => 1,
        '#states' => $states_criteria,
        '#disabled' => $disable_judging_edit,
      );

      // Criteria fields.
      $weighted_criteria = '';
      if ($round_type == 'criteria' && !empty($round['weighted_criteria'])) {
        if (is_array($round['weighted_criteria'])) {
          $items = array();
          foreach ($round['weighted_criteria'] as $label => $weight) {
            $items[] = "$weight|$label";
          }
          $weighted_criteria = implode("\n", $items);
        }
        elseif (is_string($round['weighted_criteria'])) {
          $weighted_criteria = $round['weighted_criteria'];
        }
      }
      $form['judging']['rounds'][$i]['weighted_criteria'] = array(
        '#type' => 'textarea',
        '#title' => $this->t('Weighted judging criteria'),
        '#description' => $this->t("<p>Provide the possible criteria this round can contain; enter one value per line, in the format number|label.</p><p>Key should be a numeric value and the sum of keys should equal 100. E.g. a key|label pair of 20|Creativity would mean the Creativity scoring criteria counts for 20% of the contest entry's total score.</p>"),
        '#default_value' => $weighted_criteria,
        '#states' => $states_criteria,
        '#disabled' => $disable_judging_edit,
      );

      $form['judging']['rounds'][$i]['criteria_description'] = array(
        '#type' => 'textarea',
        '#title' => $this->t('Judging criteria description'),
        '#description' => $this->t("<p>Provide a description or instructions for the judging criteria. This description will appear above the judging form.</p>"),
        '#default_value' => (!empty($round['criteria_description']) ? $round['criteria_description'] : ''),
        '#required' => TRUE,
      );

      if ($i != 1 && $i == ($num_rounds)) {
        $form['judging']['rounds'][$i]['remove'] = array(
          '#type' => 'submit',
          '#value' => $this->t('Remove this round'),
          // @see removeRound() in CompetitionJudgingTrait()
          '#submit' => array('::removeRound'),
          '#ajax' => array(
            // @see roundCallback() in CompetitionJudgingTrait()
            'callback' => '::roundCallback',
            'wrapper' => 'fieldset-rounds-wrapper',
          ),
          '#attribute' => [
            'class' => 'button-remove-judging-round',
          ],
          // Set #limit_validation_errors so that this button will not check
          // #required when removing empty round fields.
          '#limit_validation_errors' => [],
          '#disabled' => $disable_judging_edit,
        );
      }

    }

    // Allow up to 5 rounds.
    if ($num_rounds < 5) {
      $form['judging']['rounds']['add_round'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Add round'),
        // @see addRound() in CompetitionJudgingTrait()
        '#submit' => array('::addRound'),
        '#ajax' => array(
          // @see roundCallback() in CompetitionJudgingTrait()
          'callback' => '::roundCallback',
          'wrapper' => 'fieldset-rounds-wrapper',
        ),
        '#weight' => 999,
      );
    }

    $form['judging']['queues'] = array(
      '#type' => 'details',
      '#title' => $this->t('Queues'),
      '#description' => '<p>' . $this->t('Select queues to use for judging.') . '</p>',
      '#open' => TRUE,
      '#states' => array(
        'invisible' => array(
          ':input[name="judging[enabled]"]' => array('checked' => FALSE),
        ),
      ),
    );

    // Disallow enabling/disabling queues if any judging data exists, since
    // queues are available throughout all judging rounds.
    if ($judging_data_exists) {
      $form['judging']['queues']['disabled'] = array(
        '#markup' => '<div class="messages messages--warning">' . $this->t('Queue selections may not be updated while judging data exists for any round.') . '</div>',
      );
    }

    foreach ($this->configSettings['queues'] as $key => $label) {
      $form['judging']['queues'][$key] = array(
        '#type' => 'checkbox',
        '#title' => $competition->getJudgingQueueLabel($key),
        '#default_value' => (isset($judging->queues[$key]) ? $judging->queues[$key] : FALSE),
        '#disabled' => $judging_data_exists,
      );
    }

    $form['#validate'][] = '::validateRounds';

    return $this->protectBundleIdElement($form);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Conditional validation for round fields, based on selected round type.
    $rounds = $form_state->getValue(['judging', 'rounds']);

    if (!empty($rounds['add_round'])) {
      unset($rounds['add_round']);
    }

    if (!empty($rounds)) {
      foreach ($rounds as $round_id => $round) {
        switch ($round['round_type']) {
          case 'pass_fail':

            break;

          case 'criteria':
            foreach (['criterion_options', 'weighted_criteria'] as $key) {
              // '0' is not a valid value for these, so we can use empty()
              if (empty($round[$key])) {
                $elem = &$form['judging']['rounds'][$round_id][$key];
                $form_state->setError($elem, $this->t("@label field is required.", [
                  '@label' => $elem['#title'],
                ]));
              }
            }

            break;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $competition = $this->entity;

    // By this point, $this->entity has already been overwritten with form state
    // values. We've stored the original entity in form state, in ::form().
    // @see EntityForm::submitForm()
    // (Not currently used, but leaving here for documentation.)
    // $competition_original = $form_state->get('entity_original');.
    // Note: this value is structured as an array - both keys and values are the
    // cycle key, e.g.
    // [ '2011' => '2011', '2012' => '2012', ...].
    $archived_cycles = $form_state->getValue([
      'entry_limits',
      'archive_settings',
      'cycles_archived',
    ]);
    if (!empty($archived_cycles)) {
      $competition->set('cycles_archived', serialize($archived_cycles));
    }

    $limits = array(
      'require_user' => $form_state->getValue([
        'entry_limits', 'criteria', 'require_user',
      ]),
      'email_as_username' => $form_state->getValue([
        'entry_limits', 'criteria', 'email_as_username',
      ]),
      'count' => $form_state->getValue([
        'entry_limits', 'criteria', 'inline', 'count',
      ]),
      'interval' => $form_state->getValue([
        'entry_limits',
        'criteria',
        'inline', 'interval',
      ]),
      'fields' => $form_state->getValue([
        'entry_limits',
        'criteria',
        'fields',
      ]),
      'allow_partial_save' => (!empty($form_state->getValue([
        'entry_limits',
        'criteria',
        'require_user',
      ]))
        ? $form_state->getValue([
          'entry_limits',
          'criteria',
          'allow_partial_save',
        ]) : FALSE),
      'field_reentry' => $form_state->getValue([
        'entry_limits',
        'reentry',
        'field_reentry',
      ]),
    );

    // Ensure sure that reentry field is part of the unique fields array.
    if (!empty($limits['field_reentry'])) {
      if (!in_array($limits['field_reentry'], $limits['fields'])) {
        $limits['fields'][] = $limits['field_reentry'];

        drupal_set_message($this->t('The %field re-entry field was automatically added to the list of unique entry fields for the competition.', [
          '%field' => $form['entry_limits']['reentry']['field_reentry']['#options'][$limits['field_reentry']],
        ]));
      }
    }

    $longtext = array(
      'description' => $form_state->getValue(['longtext', 'description']),
      'terms_of_use' => $form_state->getValue(['longtext', 'terms_of_use']),
      'confirmation' => $form_state->getValue(['longtext', 'confirmation']),
      // Not currently supporting HTML emails.
      // This utility method strips tags, then decodes any entities.
      // Do this upon config save, so admin will see the adjusted output in
      // the config form.
      'confirmation_email' => trim(PlainTextOutput::renderFromHtml($form_state->getValue(['longtext', 'confirmation_email']))),
    );

    // Judging
    // Note that:
    // $judging['judges_rounds'] is set in CompetitionJudgesRoundsSetupForm
    // $judging['active_round'] is set in CompetitionJudgingRoundWorkflowForm
    // so those two are stored as server-side-only 'value' elements, to preserve
    // them through AJAX submits and this form submit.
    $judging = array(
      'judges_rounds' => $form_state->getValue(['judging', 'judges_rounds']),
      'active_round'  => $form_state->getValue(['judging', 'active_round']),
      'enabled'       => $form_state->getValue(['judging', 'enabled']),
      'rounds'        => $form_state->getValue(['judging', 'rounds']),
      'queues'        => $form_state->getValue(['judging', 'queues']),
    );

    foreach ($judging['rounds'] as &$round) {

      // This is an 'item' element used for markup only.
      unset($round['pass_fail_description']);

      switch ($round['round_type']) {

        case 'pass_fail':
          /* Pass/Fail round - populate scoring config used behind the scenes:
           * Options: the judging form dropdown will have:
           *   1 => Pass
           *   0 => Fail
           * In score calculations this setting is used as max possible points,
           * so it must be 1 here.
           */
          $round['criterion_options'] = 1;

          // For score calculation purposes, we need 1 criterion at 100% weight.
          // This label is displayed only in score details table.
          $round['weighted_criteria'] = array(
            $this->t("Pass/Fail")->render() => 100,
          );

          break;

        case 'criteria':
          // Convert weighted_criteria from multi-line string to label => weight
          // array.
          if (!empty($round['weighted_criteria'])) {

            $lines = explode("\n", $round['weighted_criteria']);
            if (is_array($lines)) {

              $criteria = array();
              foreach ($lines as $line) {

                $items = explode('|', $line);
                $criteria[trim($items[1])] = trim($items[0]);

              }

              $round['weighted_criteria'] = $criteria;

            }
          }

          break;

      }

    }

    // Save all settings to competition entity.
    $competition
      ->setEntryLimits($limits)
      ->setLongtext($longtext)
      ->setJudging($judging)
      ->save();

    $this
      ->entityManager
      ->clearCachedDefinitions();

    drupal_set_message($this->t('Saved the %label competition.', [
      '%label' => $competition->label(),
    ]));

    $form_state->setRedirectUrl($competition->toUrl('collection'));
  }

}
