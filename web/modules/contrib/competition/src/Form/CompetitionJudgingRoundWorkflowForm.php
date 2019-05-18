<?php

namespace Drupal\competition\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\competition\CompetitionJudgingSetup;

/**
 * Defines the Competition Judging Round Workflow form.
 */
class CompetitionJudgingRoundWorkflowForm extends FormBase {

  /**
   * The competition judging service.
   *
   * @var \Drupal\competition\CompetitionJudgingSetup
   */
  protected $judgingSetup;

  /**
   * Constructor.
   *
   * @param \Drupal\competition\CompetitionJudgingSetup $judging_setup
   *   The competition judging service.
   */
  public function __construct(CompetitionJudgingSetup $judging_setup) {

    $this->judgingSetup = $judging_setup;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('competition.judging_setup')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'competition_judging_round_workflow';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Since we're implementing FormInterface::buildForm(), we cannot add the
    // expected (and necessary) $competition arg to the method signature -
    // even though it is received by FormBuilder::getForm() and passed in here
    // via FormBuilder::retrieveForm().
    // Instead retrieve it from form state.
    /** @var \Drupal\competition\CompetitionInterface $competition */
    $competition = $form_state->getBuildInfo()['args'][0];
    $form_state->set('competition', $competition);

    $judging = $competition->getJudging();

    // (?? When retrieved from competition entity, active round is a string,
    // even though it's saved as an int.)
    $active_round = (is_string($judging->active_round) && is_numeric($judging->active_round) ? (int) $judging->active_round : $judging->active_round);
    $form_state->set('active_round', $active_round);

    $active_round_type = NULL;
    if (!empty($active_round)) {
      $active_round_type = $judging->rounds[$active_round]['round_type'];
    }
    $form_state->set('active_round_type', $active_round_type);

    $previous_round = NULL;
    $previous_round_type = NULL;
    if ($active_round > 1) {
      $previous_round = $active_round - 1;
      $previous_round_type = $judging->rounds[$previous_round]['round_type'];
    }
    $form_state->set('previous_round_type', $previous_round_type);

    // Get counts of entries in active round or to be assigned.
    $round_entry_ids = NULL;
    $avail_entry_ids = NULL;
    $count_round_entries = NULL;
    $count_avail_entries = NULL;

    if (!empty($active_round)) {
      $round_entry_ids = $this->judgingSetup->filterJudgingEntries($competition->id(), [
        'round_id' => $active_round,
      ]);
      $count_round_entries = count($round_entry_ids);

      // ... or available to be assigned, from previous round.
      if ($count_round_entries == 0) {
        if ($active_round > 1) {
          $filters = [
            'round_id' => $previous_round,
          ];
        }
        else {
          // If no previous round (this is first round), do not filter by round.
          // This retrieves all (finalized) entries.
          // [This SHOULD always be correct, as the pool of entries available
          // to move into Round 1. If data is really busted, there could be
          // entries in some round other than 1...].
          $filters = [];
        }
        $avail_entry_ids = $this->judgingSetup->filterJudgingEntries($competition->id(), $filters);
        $count_avail_entries = count($avail_entry_ids);
      }
    }

    $form_state->set('count_round_entries', $count_round_entries);
    $form_state->set('count_avail_entries', $count_avail_entries);

    // Store IDs of entries to assign into a round, for Assign submit handler.
    if ($avail_entry_ids !== NULL) {
      $form_state->set('avail_entry_ids', $avail_entry_ids);
    }

    $form['#attached']['library'][] = 'competition/competition';

    $form['wrap'] = [
      '#type' => 'details',
      '#title' => $this->t("Manage Judging Round"),
      '#open' => TRUE,
    ];

    // Round status and count messages.
    $status_round = '';
    $status_count = '';
    if (empty($active_round)) {
      $status_round = $this->t("<i>No judging round is active</i>");
    }
    else {
      $status_round = $this->t("<i>Round @round_id is active</i>", [
        '@round_id' => $active_round,
      ]);

      if ($count_round_entries > 0) {
        $status_count = $this->formatPlural($count_round_entries,
          "There is 1 entry assigned to judges.",
          "There are @count entries assigned to judges."
        );
      }
      else {
        if ($active_round > 1) {
          $status_count = $this->formatPlural($count_avail_entries,
            "There is 1 entry from the previous round available for assignment to judges.",
            "There are @count entries from the previous round available for assignment to judges."
          );
        }
        else {
          $status_count = $this->formatPlural($count_avail_entries,
            "There is 1 finalized entry available for assignment to judges.",
            "There are @count finalized entries available for assignment to judges."
          );
        }
      }
    }

    if (!empty($status_round)) {
      $form['wrap']['status_round'] = [
        '#markup' => '<p>' . $status_round . '</p>',
      ];
    }
    if (!empty($status_count)) {
      $form['wrap']['status_count'] = [
        '#markup' => '<p>' . $status_count . '</p>',
      ];
    }

    // Active round.
    $round_options = [];
    foreach (array_keys($judging->rounds) as $round_id) {
      $round_options[$round_id] = $this->t("Round @round_id", [
        '@round_id' => $round_id,
      ]);
    }
    $form['wrap']['active_round'] = [
      '#type' => 'select',
      '#title' => $this->t("Active round:"),
      '#empty_value' => '',
      '#empty_option' => $this->t("- None -"),
      '#options' => $round_options,
      '#default_value' => (!empty($active_round) ? $active_round : ''),
    ];

    $form['wrap']['submit_active_round'] = [
      '#type' => 'submit',
      '#value' => $this->t("Update"),
      // Set distinct name to override default 'op', so that correct
      // submit handlers will be called.
      '#name' => 'submit_active_round',
      // Note: if #submit is set, ::submitForm() does not run.
      '#submit' => [
        '::submitSetActiveRound',
      ],
    ];

    $form['wrap']['active_round']['#prefix'] = '<div class="active-round">';
    $form['wrap']['submit_active_round']['#suffix'] = '</div><hr>';

    // Actions to perform on the active round.
    if (!empty($active_round)) {

      // Define common CSS classes
      //
      // "Action subform" fieldsets containing fields to refine and confirm the
      // action.
      $classes_subform = [
      // Drupal core class to initially set display: none.
        'hidden',
      // Custom class for styling.
        'action-subform',
      ];

      $classes_action_btn = [
        'button',
        'round-action',
        'subform-closed',
      ];

      $classes_confirm_btn = [
        'button',
        'button--primary',
        'round-action-confirm',
      ];

      $classes_cancel_btn = [
        'button',
        'round-action-cancel',
      ];

      // Define an inline twig template for action buttons.
      $twig_btn = '<button type="button" data-action="{{ data_action }}" class="{{ classes }}">{{ text }}</button>';

      if ($count_round_entries == 0) {
        // If no entries in this round:
        $form['wrap']['pre_submit_assign'] = [
          '#type' => 'inline_template',
          '#template' => $twig_btn,
          '#context' => [
            'data_action' => 'assign-judges',
            'classes' => implode($classes_action_btn, " "),
            'text' => $this->t('Assign entries'),
          ],
        ];

        $form['wrap']['sub_assign'] = [
          '#type' => 'container',
          '#attributes' => [
            'data-action-sub' => 'assign-judges',
            'class' => $classes_subform,
          ],
          '#weight' => 101,
        ];

        // ---------------------------------------------------------------------
        // --- Assign judges (move entries into round)
        // if ($judging->rounds[$active_round]['round_type'] != 'voting') {.
        if ($active_round == 1) {
          $form['wrap']['sub_assign']['no_previous'] = [
            '#markup' => '<p>' . $this->t("This is the first round, so all finalized entries will be assigned for judging.") . '</p>',
          ];
        }
        else {
          $form['wrap']['sub_assign']['intro'] = [
            '#markup' => '<p class="intro">' . $this->t("By assigning judges, entries from the previous round are moved into this round.") . '</p>
              <p class=\"intro\">' . $this->t("You may filter the entries from the previous round via one of the following options. If both are left blank, all entries in the previous round will move into this round.") . '</p>',
          ];

          if ($previous_round_type == 'pass_fail') {
            // Limit to entries that passed in previous round
            // TODO: pass == all pass, or at least one pass?
            $form['wrap']['sub_assign']['passed_only'] = [
              '#type' => 'checkbox',
              '#title' => '<strong>' . $this->t("Limit to entries that passed in the previous round") . '</strong>',
              '#description' => $this->t('If multiple judges were assigned per entry, all judges must have marked it "Pass" in order for the entry to pass overall.'),
              '#default_value' => TRUE,
            ];
          }
          elseif ($previous_round_type == 'criteria') {
            // Limit by a minimum average score in previous round.
            $form['wrap']['sub_assign']['min_score'] = [
              '#type' => 'number',
              '#title' => $this->t("Minimum average score in previous round"),
              '#description' => $this->t("Enter the minimum average score (0 - 100) that an entry must have received in the previous round of judging to be eligible to move into this round."),
              '#min' => 0,
              '#max' => 100,
            ];
          }

          $form['wrap']['sub_assign']['or'] = [
            '#markup' => '<p class="or"><strong>' . $this->t('- OR -') . '</strong></p>',
          ];

          // Limit to specific entry IDs.
          $form['wrap']['sub_assign']['specific_ids'] = [
            '#type' => 'textarea',
            '#title' => $this->t("Specific entry IDs"),
            '#description' => $this->t("Move only these given entries into this round. Enter 1 numeric ID per line.") . '<br/>' . ($previous_round_type == 'pass_fail' ?
              $this->t("(Using this option will disregard whether entries passed or failed in the previous round.)")
              : $this->t("(Using this option will disregard any minimum score entered.)")),
          ];
        }
        $form['wrap']['sub_assign']['action_confirm'] = [
          '#type' => 'actions',
        ];
        $form['wrap']['sub_assign']['action_confirm']['submit_assign'] = [
          '#type' => 'submit',
          '#value' => $this->t("Confirm"),
          // Set distinct name to override default 'op', so that correct
          // submit handlers will be called.
          '#name' => 'submit_assign_judges',
          '#submit' => [
            '::submitAssignEntries',
          ],
          '#attributes' => [
            'class' => $classes_confirm_btn,
          ],
        ];
        $form['wrap']['sub_assign']['action_confirm']['cancel'] = [
          '#markup' => '<button type="button" class="' . implode(' ', $classes_cancel_btn) . '" data-action-cancel="assign-judges">' . $this->t('Cancel') . '</button>',
          '#allowed_tags' => ['button'],
        ];
      }
      else {
        // If entries are in this round:
        // ---------------------------------------------------------------------
        // --- Delete judge assignments and scores (remove entries from round)
        $action_attr = 'unassign-round';

        $form['wrap']['pre_submit_unassign_round'] = [
          '#type' => 'inline_template',
          '#template' => $twig_btn,
          '#context' => [
            'data_action' => $action_attr,
            'classes' => implode($classes_action_btn, " "),
            'text' => $this->t('Delete scores'),
          ],
        ];

        $form['wrap']['sub_unassign_round'] = [
          '#type' => 'container',
          '#attributes' => [
            'data-action-sub' => $action_attr,
            'class' => $classes_subform,
          ],
          '#weight' => 101,
        ];

        $form['wrap']['sub_unassign_round']['confirm_note'] = [
          '#markup' => '<p class="confirm-description">' . $this->t("This will remove all entries, judges, their scores and notes from this round.") . '</p>',
        ];

        $form['wrap']['sub_unassign_round']['action_confirm'] = [
          '#type' => 'actions',
        ];
        $form['wrap']['sub_unassign_round']['action_confirm']['submit_unassign_round'] = [
          '#type' => 'submit',
          '#value' => $this->t("Confirm"),
          // Set distinct name to override default 'op', so that correct
          // submit handlers will be called.
          '#name' => 'submit_unassign_round',
          '#submit' => [
            '::submitUnassignRound',
          ],
          '#attributes' => [
            'class' => $classes_confirm_btn,
          ],
        ];
        $form['wrap']['sub_unassign_round']['action_confirm']['cancel'] = [
          '#markup' => '<button type="button" class="' . implode(' ', $classes_cancel_btn) . '" data-action-cancel="' . $action_attr . '">' . $this->t('Cancel') . '</button>',
          '#allowed_tags' => ['button'],
        ];

        // ---------------------------------------------------------------------
        // --- Generate test scores.
        $action_attr = 'generate-scores';

        $form['wrap']['pre_submit_generate_scores'] = [
          '#type' => 'inline_template',
          '#template' => $twig_btn,
          '#context' => [
            'data_action' => $action_attr,
            'classes' => implode($classes_action_btn, " "),
            'text' => $this->t('Generate test scores'),
          ],
        ];

        $form['wrap']['sub_generate_scores'] = [
          '#type' => 'container',
          '#attributes' => [
            'data-action-sub' => $action_attr,
            'class' => $classes_subform,
          ],
          '#weight' => 101,
        ];

        $form['wrap']['sub_generate_scores']['confirm_note'] = [
          '#markup' => '<p class="confirm-description">' . $this->t("This will generate random, finalized judging scores for all entries in this round.") . '</p><p>' . $this->t("All existing scores in this round will be overwritten.") . '</p>',
        ];

        $form['wrap']['sub_generate_scores']['action_confirm'] = [
          '#type' => 'actions',
        ];
        $form['wrap']['sub_generate_scores']['action_confirm']['submit_generate_scores'] = [
          '#type' => 'submit',
          '#value' => $this->t("Confirm"),
          // Set distinct name to override default 'op', so that correct
          // submit handlers will be called.
          '#name' => 'submit_generate_scores',
          '#submit' => [
            '::submitGenerateScores',
          ],
          '#attributes' => [
            'class' => $classes_confirm_btn,
          ],
        ];
        $form['wrap']['sub_generate_scores']['action_confirm']['cancel'] = [
          '#markup' => '<button type="button" class="' . implode(' ', $classes_cancel_btn) . '" data-action-cancel="' . $action_attr . '">' . $this->t('Cancel') . '</button>',
          '#allowed_tags' => ['button'],
        ];

      }

    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    $specific_ids_str = $form_state->getValue('specific_ids');
    $specific_ids = NULL;
    if (!empty($specific_ids_str)) {
      $specific_ids_str = str_replace(["\r\n", "\r"], "\n", $specific_ids_str);
      $specific_ids = array_filter(explode("\n", $specific_ids_str));

      if (!empty($specific_ids)) {
        // Run these through the filter method, which always includes conditions
        // for the given competition, active cycle, and status 'finalized'.
        $valid_ids = $this->judgingSetup->filterJudgingEntries($form_state->get('competition')->id(), [
          'ceid' => $specific_ids,
        ]);

        $invalid = array_diff($specific_ids, $valid_ids);
        if (!empty($invalid)) {
          $form_state->setErrorByName('specific_ids', $this->t("The following are not valid entry IDs in the current competition cycle: %ids", [
            '%ids' => implode(", ", $invalid),
          ]));
        }

        $specific_ids = $valid_ids;
      }
    }

    // Store for easy access in submit handler.
    $form_state->set('specific_ids_validated', $specific_ids);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // dsm('submitForm');
    // dpm($form_state->getTriggeringElement());
    // $button = $form_state->getTriggeringElement();
  }

  /**
   * Submit handler for "Set active round" button.
   *
   * TODO: prevent setting round back to "none" once a round has been set?
   *   Or - limit to setting only to adjacent rounds (forward and back), or
   *   only next round (forward)?
   */
  public function submitSetActiveRound(array &$form, FormStateInterface $form_state) {
    $round_id = $form_state->getValue('active_round');

    if (empty($round_id)) {
      $round_id = NULL;
    }
    else {
      $round_id = (int) $round_id;
    }

    $competition = $form_state->get('competition');
    $judging = (array) $competition->getJudging();
    // (?? Somehow, when this value is retrieved later, it's a string, not int,
    // even though it's definitely an int here.)
    $judging['active_round'] = $round_id;
    $competition
      ->setJudging($judging)
      ->save();

    if (!empty($round_id)) {
      drupal_set_message($this->t("Round @round_id is now active.", [
        '@round_id' => $round_id,
      ]));
    }
    else {
      drupal_set_message($this->t("No judging round is active."), 'warning');
    }
  }

  /**
   * Submit handler for "Assign judges" confirm button.
   */
  public function submitAssignEntries(array &$form, FormStateInterface $form_state) {

    $competition_id = $form_state->get('competition')->id();

    $active_round = (int) $form_state->get('active_round');

    $entry_ids = $form_state->get('avail_entry_ids');

    // If user entered specific entry IDs, this takes precedence.
    // @see ::validateForm()
    $specific_ids = $form_state->get('specific_ids_validated');
    if (!empty($specific_ids)) {

      // If any specified IDs were NOT in previous round - warn user, but for
      // flexibility, don't prevent moving those entries into the round.
      $not_in_previous = array_diff($specific_ids, $entry_ids);
      if (!empty($not_in_previous)) {
        drupal_set_message($this->t("Warning: the following specified entry IDs have been moved into this round, but were NOT in the previous round: %ids<br/>
          If this was a mistake, please remove all entries from the round and then re-add the corrected list of entry IDs.", [
            '%ids' => implode(", ", $not_in_previous),
          ]), 'warning');
      }

      $entry_ids = $specific_ids;

      drupal_set_message($this->t("Entries for this round have been limited to the listed IDs."));
    }

    // If user did not provide specific IDs, filter by entries that passed or
    // by a min average score if provided.
    if (empty($specific_ids) && !empty($entry_ids)) {

      $min_score = NULL;

      // Previous round was pass/fail - check the passed-only checkbox.
      $passed_only = $form_state->getValue('passed_only');
      if (!empty($passed_only)) {
        // Currently, pass == all judges marked as pass. Since a pass ==
        // score of 100, the min average for all-pass is 100.
        // TODO: pass == all pass, or at least one pass?
        $min_score = 100;
      }

      // Previous round was criteria scores - check for a min score.
      $min_score_submitted = $form_state->getValue('min_score');
      if (isset($min_score_submitted)) {
        $min_score = $min_score_submitted;
      }

      // isset() ensures not NULL, but allows 0.
      if (isset($min_score)) {
        // We can safely subtract 1 from active round because the min-score
        // field is only presented if there is a previous round.
        $entry_ids = $this->judgingSetup->filterJudgingEntries($competition_id, [
          'ceid' => $entry_ids,
          'round_id' => ($active_round - 1),
          'min_score' => (float) $min_score,
        ]);

        if (!empty($passed_only)) {
          drupal_set_message($this->t("Entries for this round have been limited to those which passed in the previous round."));
        }
        else {
          drupal_set_message($this->t("Entries for this round have been limited to those with a minimum average score of <strong>@min_score</strong> in the previous round.", [
            '@min_score' => $min_score,
          ]));
        }
      }

    }

    if ($entry_ids === NULL) {
      drupal_set_message($this->t("Judge assignment has already been run for Round @round_id.", [
        '@round_id' => $active_round,
      ]), 'warning');
    }
    else {
      $this->judgingSetup->assignEntries(
        $competition_id,
        $active_round,
        $entry_ids
      );
    }

  }

  /**
   * Submit handler for "Delete judge assignments and scores" confirm button.
   */
  public function submitUnassignRound(array &$form, FormStateInterface $form_state) {

    $this->judgingSetup->unassignAllEntriesRound(
      $form_state->get('competition')->id(),
      $form_state->get('active_round')
    );

  }

  /**
   * Submit handler for "Generate test scores" confirmation button.
   */
  public function submitGenerateScores(array &$form, FormStateInterface $form_state) {

    $this->judgingSetup->generateTestScores(
      $form_state->get('competition')->id(),
      $form_state->get('active_round')
    );

    // If we got here, the method early-returned FALSE and batch wasn't set.
    // Method already handles error messaging.
  }

}
