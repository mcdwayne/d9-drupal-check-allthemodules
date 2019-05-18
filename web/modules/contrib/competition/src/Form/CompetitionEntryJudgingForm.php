<?php

namespace Drupal\competition\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Url;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\PrependCommand;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\competition\CompetitionManager;
use Drupal\competition\CompetitionJudgingSetup;

/**
 * Competition entry judging form.
 *
 * @ingroup competition
 */
class CompetitionEntryJudgingForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The competition manager.
   *
   * @var \Drupal\competition\CompetitionManager
   */
  protected $competitionManager;

  /**
   * The judging setup library.
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
   * The current route parameters.
   *
   * @var array
   */
  protected $routeParameters;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Whether current user has permission 'administer competition judging'.
   *
   * @var bool
   */
  protected $isAdmin;

  /**
   * Constructs a CompetitionEntryJudgingForm object.
   *
   * @param \Drupal\competition\CompetitionManager $competition_manager
   *   The competition manager.
   * @param \Drupal\competition\CompetitionJudgingSetup $judging_setup
   *   The judging setup library.
   * @param \Drupal\Core\Session\AccountProxy $current_user
   *   The current user.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityManagerInterface $entity_manager, CompetitionManager $competition_manager, CompetitionJudgingSetup $judging_setup, AccountProxy $current_user, RendererInterface $renderer) {

    $this->entityTypeManager = $entity_type_manager;
    $this->entityManager = $entity_manager;
    $this->competitionManager = $competition_manager;
    $this->judgingSetup = $judging_setup;
    $this->currentUser = $current_user;
    $this->renderer = $renderer;

    $this->isAdmin = $this->currentUser->hasPermission('administer competition judging');

    /*
     * Tory:
     * I feel like I've read that the REFERER var isn't always reliably
     * populated...? Maybe put competition, round id, etc. into path for ajax
     * request? (I haven't done D8 modal stuff so I dunno)
     */
    // Gather information from referring request so we can apply filters.
    $referer = \Drupal::request()->server->get('HTTP_REFERER');
    $referer_request = Request::create($referer);
    $url = \Drupal::service('path.validator')->getUrlIfValid($referer_request->getRequestUri());

    $this->routeParameters = array();
    if ($url) {
      $this->routeParameters = $url->getRouteParameters();
    }

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity.manager'),
      $container->get('competition.manager'),
      $container->get('competition.judging_setup'),
      $container->get('current_user'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'competition_entry_judging_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $competition_entry = NULL) {

    // Check for entry stored in form state, where it should be
    // stored if the entry has been saved and form is rebuilding.
    if ($form_state->has('competition_entry')) {
      $competition_entry = $form_state->get('competition_entry');
    }

    $competition = $competition_entry->getCompetition();
    $judging = $competition->getJudging();
    $queues = $this->config('competition.settings')->get('queues');

    $form['#title'] = $this->t('@cycle @label Judging | @queue', [
      '@cycle' => $competition->getCycleLabel(),
      '@label' => $competition->label(),
      '@queue' => (stristr($this->routeParameters['callback'], 'round') || ($this->routeParameters['callback'] == 'assignments') ? 'Round ' . $judging->active_round : $queues[$this->routeParameters['callback']]),
    ]);

    /* @var \Drupal\competition\CompetitionEntryInterface */
    $form['#entry'] = $competition_entry;

    /* @var \Drupal\competition\CompetitionInterface */
    $form['#competition'] = $competition;

    $form['#judging'] = $judging;

    $form['#queues'] = $queues;

    $form['container'] = array(
      '#type' => 'container',
      '#attributes' => ['id' => 'competition-entry-modal-wrapper'],
    );

    $form['container']['vtabs'] = array(
      '#type' => 'vertical_tabs',
    );

    if (!in_array($this->routeParameters['callback'], ['assignments', 'round-' . $judging->active_round])) {
      $form['container']['vtabs']['#default_tab'] = 'edit-notes';
    }

    $form['container']['nav'] = array(
      '#type' => 'container',
      '#weight' => -1,
      '#attributes' => ['class' => 'competition-judging-nav'],
    );

    $form['container']['nav']['title'] = array(
      '#markup' => $this->t('Entry @ceid', [
        '@ceid' => $form['#entry']->id(),
      ]),
      '#weight' => -1,
    );

    $form['container']['nav']['items'] = array(
      '#type' => 'container',
      '#attributes' => ['class' => 'competition-judging-nav-items'],
    );

    // Grab the list of entry IDs from the requesting entity list
    // so we can populate previous and next buttons.
    switch ($this->routeParameters['callback']) {
      case 'assignments':
        // Current user assignments.
        $filters = [
          'round_id' => $judging->active_round,
          'judge_uid' => $this->currentUser->id(),
        ];
        break;

      case 'round-' . $judging->active_round:
        // All Round N entries.
        $filters = [
          'round_id' => $judging->active_round,
        ];
        break;

      default:
        // Queues.
        $filters = [
          'queue' => $this->routeParameters['callback'],
        ];
        break;
    }

    // Get list of entries from judging screen so
    // we can select the correct Prev and Next items.
    $entry_ids = $this->judgingSetup->filterJudgingEntries($form['#competition']->id(), $filters);
    $form['#filtered_entry_ids'] = $entry_ids;

    // Previous / Next button navigation.
    $prev_ceid = NULL;
    $next_ceid = NULL;

    foreach (array_values($entry_ids) as $index => $key) {
      if ($key == $form['#entry']->id()) {
        if (isset($entry_ids[$index - 1])) {
          $prev_ceid = $entry_ids[$index - 1];
        }
        if (isset($entry_ids[$index + 1])) {
          $next_ceid = $entry_ids[$index + 1];
        }
      }
    }

    if ($prev_ceid) {

      $form['container']['nav']['items']['previous'] = array(
        '#type' => 'link',
        '#title' => $this->t('&lsaquo; Previous'),
        '#url' => Url::fromRoute('entity.competition_entry.judging.entry',
          [
            'competition_entry' => $prev_ceid,
          ],
          [
            'attributes' => [
              'class' => ['hnav', 'pager__item', 'prev', 'use-ajax'],
              'data-dialog-type' => 'modal',
              'data-dialog-options' => Json::encode([
                'width' => '75%',
              ]),
            ],
          ]
        ),
      );

    }

    if ($next_ceid) {

      $form['container']['nav']['items']['next'] = array(
        '#type' => 'link',
        '#title' => $this->t('Next &rsaquo;'),
        '#url' => Url::fromRoute('entity.competition_entry.judging.entry',
          [
            'competition_entry' => $next_ceid,
          ],
          [
            'attributes' => [
              'class' => ['hnav', 'pager__item', 'next', 'use-ajax'],
              'data-dialog-type' => 'modal',
              'data-dialog-options' => Json::encode([
                'width' => '75%',
              ]),
            ],
          ]
        ),
      );

    }

    // Entry view + scoring tab.
    $round = $form['#judging']->rounds[$form['#judging']->active_round];

    // TODO: improve round type / "does this round use scoring?" logic.
    $scoring_round = in_array($round['round_type'], ['pass_fail', 'criteria']);

    $form['container']['scoring'] = array(
      '#type' => 'details',
      '#title' => $this->t('Round @round', [
        '@round' => $form['#judging']->active_round,
      ]),
      '#group' => 'vtabs',
      '#weight' => -1,
    );

    // Judging view mode.
    $form['container']['scoring']['view'] = $this->entityManager
      ->getViewBuilder('competition_entry')
      ->view($form['#entry'], 'judging');

    // Show scoring form to user only if they are assigned to judge this entry.
    if ($scoring_round) {
      if ($form['#entry']->isJudgeAssigned($this->currentUser->id(), $form['#judging']->active_round)) {

        // Create inner container around scoring form elements
        // for AJAX-replacing content after submit.
        // @see ajaxScoringCallback()
        $form['container']['scoring']['score_subform'] = array(
          '#type' => 'container',
          '#attributes' => [
            'id' => 'competition-entry-modal-score-subform',
          ],
        );

        $score = $form['#entry']->getJudgeScore($form['#judging']->active_round, $this->currentUser->id());

        if ($score->finalized) {

          $form['container']['scoring']['score_subform']['finalized_warning'] = array(
            '#markup' => '<div class="messages messages--warning">'
            . $this->t('The scoring for this entry has been marked final and cannot be changed.')
            . '</div>',
          );

        }

        // Criteria scoring.
        // If score has been finalized, show the form elements but disable them.
        $empty_value = '';
        $empty_label = $this->t("- None -");

        if ($round['round_type'] == 'pass_fail') {

          $form['container']['scoring']['score_subform']['c0'] = array(
            '#type' => 'select',
            '#title' => $this->t("Pass/Fail"),
            '#options' => [
              '1' => $this->t("Pass"),
              '0' => $this->t("Fail"),
            ],
            '#empty_value' => $empty_value,
            '#empty_option' => $empty_label,
            '#disabled' => $score->finalized,
            '#default_value' => isset($score->display['c0']) ? $score->display['c0'] : $empty_value,
          );

        }
        elseif ($round['round_type'] == 'criteria') {

          $i = 0;
          foreach ($round['weighted_criteria'] as $label => $weight) {

            // TODO: for contrib, ultimately would be more flexible to include
            // '0' in the scoring range.
            $options = array();
            for ($j = $round['criterion_options']; $j > 0; $j--) {
              $options[$j] = $j;
            }

            $form['container']['scoring']['score_subform']["c$i"] = array(
              '#title' => $this->t('@label (@weight%)', [
                '@label' => $label,
                '@weight' => $weight,
              ]),
              '#type' => 'select',
              '#options' => $options,
              '#empty_value' => $empty_value,
              '#empty_option' => $empty_label,
              '#disabled' => $score->finalized,
              '#default_value' => isset($score->display["c$i"]) ? $score->display["c$i"] : $empty_value,
            );
            $i++;

          }

        }

        if (!$score->finalized) {

          $form['container']['scoring']['score_subform']['finalized'] = array(
            '#title' => 'These scores are final',
            '#type' => 'checkbox',
            '#description' => $this->t('Finalized scores cannot be changed. Otherwise, you may return and edit them any time.'),
            '#disabled' => $score->finalized,
            '#default_value' => $score->finalized,
          );

          $form['container']['scoring']['score_subform']['note'] = array(
            '#type' => 'textarea',
            '#title' => $this->t('Note'),
          );

          $form['container']['scoring']['score_subform']['submit'] = array(
            '#type' => 'submit',
            '#value' => $this->t('Save scores'),
            '#name' => 'submit_score',
            '#ajax' => [
              // Callback returns AJAX commands, so 'wrapper' is not needed.
              'callback' => [$this, 'ajaxScoringCallback'],
            ],
            '#validate' => [
              [$this, 'ajaxScoringValidate'],
            ],
            '#submit' => [
              [$this, 'ajaxScoringSubmit'],
            ],
            '#attributes' => [
              'class' => ['button--primary'],
            ],
          );

        }

      }
      elseif ($this->isAdmin) {

        // If current user is not assigned to judge entry but has admin access,
        // show score details table instead.
        $form['container']['scoring']['score_table'] = $form['#entry']->renderScoreDetailsTable($form['#judging']->active_round);

      }
    }

    // Queues.
    // Filter to queues enabled for this competition - queues array is:
    // queue name => enabled.
    $queues_active = array_keys(array_filter($form['#judging']->queues));
    if (!empty($queues_active)) {

      $score = NULL;
      if ($scoring_round && $form['#entry']->isJudgeAssigned($this->currentUser->id(), $form['#judging']->active_round)) {
        $score = $form['#entry']->getJudgeScore($form['#judging']->active_round, $this->currentUser->id());
      }

      foreach ($queues_active as $key) {
        $label = $form['#queues'][$key];
        $description = $this->t('Optionally move this entry to the %label list to exclude it from judging.', [
          '%label' => $label,
        ]);

        if ($form['#entry']->existsInQueue($key)) {
          $description = $this->t('Optionally remove this entry from the %label list to make it eligible for judging again.', [
            '%label' => $label,
          ]);
        }

        // Only expose queue form elements if user is admin, or if this entry
        // is assigned to current user AND their score is not finalized.
        // @see DEG-54
        if ($this->isAdmin || (!empty($score) && !$score->finalized)) {
          $form['container']['queue'][$key] = array(
            '#type' => 'details',
            '#title' => $label,
            '#description' => '<p>' . $description . '</p>',
            '#group' => 'vtabs',
          );

          $form['container']['queue'][$key]["{$key}_note"] = array(
            '#type' => 'textarea',
            '#title' => $this->t('Note'),
          );

          if (!$form['#entry']->existsInQueue($key)) {
            // Add to queue.
            $form['container']['queue'][$key]['add_to_queue'] = array(
              '#type' => 'submit',
              '#value' => $this->t('Move entry to @label list', ['@label' => $label]),
              '#queue' => $key,
              '#name' => 'add_queue',
              '#ajax' => [
                'callback' => [$this, 'ajaxQueueCallback'],
              ],
              '#attributes' => [
                'class' => ['button--primary'],
              ],
            );
          }
          else {
            // Remove from queue.
            $form['container']['queue'][$key]['remove_from_queue'] = array(
              '#type' => 'submit',
              '#value' => $this->t('Remove entry from @label list', ['@label' => $label]),
              '#queue' => $filters['queue'],
              '#name' => 'remove_queue',
              '#ajax' => [
                'callback' => [$this, 'ajaxQueueCallback'],
              ],
              '#attributes' => [
                'class' => ['button--primary'],
              ],
            );
          }
        }

      }

    }

    // Notes handling.
    $form['container']['notes'] = array(
      '#title' => $this->t('Notes'),
      '#type' => 'details',
      '#open' => FALSE,
      '#attributes' => ['id' => 'edit-notes'],
      '#group' => 'vtabs',
    );

    // Create inner container for AJAX-replacing tab content.
    $form['container']['notes']['content'] = array(
      '#type' => 'container',
      '#attributes' => [
        'id' => 'competition-entry-modal-notes-content',
      ],
    );

    $form['container']['notes']['content']['table'] = $form['#entry']->renderJudgingLog();

    // Manually edit judge assignments (admins only).
    if ($scoring_round && $this->isAdmin) {

      $num_judges = (int) $round['required_scores'];

      // Get currently assigned judges.
      $assigned = $form['#entry']->getAssignedJudges($form['#judging']->active_round, TRUE);
      $form_state->set('current_judges_assigned', array_keys($assigned));

      $assigned_names = [];
      foreach ($assigned as $uid => $user) {
        $assigned_names[] = $user->getAccountName();
      }
      sort($assigned_names);

      // All judges config'd for this round are available for assignment.
      $judges_round = $this->judgingSetup->getJudgesForRound($form['#competition']->id(), $form['#judging']->active_round, TRUE);

      $judge_options = [];
      foreach ($judges_round as $uid => $user) {
        $judge_options[$uid] = $user->getAccountName();
      }
      asort($judge_options);

      $form['container']['assignments'] = array(
        '#type' => 'details',
        '#group' => 'vtabs',
        '#title' => $this->t("Judges"),
        '#attributes' => [
          'id' => 'competition-entry-modal-assign-wrapper',
        ],
      );

      // Create inner container for AJAX-replacing tab content.
      $form['container']['assignments']['content'] = array(
        '#type' => 'container',
        '#attributes' => [
          'id' => 'competition-entry-modal-assign-content',
        ],
      );

      $form['container']['assignments']['content']['description'] = array(
        '#markup' => $this->t('<p>Select @num to whom to assign this entry in Round @round_id. (Only judges configured for Round @round_id are available here.)</p>
          <p>Currently assigned judges are pre-selected. If you remove an existing judge assignment and that judge has already submitted a score, it will be deleted so that a newly assigned judge\'s score can take its place.</p>
          <p>Currently assigned: %judges</p>', [
            '@num' => $num_judges . ' ' . $this->formatPlural($num_judges, 'judge', 'judges'),
            '@round_id' => $form['#judging']->active_round,
            '%judges' => implode(', ', $assigned_names),
          ]),
      );

      $form['container']['assignments']['content']['judges'] = array(
        '#type' => 'checkboxes',
        '#options' => $judge_options,
        '#default_value' => array_keys($assigned),
      );

      $form['container']['assignments']['content']['submit'] = array(
        '#type' => 'submit',
        '#value' => $this->t("Assign"),
        '#limit_validation_errors' => [
          ['judges'],
        ],
        '#validate' => [
          [$this, 'ajaxJudgeAssignValidate'],
        ],
        '#submit' => [
          [$this, 'ajaxJudgeAssignSubmit'],
        ],
        '#ajax' => [
          // Callback returns AJAX commands, so 'wrapper' is not needed.
          'callback' => [$this, 'ajaxJudgeAssignCallback'],
        ],
        '#attributes' => [
          'class' => ['button--primary'],
        ],
      );

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

  }

  /**
   * Implements generic callback for Ajax event; just returns whole form.
   *
   * @param array $form
   *   From render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current state of form.
   */
  public function ajaxCallback(array &$form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * Implements callback for Ajax event on add / remove queue button clicks.
   *
   * @param array $form
   *   From render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current state of form.
   */
  public function ajaxQueueCallback(array &$form, FormStateInterface $form_state) {

    $element = $form_state->getTriggeringElement();

    if ($element['#name'] == 'add_queue') {

      // Add entry to queue.
      $form['#entry']->queueAdd($element['#queue']);
      drupal_set_message($this->t('Entry @ceid was moved to the %queue list.', [
        '@ceid' => $form['#entry']->id(),
        '%queue' => $form['#queues'][$element['#queue']],
      ]));

    }
    elseif ($element['#name'] == 'remove_queue') {

      // Remove entry from queue.
      $form['#entry']->queueRemove($element['#queue']);
      drupal_set_message($this->t('Entry @ceid was removed from the %queue list.', [
        '@ceid' => $form['#entry']->id(),
        '%queue' => $form['#queues'][$element['#queue']],
      ]));

    }

    // Log judge's custom note.
    if ($note = $form_state->getValue($element['#queue'] . '_note')) {

      $form['#entry']->addJudgingLog($this->currentUser->id(), $form['#judging']->active_round, $note);

    }

    // Redirect to current callback and force UI updates.
    $response = new AjaxResponse();
    $response->addCommand(new RedirectCommand($this->routeParameters['callback']));

    return $response;
  }

  /**
   * Implements callback for Ajax event on judge assignment submit button.
   *
   * @param array $form
   *   Form render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current state of form.
   */
  public function ajaxJudgeAssignCallback(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    // Replace Assignments tab content.
    $response->addCommand(new ReplaceCommand('#competition-entry-modal-assign-content', $form['container']['assignments']['content']));

    // Inject status messages.
    $response->addCommand(new PrependCommand('#competition-entry-modal-assign-content', [
      '#type' => 'status_messages',
    ]));

    // Replace Notes tab content (to update with log messages from this action).
    $response->addCommand(new ReplaceCommand('#competition-entry-modal-notes-content', $form['container']['notes']['content']));

    return $response;
  }

  /**
   * Custom validation handler for judge assignment submit button.
   *
   * @param array $form
   *   Form render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current state of form.
   */
  public function ajaxJudgeAssignValidate(array &$form, FormStateInterface $form_state) {

    // Validate that the number of judges to be assigned == the number of judges
    // required to score an entry in this round.
    $count_assign = NULL;

    $judges = $form_state->getValue('judges');
    if (!empty($judges)) {
      // Filter to checked checkboxes.
      $judges = array_keys(array_filter($judges));
      $count_assign = count($judges);
    }

    $judging = $form['#competition']->getJudging();
    $count_required = (int) $judging->rounds[$judging->active_round]['required_scores'];

    if ($count_assign !== $count_required) {

      $form_state->setError($form['container']['assignments']['content']['judges'], $this->t("Please select @count_required to assign to this entry, as required by Round @round_id configuration.", [
        '@count_required' => $count_required . ' ' . $this->formatPlural($count_required, 'judge', 'judges'),
        '@round_id' => $judging->active_round,
      ]));

    }
  }

  /**
   * Custom validation handler for score submit button.
   *
   * @param array $form
   *   Form render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current state of form.
   */
  public function ajaxScoringValidate(array &$form, FormStateInterface $form_state) {

    // If judge is attempting to finalize their score, ensure that they've
    // submitted values for all criteria.
    $finalized = (bool) $form_state->getValue('finalized');
    $was_finalized = $form['container']['scoring']['score_subform']['finalized']['#default_value'];

    if (!$was_finalized && $finalized) {

      $round = $form['#judging']->rounds[$form['#judging']->active_round];

      for ($i = 0; $i < count($round['weighted_criteria']); $i++) {
        $key = 'c' . $i;
        $elem = &$form['container']['scoring']['score_subform'][$key];

        if ($form_state->getValue($key) === $elem['#empty_value']) {

          $form_state->setError($elem, $this->t("Please select a value for %title in order to finalize your scores.", [
            '%title' => $elem['#title'],
          ]));

        }
      }

    }

  }

  /**
   * AJAX submit handler for scoring submit button.
   *
   * @param array $form
   *   From render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current state of form.
   */
  public function ajaxScoringSubmit(array &$form, FormStateInterface $form_state) {

    $triggering_element = $form_state->getTriggeringElement();

    if ($triggering_element['#name'] == 'submit_score') {

      $round = $form['#judging']->rounds[$form['#judging']->active_round];
      $scores = array();

      for ($i = 0; $i < count($round['weighted_criteria']); $i++) {
        $scores["c$i"] = $form_state->getValue("c$i");
      }

      $finalized = (bool) $form_state->getValue('finalized');

      // Saving score also handles logging the scoring action and (if true) the
      // finalizing action.
      $form['#entry']->setJudgeScore($form['#judging']->active_round, $this->currentUser->id(), $scores, $finalized);

      // Log judge's custom note.
      if ($note = $form_state->getValue('note')) {

        $form['#entry']->addJudgingLog($this->currentUser->id(), $form['#judging']->active_round, $note);

      }

      drupal_set_message($this->t('Scores for entry @ceid have been saved.', ['@ceid' => $form['#entry']->id()]));

      // Update newly-saved entry in form state, for use in rebuild.
      $form_state->set('competition_entry', $form['#entry']);

    }

    $form_state->setRebuild(TRUE);

  }

  /**
   * Implements callback for AJAX event on score submit button.
   *
   * @param array $form
   *   Form render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current state of form.
   */
  public function ajaxScoringCallback(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    // Replace scoring sub-form within tab content.
    $response->addCommand(new ReplaceCommand('#competition-entry-modal-score-subform', $form['container']['scoring']['score_subform']));

    // Inject status messages at top of sub-form.
    $response->addCommand(new PrependCommand('#competition-entry-modal-score-subform', [
      '#type' => 'status_messages',
    ]));

    // Replace Notes tab content (to update with log messages from this action).
    $response->addCommand(new ReplaceCommand('#competition-entry-modal-notes-content', $form['container']['notes']['content']));

    return $response;
  }

  /**
   * AJAX submit handler for button to update judge assignments.
   *
   * @param array $form
   *   Form render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current state of form.
   */
  public function ajaxJudgeAssignSubmit(array &$form, FormStateInterface $form_state) {

    $to_assign = $form_state->getValue('judges');
    if (!empty($to_assign)) {
      // Filter to checked checkboxes.
      $to_assign = array_keys(array_filter($to_assign));

      // Compare to those already assigned.
      // Note that validation handler has ensured that the number of judges
      // selected == number required for this round.
      $diff = array_diff($to_assign, $form_state->get('current_judges_assigned'));

      if (empty($diff)) {
        drupal_set_message($this->t("The selected judges are already assigned to this entry."), 'warning');
      }
      else {
        // Set new assignments, without replacing existing ones.
        $form['#entry']->assignJudges($form['#judging']->active_round, $to_assign, FALSE);
        drupal_set_message($this->t("Judge assignments for entry @ceid updated.", [
          '@ceid' => $form['#entry']->id(),
        ]));

        // Update newly-saved entry in form state, for use in rebuild.
        $form_state->set('competition_entry', $form['#entry']);
      }
    }

    $form_state->setRebuild(TRUE);

  }

}
