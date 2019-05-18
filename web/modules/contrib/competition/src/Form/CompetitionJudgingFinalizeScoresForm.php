<?php

namespace Drupal\competition\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\competition\CompetitionJudgingSetup;

/**
 * Defines the Competition Judging Finalize Scoring form.
 */
class CompetitionJudgingFinalizeScoresForm extends FormBase {

  /**
   * The competition judging service.
   *
   * @var \Drupal\competition\CompetitionJudgingSetup
   */
  protected $judgingSetup;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * User is judging admin.
   *
   * @var bool
   */
  protected $userIsJudgingAdmin;

  /**
   * Constructor.
   *
   * @param \Drupal\competition\CompetitionJudgingSetup $judging_setup
   *   The competition judging setup service.
   * @param \Drupal\Core\Session\AccountProxy $current_user
   *   The current logged-in user.
   */
  public function __construct(CompetitionJudgingSetup $judging_setup, AccountProxy $current_user) {

    $this->judgingSetup = $judging_setup;
    $this->currentUser = $current_user;

    $this->userIsJudgingAdmin = $this->currentUser->hasPermission('administer competition judging');

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('competition.judging_setup'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'competition_judging_finalize_scores';
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

    $active_round = (is_string($judging->active_round) && is_numeric($judging->active_round) ? (int) $judging->active_round : $judging->active_round);
    $form_state->set('active_round', $active_round);

    // Store judge user (to process their assigned entries only),
    // NULL for admin.
    $form_state->set('judge_uid', ($this->userIsJudgingAdmin ? NULL : $this->currentUser->id()));

    // Get entry IDs for this round and/or assigned to this judge.
    $entry_ids_all = NULL;
    $entry_ids_complete = NULL;

    if (!empty($active_round)) {
      // Admins can finalize scores for all entries in the round.
      $filters = [
        'round_id' => $active_round,
      ];

      // Non-admin judges can only finalize (their) scores on their assigned
      // entries.
      if (!$this->userIsJudgingAdmin) {
        $filters['judge_uid'] = $this->currentUser->id();
      }

      $entry_ids_all = $this->judgingSetup->filterJudgingEntries($competition->id(), $filters);

      // Now get only entries with completed scores.
      $filters['score_complete'] = TRUE;
      $entry_ids_complete = $this->judgingSetup->filterJudgingEntries($competition->id(), $filters);
    }

    $form_state->set('entry_ids_all', $entry_ids_all);
    $form_state->set('entry_ids_complete', $entry_ids_complete);

    // Scores may be bulk-finalized only if all are complete.
    $can_finalize = (!empty($entry_ids_all) && count($entry_ids_complete) == count($entry_ids_all));

    $form['#attached']['library'][] = 'competition/competition';

    $description = [];
    if (!empty($entry_ids_all)) {
      if ($this->userIsJudgingAdmin) {
        $description[] = $this->t("This will mark all judges' scores as final for all entries in this round. An entry's scores cannot be changed once they are marked as final.");
      }
      else {
        $description[] = $this->t("This will mark all your scores in this round as final. An entry's scores cannot be changed once they are marked as final.");
      }

      if (!$can_finalize) {
        $description[] = $this->t("<em>(This feature will be available once all entry scores are complete.)</em>");
      }
    }
    else {
      if ($this->userIsJudgingAdmin) {
        $description[] = $this->t("(There are no entries in this round yet.)");
      }
      else {
        $description[] = $this->t("(There are no entries assigned to you in this round yet.)");
      }
    }

    $form['wrap'] = [
      '#type' => 'details',
      '#title' => $this->t("Finalize Scores"),
      '#description' => '<p>' . implode('</p><p>', $description) . '</p>',
      '#open' => FALSE,
      '#attributes' => [
        'class' => ['judging-instructions'],
      ],
    ];

    // Add buttons only if entries are ready for score finalization.
    if ($can_finalize) {

      $action_attr = 'finalize';

      $form['wrap']['pre_finalize_scores'] = [
        '#type' => 'inline_template',
        '#template' => '<button type="button" data-action="{{ action_attr }}" class="button subform-closed">{{ text }}</button>',
        '#context' => [
          'action_attr' => $action_attr,
          'text' => $this->t("Mark all scores final"),
        ],
      ];

      $form['wrap']['finalize_scores'] = [
        '#type' => 'container',
        '#attributes' => [
          'data-action-sub' => $action_attr,
          'class' => [
      // Drupal core class to initially set display: none.
            'hidden',
      // Custom class for styling.
            'action-subform',
          ],
        ],
      ];

      $form['wrap']['finalize_scores']['confirm_note'] = [
        '#markup' => '<p class="confirm-description">' . $this->t("Finalize all entry scores?") . '</p>',
      ];

      $form['wrap']['finalize_scores']['action_confirm'] = [
        '#type' => 'actions',
      ];
      $form['wrap']['finalize_scores']['action_confirm']['submit_finalize_scores'] = [
        '#type' => 'submit',
        '#value' => $this->t("Confirm"),
        '#attributes' => [
          'class' => [
            'button',
            'button--primary',
          ],
        ],
      ];

      $form['wrap']['finalize_scores']['action_confirm']['cancel'] = [
        '#type' => 'inline_template',
        '#template' => '<button type="button" data-action-cancel="{{ action_attr }}" class="button">{{ text }}</button>',
        '#context' => [
          'action_attr' => $action_attr,
          'text' => $this->t("Cancel"),
        ],
      ];

    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $entry_ids_all = $form_state->get('entry_ids_all');
    $entry_ids_complete = $form_state->get('entry_ids_complete');
    $active_round = $form_state->get('active_round');
    $judge_uid = $form_state->get('judge_uid');

    // This should never happen, because the submit button is not included
    // if there are no entries in the round.
    if (empty($active_round) || empty($entry_ids_all)) {
      drupal_set_message($this->t("Unexpected error: no judging round is active or there are no entries in the active round."), 'error');
      return;
    }

    // This should never happen either, because the submit button is not
    // included if not all entry scores are complete.
    if (count($entry_ids_complete) != count($entry_ids_all)) {
      drupal_set_message($this->t("Unexpected error: not all entries have completed scores, so all scores cannot be finalized."), 'error');
      return;
    }

    // If all is well, set the batch.
    $this->judgingSetup->finalizeScores(
      $active_round,
      $entry_ids_complete,
      $judge_uid
    );

  }

}
