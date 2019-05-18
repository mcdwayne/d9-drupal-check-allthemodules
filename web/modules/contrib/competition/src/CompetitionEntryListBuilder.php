<?php

namespace Drupal\competition;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\competition\Entity\Competition;

/**
 * Defines a class to build a listing of Competition entries.
 *
 * @ingroup competition
 */
class CompetitionEntryListBuilder extends EntityListBuilder {
  /**
   * The competition manager.
   *
   * @var \Drupal\competition\CompetitionManager
   */
  protected $competitionManager;

  /**
   * The competition.
   *
   * @var \Drupal\competition\CompetitionInterface
   */
  protected $competition;

  /**
   * The current HTTP request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * The competition judging service.
   *
   * @var \Drupal\competition\CompetitionJudgingSetup
   */
  protected $judgingSetup;

  /**
   * Bool to indicate if this class will render a competition judging list.
   *
   * @var bool
   * @see \Drupal\competition\Controller\CompetitionEntryController::adminJudging()
   */
  protected $isJudgingList;

  /**
   * Boolean to indicate this is displaying list of entries in a judging queue.
   *
   * May only be TRUE if $isJudgingList is also TRUE.
   *
   * @var bool
   */
  protected $isJudgingQueue;

  /**
   * Whether the current user has 'administer competition judging' permission.
   *
   * @var bool
   */
  protected $userIsJudgingAdmin;

  /**
   * The current route parameters.
   *
   * @var array
   */
  protected $routeParameters;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $dbConnection;

  /**
   * Constructs a new CompetitionEntryListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\competition\CompetitionManager $competition_manager
   *   The competition manager service.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request service.
   * @param \Drupal\Core\Session\AccountProxy $current_user
   *   The user service.
   * @param \CompetitionJudgingSetup $judging_setup
   *   The Competition Judging service.
   * @param \Drupal\Core\Database\Connection $db_connection
   *   The database service.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, CompetitionManager $competition_manager, Request $request, AccountProxy $current_user, CompetitionJudgingSetup $judging_setup, Connection $db_connection) {
    $this->competitionManager = $competition_manager;
    $this->request = $request;
    $this->currentUser = $current_user;
    $this->judgingSetup = $judging_setup;
    $this->dbConnection = $db_connection;

    $url = Url::createFromRequest($this->request);

    $this->routeParameters = $url->getRouteParameters();

    // Set judging context variables.
    $this->isJudgingList = (stripos($url->getInternalPath(), 'judging') !== FALSE);

    if (isset($this->routeParameters['competition'])) {
      $this->competition = $this->competitionManager->getCompetitionFromUrl($request->getRequestUri());
    }

    $this->isJudgingQueue = FALSE;
    if ($this->isJudgingList && !empty($this->competition)) {
      $judging = $this->competition->getJudging();
      $this->isJudgingQueue = array_key_exists($this->routeParameters['callback'], $judging->queues);
    }

    $this->userIsJudgingAdmin = $this->currentUser->hasPermission('administer competition judging');

    parent::__construct($entity_type, $storage);
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('competition.manager'),
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('current_user'),
      $container->get('competition.judging_setup'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    // Return early if this is not a judging list.
    if (!$this->isJudgingList) {
      $header['ceid'] = $this->t('ID');
      $header['competition'] = $this->t('Competition');

      $header = $header + parent::buildHeader();

      return $header;
    }

    // Stuff for a judging list.
    $judging = $this->competition->getJudging();
    $round = $judging->rounds[$judging->active_round];
    // TODO: improve round type / "does this round use scoring?" logic.
    $scoring_round = in_array($round['round_type'], ['pass_fail', 'criteria']);

    $header['ceid'] = $this->t('ID');

    if ($scoring_round && ($this->routeParameters['callback'] !== 'assignments') && $this->userIsJudgingAdmin) {
      $header['count_complete'] = $this->t("Scores Finalized");
    }

    // Users who are not judging admins will get a basic column.
    $header['score_average'] = ($round['round_type'] == 'pass_fail' ? $this->t('Status') : $this->t('Score'));

    // Judging admins get a more sophisticated column.
    if (($this->routeParameters['callback'] !== 'assignments') && $this->userIsJudgingAdmin) {

      // Label.
      switch ($round['round_type']) {
        case 'pass_fail':
          $label = $this->t('Status');
          break;

        case 'voting':
          $label = $this->t('Votes');
          break;

        default:
          $label = $this->t('Score');
          break;
      }

      // Sorting query.
      if ($round['round_type'] == 'voting') {

        $header['score_average'] = [
          'field' => 'ast.votes',
          'specifier' => 'ast.votes',
          'data' => $label,
        ];
      }
      else {
        $header['score_average'] = [
          'field' => 'ast.scores_computed',
          'specifier' => 'ast.scores_computed',
          'data' => $label,
        ];
      }
    }

    $header = $header + parent::buildHeader();
    unset($header['operations']);

    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $competition = $this->competitionManager->getCompetition($entity->bundle());

    // Return early if this is not a judging list.
    if (!$this->isJudgingList) {
      $row['ceid'] = $entity->label();
      $row['competition'] = $competition->getCycleLabel($competition->getCycle()) . ' ' . $competition->getLabel();

      $row = $row + parent::buildRow($entity);

      return $row;
    }

    // Stuff for a judging list.
    $data = $entity->getData();
    $judging = $this->competition->getJudging();
    $round = $judging->rounds[$judging->active_round];
    $round_data = [];
    if (!empty($data['judging']['rounds'][$judging->active_round])) {
      $round_data = $data['judging']['rounds'][$judging->active_round];
    }
    // TODO: improve round type / "does this round use scoring?" logic.
    $scoring_round = in_array($round['round_type'], ['pass_fail', 'criteria']);

    $row_properties = [];

    // ID (ceid).
    $row['ceid'] = Link::fromTextAndUrl($entity->label(), new Url(
      'entity.competition_entry.judging.entry', [
        'competition_entry' => $entity->id(),
      ],
      [
        'attributes' => [
          'class' => ['use-ajax'],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => Json::encode([
            'width' => '75%',
          ]),
        ],
      ]
    ));

    // Scores Finalized (count_complete) -- visible to admin only.
    if ($scoring_round && ($this->routeParameters['callback'] !== 'assignments') && $this->userIsJudgingAdmin) {
      $row['count_complete'] = '-';

      if (!empty($round_data)) {
        if (!empty($round_data['scores'])) {
          $count_finalized = 0;
          foreach ($round_data['scores'] as $score) {
            if ($score->finalized) {
              $count_finalized++;
            }
          }
          $row['count_complete'] = $count_finalized . " / " . count($round_data['scores']);

          // Set background color on table row according to how many
          // judges have finalized their scores thus far.
          if ($count_finalized > 0) {
            if ($count_finalized == count($round_data['scores'])) {
              // All assigned judges have completed and finalized their scores.
              $row_properties['class'][] = 'score-final';
            }
            else {
              // At least 1 assigned judge has completed and finalized score.
              $row_properties['class'][] = 'score-progress';
            }
          }
        }
      }
    }

    // Status/Score (score_average).
    $row['score_average'] = '-';
    switch ($this->routeParameters['callback']) {
      // Current user assignments.
      case 'assignments':
        if ($entity->hasJudgeScore($judging->active_round, $this->currentUser->id())) {
          // Set background color on table row according to current judge's
          // scoring status on this entry:
          // - 'score-final' (green) = completed and finalized
          // - 'score-complete' (yellow) = completed but not finalized
          // - (none) = incomplete (only some criteria values) or none at all.
          if ($entity->hasJudgeScoreFinalized($judging->active_round, $this->currentUser->id())) {
            $row_properties['class'][] = 'score-final';
          }
          else {
            $row_properties['class'][] = 'score-complete';
          }

          $score = $entity->getJudgeScore($judging->active_round, $this->currentUser->id());
          if (!empty($score->criteria)) {
            $average = number_format(array_sum(array_values($score->criteria)), 2);
            $display = $average . '%';
            if ($round['round_type'] == 'pass_fail') {
              $display = ($average == 100 ? $this->t('Pass') : $this->t('Fail'));
            }

            $row['score_average'] = $display;
          }
        }
        break;

      // All Round N entries, Queues.
      case 'round-' . $judging->active_round:
      default:

        // Since 0 is a legitimate possible score, check against NULL.
        if ($scoring_round && $round_data['computed'] !== NULL) {
          $round_data['computed'] = number_format($round_data['computed'], 2);

          // Add AJAX link to show score details table.
          // Via 'use-ajax' class, the /nojs/ path component will be changed to
          // '/ajax/'.
          $score_url = Url::fromRoute(
            'entity.competition_entry.judging.score_details',
            [
              'is_ajax' => 'nojs',
              'entry' => $entity->id(),
              'round_id' => $judging->active_round,
            ],
            [
              'attributes' => [
                'id' => 'entry-score-' . $entity->id(),
                'class' => ['use-ajax'],
              ],
            ]
          );

          $score_markup = Link::fromTextAndUrl($round_data['computed'] . "%", $score_url);

          // Add pass/fail status label.
          if ($round['round_type'] == 'pass_fail') {
            // Currently, pass == all assigned judges marked as pass, i.e.
            // average score == 100.
            $score_markup = Link::fromTextAndUrl(($round_data['computed'] == 100 ? $this->t('Pass') : $this->t('Fail')), $score_url);
          }

          $row['score_average'] = [
            'data' => [
              '#markup' => $score_markup->toString(),
            ],
          ];
        }
        elseif ($round['round_type'] == 'voting') {
          // Voting round - show total number of votes.
          // TODO: voting round isolation into voting submodule.
          $row['score_average'] = Link::fromTextAndUrl((isset($round_data['votes']) ? $round_data['votes'] : ''), new Url(
            'entity.competition_entry.view_votes', [
              'competition_entry' => $entity->id(),
            ]));

        }
        break;
    }

    $row = $row + parent::buildRow($entity);
    unset($row['operations']);

    // Apply row properties aside of cells.
    // For 'table' render element, a row can be just an array of cells - which
    // is how parent list builder creates the row. To add other properties, we
    // restructure the array.
    // @see template_preprocess_table
    //
    // Skip row colorization for queues.
    if (!in_array($this->routeParameters['callback'], ['assignments', 'round-' . $judging->active_round])) {
      $row_properties = [];
    }

    if (!empty($row_properties)) {
      $row = array_merge([
        // Move cells down to 'data' key.
        'data' => $row,
      ], $row_properties);
    }

    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    // Return early if this is not a judging list.
    if (!$this->isJudgingList || empty($this->competition)) {
      return parent::load();
    }

    $judging = $this->competition->getJudging();

    // TODO: improve round type / "does this round use scoring?" logic.
    $round = $judging->rounds[$judging->active_round];
    $scoring_round = in_array($round['round_type'], ['pass_fail', 'criteria']);
    $voting_round = $round['round_type'] == 'voting';

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

    // Get entries according to filters.
    $entry_ids = $this->judgingSetup->filterJudgingEntries($this->competition->id(), $filters);

    if (!empty($entry_ids)) {
      /*
       * CompetitionJudgingSetup::filterJudgingEntries() retrieves entries
       * matching the filter criteria - but we need to apply a per-page limit.
       * Currently, filterJudgingEntries() loads entries' data via basic query,
       * then loops to apply filters on judging data. We need a slice of that
       * filtered set. Rather than rebuilding pager functionality, run another
       * query - limiting to the set of filtered entry IDs - and apply pager
       * there. We'll then load the resulting entities.
       */
      /* @var \Drupal\Core\Database\Query\SelectInterface $query */
      $query = $this->judgingSetup
        ->getJudgingEntriesBaseQuery($this->competition->id())
        ->condition('ce.ceid', $entry_ids, 'IN');

      // Add sort using separate index table.
      // Note that index table has already been joined -.
      // @see CompetitionJudgingSetup::getJudgingEntriesBaseQuery()
      //
      // TODO: voting round isolation into submodule.
      if (($scoring_round || $voting_round) && $this->request->query->has('order') && $this->request->query->has('sort')) {

        if (in_array($this->routeParameters['callback'], ['assignments', 'round-' . $judging->active_round])) {
          $query->condition('ast.scores_round', $judging->active_round);
        }

        if ($scoring_round) {
          $query->orderBy('ast.scores_finalized', $this->request->query->get('sort'));
          $query->orderBy('ast.scores_computed', $this->request->query->get('sort'));
        }
        elseif ($voting_round) {
          $query->orderBy('ast.votes', $this->request->query->get('sort'));
        }

      }

      // Add pager support.
      if ($this->limit) {
        $entry_ids = $query
          ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
          ->limit($this->limit)
          ->execute()
          ->fetchCol(0);
      }
      else {
        $entry_ids = $query
          ->execute()
          ->fetchCol(0);
      }

      return $this->storage->loadMultiple($entry_ids);
    }

    return [];
  }

  /**
   * {@inheritdoc}
   *
   * Override empty message, particularly for judging rounds and queues.
   */
  public function render() {
    $build = parent::render();
    $build['table']['#attributes']['class'][] = 'competition-entries-table';

    $empty = $this->t('There are no entries yet.');

    // Return early if this is not a judging list.
    if (!$this->isJudgingList || empty($this->competition)) {
      $build['table']['#empty'] = $empty;
      return $build;
    }

    $build['table']['#attributes']['class'][] = 'judging';

    $judging = $this->competition->getJudging();

    // Add header content according to judge user and active round.
    $round_description = NULL;
    $color_key = NULL;

    switch ($this->routeParameters['callback']) {
      case 'assignments':
        // Current user assignments.
        $empty = $this->t("There are no entries assigned to you in this round yet.");

        // Round description.
        $round_description = $judging->rounds[$judging->active_round]['criteria_description'];

        // Color legend for rows.
        $color_key = [
          'green' => $this->t("Green rows indicate that you've submitted and finalized your scores for those entries."),
          'yellow' => $this->t("Yellow rows indicate that you've submitted but not finalized your scores for those entries."),
        ];

        break;

      case 'round-' . $judging->active_round:
        // All Round N entries.
        $empty = $this->t('There are no entries in this round yet. <a href=":url_judging_setup">Assign judges to move entries into this round.</a>', [
          ':url_judging_setup' => Url::fromRoute('entity.competition_entry.judging', [
            'competition' => $this->competition->id(),
            'callback' => 'setup',
          ])->toString(),
        ]);

        // Color legend for rows.
        // TODO: voting round isolation into voting submodule.
        if ($judging->rounds[$judging->active_round]['round_type'] != 'voting') {
          $color_key = [
            'green' => $this->t("Green rows indicate that all assigned judges have submitted and finalized their scores for those entries."),
            'yellow' => $this->t("Yellow rows indicate that at least one assigned judge has submitted and finalized their score on each of those entries."),
          ];
        }

        break;

      default:
        // Queues.
        $empty = $this->t('There are no entries in this queue.');
        break;
    }

    $build['table']['#empty'] = $empty;

    // Add expand/collapse "Instructions" area above table.
    if (!empty($round_description) || !empty($color_key)) {

      $build['instructions'] = [
        '#type' => 'details',
        '#title' => $this->t("Instructions"),
        '#open' => FALSE,
        '#attributes' => [
          'class' => ['judging-instructions'],
        ],
        '#weight' => -10,
      ];

      if (!empty($round_description)) {
        $build['instructions']['round_description'] = [
          '#markup' => '<div class="round-description">
            <p class="description">' . $round_description . '</p>
          </div>',
        ];
      }

      if (!empty($color_key)) {
        $build['instructions']['color_key'] = [
          '#markup' => '<div class="color-key">
            <div class="final messages messages--status">' . $color_key['green'] . '</div>
            <div class="complete messages messages--warning">' . $color_key['yellow'] . '</div>
          </div>',
        ];
      }

    }

    return $build;
  }

  /**
   * Set route parameters.
   *
   * @param array $route_parameters
   *   Route parameters.
   */
  public function setRouteParameters(array $route_parameters) {
    $this->routeParameters = $route_parameters;
  }

  /**
   * Set competition.
   *
   * @param \Drupal\competition\Entity\Competition $competition
   *   Set competition object and judging.
   * @param bool $is_judging_list
   *   Whether this list builder is a judging list.
   */
  public function setCompetition(Competition $competition, $is_judging_list = FALSE) {

    $this->competition = $competition;
    $this->isJudgingList = $is_judging_list;

    $this->isJudgingQueue = FALSE;
    if ($this->isJudgingList && !empty($this->competition)) {
      $judging = $this->competition->getJudging();
      $this->isJudgingQueue = array_key_exists($this->routeParameters['callback'], $judging->queues);
    }

  }

}
