<?php

namespace Drupal\competition;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationManager;
use Drupal\Core\Url;

/**
 * Service to warehouse utilities for configuring competition judging.
 */
class CompetitionJudgingSetup {

  use StringTranslationTrait;

  const INDEX_TABLE = 'competition_entry_index';

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $dbConnection;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * The CompetitionManager service.
   *
   * @var \Drupal\competition\CompetitionManager
   */
  protected $competitionManager;

  /**
   * The user storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storageUser;

  /**
   * The competition storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storageCompetition;

  /**
   * The competition entry storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storageCompetitionEntry;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Database\Connection $db_connection
   *   The database connection.
   * @param \Drupal\Core\StringTranslation\TranslationManager $string_translation
   *   The string translation service.
   * @param \Drupal\Core\Session\AccountProxy $current_user
   *   The logged-in user account.
   * @param \Drupal\competition\CompetitionManager $competition_manager
   *   The competition manager service.
   */
  public function __construct(ConfigFactory $config_factory, EntityTypeManagerInterface $entity_type_manager, Connection $db_connection, TranslationManager $string_translation, AccountProxy $current_user, CompetitionManager $competition_manager) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->dbConnection = $db_connection;
    // Defined in StringTranslationTrait.
    $this->stringTranslation = $string_translation;
    $this->currentUser = $current_user;
    $this->competitionManager = $competition_manager;

    $this->storageUser = $this->entityTypeManager->getStorage('user');
    $this->storageCompetition = $this->entityTypeManager->getStorage('competition');
    $this->storageCompetitionEntry = $this->entityTypeManager->getStorage('competition_entry');
  }

  /**
   * Helper to load competition entity.
   *
   * @param string $competition_id
   *   Competition ID.
   *
   * @return \Drupal\competition\CompetitionInterface
   *   The competition entity.
   *
   * @throws \InvalidArgumentException
   *   If there is no competition entity with the given ID.
   */
  protected function loadCompetition($competition_id) {
    // Note: EntityStorageBase::loadMultiple() handles caching already.
    $competition = $this->storageCompetition->load($competition_id);

    if ($competition === NULL) {
      throw new \InvalidArgumentException("Argument \$competition_id must be the ID of a competition entity that exists.");
    }

    return $competition;
  }

  /**
   * Get judge users (IDs or entities).
   *
   * Defined as having the permission 'judge competition entries'.
   *
   * In module's default configuration, this is all users having role
   * 'competition_judge' or 'competition_judge_leader'.
   *
   * @param string|null $role
   *   A user role name by which to limit returned users.
   * @param bool $load_entities
   *   Whether to load entities.
   *
   * @return array|\Drupal\user\UserInterface[]
   *   Array of user IDs or user entities
   */
  public function getJudgeUsers($role = NULL, $load_entities = FALSE) {
    $permission = 'judge competition entries';
    $roles = [];

    // Find all roles with this permission.
    $role_storage = $this->entityTypeManager->getStorage('user_role');
    /** @var \Drupal\user\RoleInterface $role */
    foreach ($role_storage->loadMultiple() as $role_id => $role) {
      // Bypass roles with 'is_admin' = TRUE, because they automatically have
      // all permissions. This is unintuitive; it's not likely that judge roles
      // are also site admin roles. Therefore, don't use hasPermissions() -
      // which returns TRUE for any role with is_admin = TRUE.
      // @see Role::hasPermission()
      // TODO: confirm we do want to bypass admin roles because they
      // automatically receive all permissions.
      if (in_array($permission, $role->getPermissions())) {
        $roles[] = $role_id;
      }
    }

    $uids = [];

    // No need to query for users if there are no roles.
    if (!empty($roles)) {
      /** @var \Drupal\Core\Entity\Query\QueryInterface $query */
      $query = $this->storageUser->getQuery();

      // Limit to active (not blocked)?
      // $query->condition('status', 1);.
      $or_roles = $query->orConditionGroup();
      foreach ($roles as $role_id) {
        $or_roles->condition('roles', $role_id);
      }
      $query->condition($or_roles);

      $uids = $query->execute();
    }

    if ($load_entities && !empty($uids)) {
      return $this->storageUser->loadMultiple($uids);
    }
    else {
      return $uids;
    }
  }

  /**
   * Helper to return judges assigned to a given round.
   *
   * @param string $competition_id
   *   The competition ID.
   * @param int $round_id
   *   The round ID.
   * @param bool $load_entities
   *   Whether to load entities.
   *
   * @return array|\Drupal\user\UserInterface[]
   *   Array of user IDs or user entities.
   *
   * @see \Drupal\competition\Form\CompetitionJudgesRoundsSetupForm
   */
  public function getJudgesForRound($competition_id, $round_id, $load_entities = FALSE) {
    $competition = $this->loadCompetition($competition_id);
    $judging = $competition->getJudging();

    $round_id = (int) $round_id;

    $uids = [];
    foreach ($judging->judges_rounds as $uid => $rounds) {
      if (in_array($round_id, $rounds, TRUE)) {
        $uids[] = (int) $uid;
      }
    }

    // Load entities to ensure returning only users that still exist (in case of
    // deleted users leading to stale assignment data).
    // loadMultiple() simply leaves out any ids that did not load an entity.
    $users = $this->storageUser->loadMultiple($uids);
    $uids_valid = array_keys($users);

    if (count($uids_valid) != count($uids)) {
      $uids_invalid = array_diff($uids, $uids_valid);

      drupal_set_message($this->formatPlural(
        count($uids_invalid),
        "User ID %uids was assigned to judge Round @round, but no user account for this ID was found. Most likely the account was deleted. This user ID has been bypassed.",
        "User IDs %uids were assigned to judge Round @round, but no user accounts for these IDs were found. Most likely the accounts were deleted. These user IDs have been bypassed.",
        [
          '%uids' => implode(", ", $uids_invalid),
          '@round' => $round_id,
        ]
      ), 'warning');
    }

    return ($load_entities ? $users : $uids_valid);
  }

  /**
   * Get the rounds to which a judge user is assigned, in a given competition.
   *
   * Note: this does not validate that $judge_uid is a user with appropriate
   * judging permissions - only whether that user ID is present in the
   * current judge/round assignment settings.
   *
   * @param string $competition_id
   *   The competition entity ID.
   * @param int $judge_uid
   *   The judge user ID.
   *
   * @return array
   *   Array of integer IDs of rounds. Empty array if this user ID is not
   *   assigned to any.
   *
   * @see \Drupal\competition\Form\CompetitionJudgesRoundsSetupForm
   */
  public function getJudgeAssignedRounds($competition_id, $judge_uid) {
    $competition = $this->loadCompetition($competition_id);
    $judging = $competition->getJudging();

    $judge_uid = (int) $judge_uid;

    $rounds = [];

    if (!empty($judging->judges_rounds) && !empty($judging->judges_rounds[$judge_uid])) {
      $rounds = $judging->judges_rounds[$judge_uid];
    }

    return $rounds;
  }

  /**
   * Check if a judge is assigned to any judging round(s) in this competition.
   *
   * Note: this does not validate that $judge_uid is a user with appropriate
   * judging permissions - only whether that user ID is present in the
   * current judge/round assignment settings.
   *
   * @param string $competition_id
   *   The competition ID.
   * @param int $judge_uid
   *   The user ID of a judge user.
   *
   * @return bool
   *   TRUE if judge is assigned to one or more rounds; else FALSE.
   *
   * @see \Drupal\competition\Form\CompetitionJudgesRoundsSetupForm
   */
  public function isJudgeAssignedCompetition($competition_id, $judge_uid) {
    $competition = $this->loadCompetition($competition_id);
    $judging = $competition->getJudging();

    $judge_uid = (int) $judge_uid;

    return (!empty($judging->judges_rounds) && !empty($judging->judges_rounds[$judge_uid]));
  }

  /**
   * Get all competitions in which a judge is assigned to any judging round(s).
   *
   * Note: this does not validate that $judge_uid is a user with appropriate
   * judging permissions - only whether that user ID is present in the
   * current judge/round assignment settings.
   *
   * @param int $judge_uid
   *   The user ID of a judge user.
   *
   * @return \Drupal\competition\CompetitionInterface[]
   *   Array of all competitions in which this judge is assigned to one or more
   *   rounds; empty array if none.
   *
   * @see \Drupal\competition\Form\CompetitionJudgesRoundsSetupForm
   */
  public function getJudgeAssignedCompetitions($judge_uid) {
    $judge_uid = (int) $judge_uid;

    $competitions = [];

    $competitions_all = $this->competitionManager->getCompetitions(NULL, TRUE);
    foreach ($competitions_all as $competition) {
      $judging = $competition->getJudging();

      if (!empty($judging->judges_rounds) && !empty($judging->judges_rounds[$judge_uid])) {
        $competitions[] = $competition;
      }
    }

    return $competitions;
  }

  /**
   * Assign given entries to judges in a round.
   *
   * Assignments are split as evenly as possible between all judges, according
   * to how many judges are required to judge each entry in this round.
   *
   * This kicks off a batch process (unless there is an issue with judging
   * setup such that assignment cannot proceed).
   *
   * @param int $competition_id
   *   The competition ID.
   * @param int $round_id
   *   The round ID.
   * @param array $entry_ids
   *   Entry IDs.
   *
   * @return bool|null|\Symfony\Component\HttpFoundation\RedirectResponse
   *   The result of batch_process() call - a redirect response or NULL - or
   *   FALSE if a setup issue prevented running batch.
   */
  public function assignEntries($competition_id, $round_id, array $entry_ids) {

    /** @var \Drupal\competition\CompetitionInterface $competition */
    $competition = $this->loadCompetition($competition_id);
    $judging = $competition->getJudging();

    // TODO: determine if this would ever happen; check for now as backup.
    // TODO: how to handle error situation? throw exception?
    if (empty($entry_ids)) {
      drupal_set_message($this->t("There are no entries to be assigned in this round."), 'warning');
      return FALSE;
    }

    // Get number of judges required per entry for this round.
    // If this is a voting round then limit to just 1 judge.
    $num_judges_per = ($judging->rounds[$round_id]['round_type'] == 'voting') ? 1 : (int) $judging->rounds[$round_id]['required_scores'];

    // Get the judges for this round.
    $judge_uids = $this->getJudgesForRound($competition_id, $round_id);

    // Are there enough judge accounts to assign required number of judges per
    // entry?
    // TODO: how to handle error situation? throw exception?
    if (count($judge_uids) < $num_judges_per) {
      drupal_set_message($this->t("Judging assignment could not be completed:<br/><br/>Round @round requires @judges_per judging score(s) per entry, but there are only @num_judges judge account(s) assigned to this round. Add more accounts with the 'Judge competition entries' permission and/or assign judge accounts to this round to continue.", [
        '@round' => $round_id,
        '@judges_per' => $num_judges_per,
        '@num_judges' => count($judge_uids),
      ]), 'error');
      return FALSE;
    }

    $batch = [
      'title' => $this->t("Assigning entries to judges..."),
      'operations' => [
        [
          // Callback.
          [static::class, 'assignEntriesBatchProcess'],
          // Arguments to pass to callback.
          [
            $round_id,
            $judge_uids,
            $entry_ids,
            $num_judges_per,
          ],
        ],
      ],
      'finished' => [static::class, 'assignEntriesBatchFinished'],
    ];

    batch_set($batch);

  }

  /**
   * Entry/judge assignment batch processor.
   *
   * @param int $round_id
   *   The integer ID of the round.
   * @param array $judge_uids
   *   User IDs of all judges configured to judge in this round.
   * @param array $entry_ids_all
   *   Array of IDs of all competition entries in this round, to be assigned.
   * @param int $num_judges_per
   *   Batch size.
   * @param array $context
   *   Key 'sandbox' contains values that persist through all calls to this op.
   *   Key 'results' contains values to pass to the batch-finished function.
   */
  public static function assignEntriesBatchProcess($round_id, array $judge_uids, array $entry_ids_all, $num_judges_per, array &$context) {

    if (empty($context['sandbox'])) {

      $context['results']['round_id'] = $round_id;

      // Counter of entries processed currently.
      $context['results']['count_entries'] = 0;

      // Total number of entries to process.
      $context['sandbox']['total'] = count($entry_ids_all);

      // Assignments made, keyed by judge uid.
      $context['results']['assigned_by_judge'] = [];

      // Assignments made, keyed by entry id.
      $context['results']['assigned_by_entry'] = [];

      // Current index within array of available judges.
      $context['sandbox']['judge_index'] = 0;

      shuffle($judge_uids);

    }

    $count_judges = count($judge_uids);

    // Convenience var.
    $j = &$context['sandbox']['judge_index'];

    $per = \Drupal::config('competition.settings')->get('batch_size');

    $entry_ids = array_slice($entry_ids_all, $context['results']['count_entries'], $per);
    $entries = \Drupal::entityTypeManager()->getStorage('competition_entry')->loadMultiple($entry_ids);

    /** @var \Drupal\competition\CompetitionEntryInterface $entry */
    foreach ($entries as $entry_id => $entry) {

      /*
       * Evenly-divided assignment logic:
       *
       * Loop through the available judges, repeatedly. Assign the next N judges
       * to the current entry. This ensures:
       * a) unique set of judges for each entry (no repeats)
       * b) even distribution of entries across all judges
       *
       * Example: 3 judges required per entry; 4 judges available
       *   entry 1: j1, j2, j3
       *   entry 2: j4, j1, j2
       *   entry 3: j3, j4, j1
       *   entry 4: j2, j3, j4
       *   entry 5: j1, j2, j3
       *
       *   15 assignments; j1 => 4, j2 => 4, j3 => 4, j4 => 3
       */
      // Collect judges for this entry.
      $assign_uids = [];
      for ($i = 0; $i < $num_judges_per; $i++) {
        $assign_uids[] = $judge_uids[$j];

        // Update repeated-looping judge index.
        $j++;
        if ($j == $count_judges) {
          $j = 0;
        }
      }

      // Save assignments.
      $entry->assignJudges($round_id, $assign_uids);

      // Log the assignment.
      $context['results']['assigned_by_entry'][$entry_id] = $assign_uids;
      foreach ($assign_uids as $uid) {
        $context['results']['assigned_by_judge'][$uid][] = $entry_id;
      }

      $context['results']['count_entries']++;
    }

    // Update progress.
    $context['finished'] = $context['results']['count_entries'] / $context['sandbox']['total'];
  }

  /**
   * Entry/judge assignment batch completion handler.
   *
   * @param bool $success
   *   TRUE if no PHP fatals.
   * @param array $results
   *   The $context['results'] array built during operation callbacks.
   * @param array $operations
   *   Batch API operations.
   */
  public static function assignEntriesBatchFinished($success, array $results, array $operations) {
    if ($success) {
      $output = [];

      $output['msg'] = [
        '#markup' => \Drupal::translation()->formatPlural(
          $results['count_entries'],
          "Assigned 1 entry to judges in Round @round.",
          "Assigned @count entries to judges in Round @round.",
          [
            '@round' => $results['round_id'],
          ]
        ),
      ];

      drupal_set_message(\Drupal::service('renderer')->render($output));
    }
    else {
      drupal_set_message(t('An error occurred while assigning judges.'), 'error');
    }
  }

  /**
   * Remove all entries from given round - delete judge assignments and scores.
   *
   * This also currently handles removing entries from voting round and
   * deleting votes.
   * TODO: voting round isolation into voting submodule.
   *
   * @param int $competition_id
   *   The competition ID.
   * @param int $round_id
   *   The round ID.
   */
  public function unassignAllEntriesRound($competition_id, $round_id) {
    $entry_ids = $this->filterJudgingEntries($competition_id, [
      'round_id' => $round_id,
    ]);

    $batch = [
      'title' => $this->t('Deleting entry/judge assignments...'),
      'operations' => [
        [
          // Callback.
          [static::class, 'unassignAllEntriesRoundBatchProcess'],
          // Arguments to pass to callback.
          [
            $competition_id,
            $entry_ids,
            $round_id,
          ],
        ],
      ],
      'finished' => [static::class, 'unassignAllEntriesRoundBatchFinished'],
    ];

    batch_set($batch);

  }

  /**
   * Remove entries from round - batch processor.
   *
   * @param string $competition_id
   *   The competition entity ID.
   * @param array $entry_ids_all
   *   The entry IDs.
   * @param int $round_id
   *   The round ID.
   * @param array $context
   *   The batch API context.
   */
  public static function unassignAllEntriesRoundBatchProcess($competition_id, array $entry_ids_all, $round_id, array &$context) {
    /** @var \Drupal\competition\CompetitionJudgingSetup $judging_setup */
    $judging_setup = \Drupal::service('competition.judging_setup');

    if (empty($context['sandbox'])) {
      $competition = $judging_setup->loadCompetition($competition_id);

      $context['results']['round_id'] = $round_id;

      $judging = $competition->getJudging();
      $context['results']['round_type'] = $judging->rounds[$round_id]['round_type'];

      // Counter of entries processed currently.
      $context['results']['count_entries'] = 0;

      // Total number of entries to process.
      $context['sandbox']['total'] = count($entry_ids_all);

    }

    $per = \Drupal::config('competition.settings')->get('batch_size');

    $entry_ids = array_slice($entry_ids_all, $context['results']['count_entries'], $per);
    $entries = $judging_setup->storageCompetitionEntry->loadMultiple($entry_ids);

    /** @var \Drupal\competition\CompetitionEntryInterface $entry */
    foreach ($entries as $entry) {
      $data = $entry->getData();

      // Remove judge assignments/scores.
      if (array_key_exists($round_id, $data['judging']['rounds'])) {
        unset($data['judging']['rounds'][$round_id]);
      }

      // Remove log messages for all actions in this round.
      if (!empty($data['judging']['log'])) {
        foreach ($data['judging']['log'] as $i => $log) {
          if ($log['round_id'] == $round_id) {
            unset($data['judging']['log'][$i]);
          }
        }
      }

      $entry->setData($data);
      $entry->save();

      $context['results']['count_entries']++;
    }

    // Update progress.
    $context['finished'] = $context['results']['count_entries'] / $context['sandbox']['total'];
  }

  /**
   * Remove entries from round - batch completion handler.
   *
   * @param bool $success
   *   TRUE if no PHP fatals.
   * @param array $results
   *   The $context['results'] array built during operation callbacks.
   * @param array $operations
   *   The batch API operations.
   */
  public static function unassignAllEntriesRoundBatchFinished($success, array $results, array $operations) {
    if ($success) {
      // Delete index records / other data that can be handled in one query.
      if (in_array($results['round_type'], ['pass_fail', 'criteria'])) {
        \Drupal::database()
          ->delete(CompetitionJudgingSetup::INDEX_TABLE)
          ->condition('scores_round', $results['round_id'], '=')
          ->execute();
      }
      elseif ($results['round_type'] == 'voting') {
        // TODO: voting round isolation into voting submodule.
        // Clear votes for all entries in the round.
        /** @var \Drupal\competition_voting\CompetitionVoting $voting */
        $voting = \Drupal::service('competition.voting');
        $count_votes_deleted = $voting->deleteVotes([
          'round_id' => $results['round_id'],
        ]);
      }

      if ($results['round_type'] == 'voting') {
        drupal_set_message(\Drupal::translation()->formatPlural(
          $results['count_entries'],
          'Removed <strong>1</strong> entry from Round @round_id and deleted <strong>@count_votes</strong> associated votes.',
          'Removed <strong>@count</strong> entries from Round @round_id and deleted <strong>@count_votes</strong> associated votes.',
          [
            '@round_id' => $results['round_id'],
            '@count_votes' => $count_votes_deleted,
          ]
        ));
      }
      else {
        drupal_set_message(\Drupal::translation()->formatPlural(
          $results['count_entries'],
          'Removed <strong>1</strong> entry, its scores and judge assignments from Round @round_id.',
          'Removed <strong>@count</strong> entries, their scores and judge assignments from Round @round_id.',
          [
            '@round_id' => $results['round_id'],
          ]
        ));
      }
    }
    else {
      drupal_set_message(t('An error occurred while deleting judge assignments and scores.'), 'error');
    }
  }

  /**
   * Generate test scores for all judges on all entries in a given round.
   *
   * This will overwrite *any* existing score values for this round!
   *
   * @param string $competition_id
   *   The competition ID.
   * @param int $round_id
   *   The round ID.
   *
   * @return bool|null
   *   Returns FALSE on error situation.
   */
  public function generateTestScores($competition_id, $round_id) {

    $entry_ids = $this->filterJudgingEntries($competition_id, [
      'round_id' => $round_id,
    ]);

    // This shouldn't happen if called from judging round workflow form.
    // TODO: how to handle error situation? throw exception?
    if (empty($entry_ids)) {
      drupal_set_message($this->t("There are no entries in the given round."), 'warning');
      return FALSE;
    }

    $batch = [
      'title' => $this->t("Generating test scores..."),
      'operations' => [
        [
          // Callback.
          [static::class, 'generateTestScoresBatchProcess'],
          // Arguments to pass to callback.
          [
            $competition_id,
            $entry_ids,
            $round_id,
          ],
        ],
      ],
      'finished' => [static::class, 'generateTestScoresBatchFinished'],
    ];

    batch_set($batch);

  }

  /**
   * Generate test scores for a round - batch processor.
   *
   * @param string $competition_id
   *   The competition ID.
   * @param array $entry_ids_all
   *   The entry IDs.
   * @param int $round_id
   *   The round IDs.
   * @param array $context
   *   The batch API context.
   */
  public static function generateTestScoresBatchProcess($competition_id, array $entry_ids_all, $round_id, array &$context) {

    if (empty($context['sandbox'])) {

      $context['results']['round_id'] = $round_id;

      // Counter of entries processed currently.
      $context['results']['count_entries'] = 0;

      // Counter of entries that were found to have no judges assigned.
      $context['results']['count_entries_no_judges'] = 0;

      // Total number of entries to process.
      $context['sandbox']['total'] = count($entry_ids_all);

      // Criteria config for this round.
      $competition = \Drupal::entityTypeManager()->getStorage('competition')->load($competition_id);
      $round = $competition->getJudging()->rounds[$round_id];

      $context['sandbox']['criteria_count'] = count($round['weighted_criteria']);

      if ($round['round_type'] == 'pass_fail') {
        /* Pass/Fail rounds have two score value options:
         *   1 => Pass
         *   0 => Fail.
         * @see CompetitionForm::save()
         */
        $context['sandbox']['point_options'] = [1, 0];
      }
      else {
        // TODO: for contrib, consider allowing 0 as a criterion score option.
        $context['sandbox']['point_options'] = range(1, (int) $round['criterion_options'], 1);
      }
    }

    $per = \Drupal::config('competition.settings')->get('batch_size');

    $entry_ids = array_slice($entry_ids_all, $context['results']['count_entries'], $per);
    $entries = \Drupal::entityTypeManager()->getStorage('competition_entry')->loadMultiple($entry_ids);

    /** @var \Drupal\competition\CompetitionEntryInterface $entry */
    foreach ($entries as $entry) {

      // (Judge assignment should be run before this...)
      $data = $entry->getData();
      if (!empty($data['judging']['rounds'][$round_id]['scores'])) {
        $point_options = $context['sandbox']['point_options'];

        foreach ($data['judging']['rounds'][$round_id]['scores'] as $score) {
          // Set a random point value for each criterion.
          $input = [];
          for ($i = 0; $i < $context['sandbox']['criteria_count']; $i++) {
            $input['c' . $i] = $point_options[array_rand($point_options)];
          }
          // Save score (with $finalized = TRUE).
          $entry->setJudgeScore($round_id, $score->uid, $input, TRUE);
        }
      }
      else {
        $context['results']['count_entries_no_judges']++;
      }

      $context['results']['count_entries']++;
    }

    // Update progress.
    $context['finished'] = $context['results']['count_entries'] / $context['sandbox']['total'];

  }

  /**
   * Generate test scores for a round - batch completion handler.
   *
   * @param bool $success
   *   TRUE if no PHP fatals.
   * @param array $results
   *   The $context['results'] array built during operation callbacks.
   * @param array $operations
   *   Batch API operations.
   */
  public static function generateTestScoresBatchFinished($success, array $results, array $operations) {
    if ($success) {

      drupal_set_message(\Drupal::translation()->formatPlural(
        $results['count_entries'] - $results['count_entries_no_judges'],
        "Generated test scores for 1 entry.",
        "Generated test scores for @count entries."
      ));

      if (!empty($results['count_entries_no_judges'])) {
        // Not exactly an error, but shouldn't happen.
        drupal_set_message(\Drupal::translation()->formatPlural(
          $results['count_entries_no_judges'],
          'Notice: Could not generate test scores for 1 entry because it has no judges assigned. (Judge assignment for the round should be run before generating test scores.)',
          'Notice: Could not generate test scores for @count entries because they have no judges assigned. (Judge assignment for the round should be run before generating test scores.)'
        ), 'warning');
      }

    }
    else {
      drupal_set_message(t('An error occurred while generating test scores.'), 'error');
    }
  }

  /**
   * Judging entries base query.
   *
   * Get a select query for all entries available for judging in competition's
   * active cycle (NOT filtered by round, judge assignments, etc).
   *
   * Query has only these base conditions applied:
   *   'type' - entries in the given competition
   *   'cycle' - current cycle of the competition
   *   'status' - finalized
   *
   * No sort is applied.
   *
   * Query is tagged with 'competition_entry_judging'.
   *
   * @param string $competition_id
   *   Competition ID.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   The query - NOT executed.
   */
  public function getJudgingEntriesBaseQuery($competition_id) {

    /** @var \Drupal\competition\CompetitionInterface $competition */
    $competition = $this->loadCompetition($competition_id);

    // $options = $this->dbConnection->getConnectionOptions();
    // $options['pdo'][\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY] = FALSE;.
    /** @var \Drupal\Core\Database\Query\SelectInterface $query */
    $query = $this->dbConnection
    // , $options.
      ->select('competition_entry', 'ce')
      ->fields('ce', [
        'ceid',
        'data',
      ])
      ->condition('ce.type', $competition->id())
      ->condition('ce.cycle', $competition->getCycle())
      ->condition('ce.status', CompetitionEntryInterface::STATUS_FINALIZED);

    $query->leftJoin(CompetitionJudgingSetup::INDEX_TABLE, 'ast', 'ast.ceid = ce.ceid');

    $query->addTag('competition_entry_judging');

    return $query;
  }

  /**
   * Get competition entries filtered by certain judging-related criteria.
   *
   * Conditions always applied:
   *   'type' - entries in the given competition.
   *   'cycle' - current cycle of the competition.
   *   'status' - finalized.
   *
   * @param string $competition_id
   *   Competition ID.
   * @param array $filters
   *   Parameters by which to further filter the initial set - which is entries
   *   in the current cycle of the competition. All are optional.
   *
   *   'ceid' - array
   *     Entry IDs - limit to within this set of IDs
   *   'queue' - string
   *     Limit to entries in this queue. As queues are exclusive, no other
   *     filters are applicable if this is provided (except 'ceid').
   *   'round_id' - int
   *     Limit to entries in this judging round. (This will not include entries
   *     in any queue, by definition.)
   *     If provided as NULL - filter to entries not in any round.
   *   'judging' - boolean
   *     If TRUE - limit to entries that are in any round or queue
   *
   *   These parameters will only apply if 'round_id' is given and non-NULL:
   *   'judge_uid' - int
   *     Entries assigned to this judge, in the given round.
   *   'score_complete' - boolean
   *     If TRUE - if 'judge_uid' is also provided, limit to entries which that
   *     judge has completed scoring; if 'judge_uid' not provided, limit to
   *     entries with complete scores for ALL assigned judges in the round.
   *   'min_score' - float
   *     Entries with average score (thus far) in the given round of at least
   *     this value.
   *     Note: this does not ensure that all assigned judges' scores are
   *     populated or finalized!
   *
   * @return array
   *   Filtered entries.
   */
  public function filterJudgingEntries($competition_id, array $filters) {

    $filters = array_intersect_key($filters, array_flip([
      'ceid',
      'queue',
      'round_id',
      'judging',
      'judge_uid',
      'score_complete',
      'min_score',
    ]));

    $filtered = [];
    $queued = [];

    /** @var \Drupal\Core\Database\Query\SelectInterface $query */
    $query = $this->getJudgingEntriesBaseQuery($competition_id);

    if (!empty($filters['round_id']) && !empty($filters['judge_uid'])) {
      $query->condition('ast.scores_round', $filters['round_id']);
    }

    // Filter to an initial set of entry IDs.
    if (!empty($filters['ceid']) && is_array($filters['ceid'])) {
      $query->condition('ce.ceid', $filters['ceid'], 'IN');
      unset($filters['ceid']);
    }

    /** @var \Drupal\Core\Database\StatementInterface $iterator */
    $iterator = $query->execute();

    if (empty($filters)) {
      // If no further filters, we don't need to iterate the rows.
      // Get 'ceid' column of query result.
      $filtered = $iterator->fetchCol(0);
    }
    else {
      while ($row = $iterator->fetchObject()) {
        $data = unserialize($row->data);

        // Filter for entries in any round or queue.
        if (!empty($filters['judging'])) {
          if (!empty($data['judging']['rounds']) || !empty($data['judging']['queues'])) {
            $filtered[] = $row->ceid;
            continue;
          }
        }

        // Filter for entry in queue.
        if (!empty($filters['queue'])) {
          if (!empty($data['judging']['queues']) && in_array($filters['queue'], $data['judging']['queues'])) {
            $filtered[] = $row->ceid;
            continue;
          }
        }

        if (!empty($data['judging']['queues'])) {
          $queued[] = $row->ceid;
        }

        // Filter for entry NOT in a queue.
        if (!in_array($row->ceid, $queued) && array_key_exists('round_id', $filters)) {

          if ($filters['round_id'] == NULL) {
            // Filter for entry in no rounds.
            if (empty($data['judging']['rounds'])) {
              $filtered[] = $row->ceid;
            }
          }
          elseif (!empty($data['judging']['rounds'][$filters['round_id']])) {
            // Filter for entry in a round.
            if (!empty($filters['judge_uid'])) {
              // Filter for entry assigned to judge.
              if (!empty($data['judging']['rounds'][$filters['round_id']]['scores'])) {
                foreach ($data['judging']['rounds'][$filters['round_id']]['scores'] as $score) {
                  if ($filters['judge_uid'] == $score->uid) {
                    if (!empty($filters['score_complete'])) {
                      // Filter for judge's score complete.
                      $complete = TRUE;
                      foreach ($score->criteria as $val) {
                        if ($val === NULL) {
                          $complete = FALSE;
                        }
                      }
                      if ($complete) {
                        $filtered[] = $row->ceid;
                      }
                    }
                    else {
                      // All entries assigned to judge.
                      $filtered[] = $row->ceid;
                    }

                    break;
                  }
                }
              }
            }
            elseif (!empty($filters['score_complete'])) {
              // Filter for entries with ALL scores complete in this round.
              $complete = FALSE;

              if (!empty($data['judging']['rounds'][$filters['round_id']]['scores'])) {
                $complete = TRUE;
                foreach ($data['judging']['rounds'][$filters['round_id']]['scores'] as $score) {
                  foreach ($score->criteria as $val) {
                    if ($val === NULL) {
                      $complete = FALSE;
                      break 2;
                    }
                  }
                }
              }

              if ($complete) {
                $filtered[] = $row->ceid;
              }
            }
            elseif (!empty($filters['min_score'])) {
              // Filter for entry having minimum score.
              if (isset($data['judging']['rounds'][$filters['round_id']]['computed'])) {
                if ($data['judging']['rounds'][$filters['round_id']]['computed'] >= $filters['min_score']) {
                  $filtered[] = $row->ceid;
                }
              }
            }
            else {
              // All round entries.
              $filtered[] = $row->ceid;
            }

          }
        }
      }
    }

    $filtered = array_values(array_unique($filtered));

    return $filtered;
  }

  /**
   * Get judging admin page links.
   *
   * Used in CompetitionEntryController() and CompetitionEntryJudgingForm().
   *
   * @param string|null $competition
   *   The competition entity ID.
   *   Note: This is not upcast/type-hinted as a CompetitionInterface entity, so
   *   that a single route can be used with or without {competition} route param
   *   and be a local task tab.
   * @param string $callback
   *   Callback type: 'setup', 'round-*' label or queue label.
   *
   * @return array
   *   Array of links.
   */
  public function getNavLinks($competition, $callback) {

    $judging = $competition->getJudging();
    $round_type = (!empty($judging->active_round) ? $judging->rounds[$judging->active_round]['round_type'] : NULL);

    $links = [];

    // Setup.
    if ($this->currentUser->hasPermission('administer competition judging setup')) {
      $url = Url::fromRoute('entity.competition_entry.judging', [
        'competition' => $competition->id(),
        'callback' => 'setup',
      ]);

      $links['setup'] = [
        'title' => $this->t('Setup'),
        'url' => $url,
      ];
    }

    // Assignments (current user) - only applicable to scored round types.
    if (!empty($judging->active_round) && in_array($round_type, ['pass_fail', 'criteria'])) {
      $count = count($this->filterJudgingEntries($competition->id(), [
        'round_id' => $judging->active_round,
        'judge_uid' => $this->currentUser->id(),
      ]));
      $url = Url::fromRoute('entity.competition_entry.judging', [
        'competition' => $competition->id(),
        'callback' => 'assignments',
      ]);

      if ($count > 0) {
        $links['assignments'] = [
          'title' => $this->t('My Assignments <sup>[@count]</sup>', [
            '@count' => $count,
          ]),
          'url' => $url,
        ];
      }
    }

    // Round N (all entries).
    if ($this->currentUser->hasPermission('administer competition judging')) {
      // Keeping this in the loop for now, possibly with an eye towards
      // exposing ALL rounds at once to admin users.
      foreach ($judging->rounds as $round => $meta) {
        if ($round == $judging->active_round) {
          $count = count($this->filterJudgingEntries($competition->id(), [
            'round_id' => $round,
          ]));
          $url = Url::fromRoute('entity.competition_entry.judging', [
            'competition' => $competition->id(),
            'callback' => 'round-' . $round,
          ]);

          $links['round_' . $round] = [
            'title' => $this->t('Round @round <sup>[@count]</sup>', [
              '@round' => $round,
              '@count' => $count,
            ]),
            'url' => $url,
          ];
        }
      }
    }

    // Queues.
    if ($this->currentUser->hasPermission('administer competition judging')) {
      foreach ($judging->queues as $queue => $enabled) {
        if (!$enabled) {
          continue;
        }

        $count = count($this->filterJudgingEntries($competition->id(), [
          'queue' => $queue,
        ]));
        $url = Url::fromRoute('entity.competition_entry.judging', [
          'competition' => $competition->id(),
          'callback' => $queue,
        ]);

        $links[$queue] = [
          'title' => $this->t('@title <sup>[@count]</sup>', [
            '@title' => $competition->getJudgingQueueLabel($queue),
            '@count' => $count,
          ]),
          'url' => $url,
        ];
      }
    }

    foreach (array_keys($links) as $key) {
      if ($callback == $key || str_replace('-', '_', $callback) == $key) {
        $links[$key]['attributes']['class'] = ['is-active'];
      }
    }

    return $links;
  }

  /**
   * Finalize scores for all given entries.
   *
   * This kicks off a batch process.
   *
   * @param int $round_id
   *   The round ID.
   * @param array $entry_ids
   *   Entry IDs to process. Calling code should ensure that relevant scores
   *   (either for this judge, or all, according to $judge_uid) are complete
   *   on each entry. However, the batch processor does check if the scores are
   *   complete, and will skip finalizing any that are incomplete (and list
   *   such in an error message).
   * @param int|null $judge_uid
   *   The uid of judge for whom to finalize scores; NULL indicates an admin
   *   is running finalization and all scores on the given entries are to be
   *   finalized.
   */
  public function finalizeScores($round_id, array $entry_ids, $judge_uid) {

    $batch = [
      'title' => $this->t("Finalizing scores..."),
      'operations' => [
        [
          // Callback.
          [static::class, 'finalizeScoresBatchProcess'],
          // Arguments to pass to callback.
          [
            $round_id,
            $entry_ids,
            $judge_uid,
          ],
        ],
      ],
      'finished' => [static::class, 'finalizeScoresBatchFinished'],
    ];

    batch_set($batch);

    // TODO: if called from form submit handler, form api calls batch_process().
    // Can we call it conditionally?
    //
    // Redirect to Round N tab (admins) or Assignments tab (admins).
    // return batch_process(Url::fromRoute(...));.
  }

  /**
   * Finalize scores batch processor.
   *
   * @param int $round_id
   *   The round ID.
   * @param array $entry_ids_all
   *   Entry IDs to process. Calling code should ensure that relevant scores
   *   (either for this judge, or all, according to $judge_uid) are complete
   *   on each entry. However, this method does check if the scores are
   *   complete, and will skip finalizing any that are incomplete (and list
   *   such in an error message).
   * @param int|null $judge_uid
   *   The uid of judge for whom to finalize scores; NULL indicates an admin
   *   is running finalization and all scores on the given entries are to be
   *   finalized.
   * @param array $context
   *   Key 'sandbox' contains values that persist through all calls to this op.
   *   Key 'results' contains values to pass to the batch-finished function.
   */
  public static function finalizeScoresBatchProcess($round_id, array $entry_ids_all, $judge_uid, array &$context) {

    // Set batch init values.
    if (empty($context['sandbox'])) {

      $context['results']['round_id'] = $round_id;

      $context['sandbox']['current_uid'] = \Drupal::currentUser()->id();

      if (empty($judge_uid)) {
        $context['sandbox']['judge_names'] = [];
      }

      // Counter of entries processed currently.
      $context['results']['progress'] = 0;

      // Total number of entries to process.
      $context['sandbox']['total'] = count($entry_ids_all);

      // Count of entries for which scores are successfully finalized.
      $context['results']['count_finalized'] = 0;

      // Count of entries for which scores were already finalized.
      $context['results']['count_already'] = 0;

      // Log entries with incomplete scores (should be none; this is backup).
      $context['results']['incomplete_entries'] = [];

    }

    $storage_user = \Drupal::entityTypeManager()->getStorage('user');

    // Get the number of entities to load per batch cycle.
    $per = \Drupal::config('competition.settings')->get('batch_size');

    // Split the total entries list into a smaller entries list,
    // starting at the current progress position.
    $entry_ids = array_slice($entry_ids_all, $context['results']['progress'], $per);
    $entries = \Drupal::entityTypeManager()->getStorage('competition_entry')->loadMultiple($entry_ids);

    /** @var \Drupal\competition\CompetitionEntryInterface $entry */
    foreach ($entries as $entry) {

      // Score completion should be verified by calling code. However, to avoid
      // creating bad data, do not finalize any incomplete score.
      $complete = (!empty($judge_uid) ?
        $entry->hasJudgeScore($round_id, $judge_uid) :
        $entry->hasAllJudgeScores($round_id)
      );

      if (!$complete) {
        $context['results']['incomplete_entries'][] = $entry->id();
      }
      else {
        // If score(s) verified as complete, now mark finalized, if not already.
        $data = $entry->getData();
        $logs = [];
        $updated = FALSE;

        foreach ($data['judging']['rounds'][$round_id]['scores'] as &$score) {

          if (!empty($judge_uid) && $score->uid != $judge_uid) {
            continue;
          }

          if (!$score->finalized) {
            $score->finalized = TRUE;
            $updated = TRUE;

            // Log the action.
            if (!empty($judge_uid) && $context['sandbox']['current_uid'] == $judge_uid) {
              $logs[] = [
                'uid' => $context['sandbox']['current_uid'],
                'round_id' => $round_id,
                'message' => "Finalized score for entry @ceid in Round @round_id.",
                'message_args' => [
                  '@ceid' => $entry->id(),
                  '@round_id' => $round_id,
                ],
              ];
            }
            else {
              if (empty($context['sandbox']['judge_names'][$score->uid])) {
                $context['sandbox']['judge_names'][$score->uid] = $storage_user->load($score->uid)->getAccountName();
              }

              $logs[] = [
                'uid' => $context['sandbox']['current_uid'],
                'round_id' => $round_id,
                'message' => "Finalized score by @name for entry @ceid in Round @round_id.",
                'message_args' => [
                  '@name' => $context['sandbox']['judge_names'][$score->uid],
                  '@ceid' => $entry->id(),
                  '@round_id' => $round_id,
                ],
              ];
            }
          }

        }

        if ($updated) {
          // Save updates.
          $entry->setData($data);
          $entry->save();

          $entry->addJudgingLogMultiple($logs);

          $context['results']['count_finalized']++;
        }
        else {
          // This judge's score, or all scores, were already finalized.
          $context['results']['count_already']++;
        }
      }

      $context['results']['progress']++;

    }

    // Update progress.
    $context['finished'] = $context['results']['progress'] / $context['sandbox']['total'];

  }

  /**
   * Finalize scoring batch completion handler.
   *
   * @param bool $success
   *   TRUE if no PHP fatals.
   * @param array $results
   *   The $context['results'] array built during operation callbacks.
   * @param array $operations
   *   Batch API operations.
   */
  public static function finalizeScoresBatchFinished($success, array $results, array $operations) {

    if ($success) {
      $translation = \Drupal::translation();

      drupal_set_message($translation->formatPlural(
        $results['count_finalized'],
        "Finalized scores for 1 entry in Round @round.",
        "Finalized scores for @count entries in Round @round.", [
          '@round' => $results['round_id'],
        ]
      ));

      if ($results['count_already'] > 0) {
        drupal_set_message($translation->formatPlural(
          $results['count_already'],
          "(Scores for 1 entry were already finalized.)",
          "(Scores for @count entries were already finalized.)"
        ));
      }

      if (count($results['incomplete_entries']) > 0) {
        drupal_set_message(t("Unexpected error: could not finalize scores for the following entries because they were incomplete:<br/>@ids", [
          '@ids' => implode(", ", $results['incomplete_entries']),
        ]), 'error');
      }

    }
    else {
      drupal_set_message(t('An error occurred while finalizing scores.'), 'error');
    }

  }

}
