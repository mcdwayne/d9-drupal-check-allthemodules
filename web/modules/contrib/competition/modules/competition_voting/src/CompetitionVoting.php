<?php

namespace Drupal\competition_voting;

use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\competition\CompetitionInterface;
use Drupal\competition\CompetitionEntryInterface;
use Drupal\supercookie\SupercookieManager;
use Drupal\supercookie\SupercookieResponse;

/**
 * Service to warehouse utilities for competition voting.
 */
class CompetitionVoting {

  const VOTING_TABLE = 'competition_entry_voting';

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
  protected $database;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * The supercookie manager service.
   *
   * @var \Drupal\supercookie\SupercookieManager
   */
  protected $supercookieManager;

  /**
   * The supercookie response service.
   *
   * @var \Drupal\supercookie\SupercookieResponse
   */
  protected $supercookieResponse;

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
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Session\AccountProxy $current_user
   *   The current user.
   * @param \Drupal\supercookie\SupercookieManager $supercookie_manager
   *   The SupercookieManager service.
   * @param \Drupal\supercookie\SupercookieResponse $supercookie_response
   *   The SupercookieResponse service.
   * @param Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, Connection $database, AccountProxy $current_user, SupercookieManager $supercookie_manager, SupercookieResponse $supercookie_response, RequestStack $request_stack) {

    $this->entityTypeManager = $entity_type_manager;
    $this->database = $database;
    $this->supercookieManager = $supercookie_manager;
    $this->supercookieResponse = $supercookie_response;
    $this->currentUser = $current_user;
    $this->storageCompetition = $this->entityTypeManager->getStorage('competition');
    $this->storageCompetitionEntry = $this->entityTypeManager->getStorage('competition_entry');
    $this->requestStack = $request_stack;

  }

  /**
   * Get config of all voting rounds on given competition.
   *
   * @param \Drupal\competition\CompetitionInterface $competition
   *   The competition entity.
   *
   * @return array|null
   *   Array of sub-arrays; keys are integer round IDs; sub-arrays are round
   *   config as stored on competition config entity. (NULL if no voting rounds
   *   exist.)
   *
   * @see competition_voting_form_competition_edit_form_alter()
   */
  public function getVotingRounds(CompetitionInterface $competition) {

    $voting_rounds = NULL;

    $judging = $competition->getJudging();
    if (!empty($judging->rounds)) {
      foreach ($judging->rounds as $round_id => $round_config) {
        if ($round_config['round_type'] == 'voting') {
          if (empty($voting_rounds)) {
            $voting_rounds = [];
          }
          $voting_rounds[$round_id] = $round_config;
        }
      }
    }

    return $voting_rounds;

  }

  /**
   * Check if the given round is a voting round in this competition.
   *
   * @param \Drupal\competition\CompetitionInterface $competition
   *   The competition entity.
   * @param int $round_id
   *   The round ID.
   *
   * @throws \InvalidArgumentException
   *   If $round_id is not an int or numeric string.
   *
   * @return bool
   *   TRUE if the competition has this round and it's a voting round, otherwise
   *   FALSE.
   */
  public function isVotingRound(CompetitionInterface $competition, $round_id) {

    if (!is_numeric($round_id)) {
      throw new \InvalidArgumentException("Argument \$round_id should be an integer or string containing an integer.");
    }

    $round_id = (int) $round_id;
    $voting_rounds = $this->getVotingRounds($competition);

    return !empty($voting_rounds[$round_id]);

  }

  /**
   * User access.
   *
   * @param \Drupal\competition\CompetitionEntryInterface $competition_entry
   *   The competition entry.
   *
   * @return bool
   *   True if user can vote on this entry.
   */
  public function access(CompetitionEntryInterface $competition_entry) {

    return $this->currentUser
      ->hasPermission('vote for judged contest ' . $competition_entry->getCompetition()->id() . ' entry');

  }

  /**
   * Is voting allowed?
   *
   * @param \Drupal\competition\CompetitionEntryInterface $competition_entry
   *   The competition entry.
   *
   * @return bool
   *   Whether voting is allowed.
   */
  public function isVoteAllowed(CompetitionEntryInterface $competition_entry) {

    $allowed = FALSE;
    $round = $this->getVotingRound($competition_entry);

    if (!$round) {
      return FALSE;
    }

    $competition = $competition_entry->getCompetition();
    $judging = $competition->getJudging();

    // If this competition is configured to limit votes allowed per user.
    if ($round['votes_allowed']) {

      $interval = $this->getVotingInterval($competition_entry);

      $votes = $this->getVoteCount([
        'source_id' => $this->getUserSourceId(),
        'interval' => $interval,
        'round_id' => $judging->active_round,
      ]);
      // Limited number of votes allowed, across all entries.
      // Check total count of user's votes (within interval).
      // TODO? if total limit > 1, this allows multiple PER entry.
      $allowed = (bool) ($votes < $round['votes_allowed']);

    }
    else {

      $votes = $this->getVoteCount([
        'source_id' => $this->getUserSourceId(),
        'round_id' => $judging->active_round,
      ]);

      // Unlimited votes allowed on all entries.
      // Check count of user's votes on this entry.
      // TODO? this limits to 1 PER entry (within interval).
      if ($votes == 0) {
        $allowed = TRUE;
      }

    }

    return $allowed;

  }

  /**
   * Get voting round data.
   *
   * @param \Drupal\competition\CompetitionEntryInterface $competition_entry
   *   The competition entry.
   *
   * @return array|null
   *   Voting round data or NULL if there is no active voting round.
   */
  public function getVotingRound(CompetitionEntryInterface $competition_entry) {

    $judging = $competition_entry->getCompetition()->getJudging();
    $round = $judging->rounds[$judging->active_round];

    if ($round && $round['round_type'] == 'voting') {

      return $round;

    }

    return NULL;

  }

  /**
   * Get voting round interval.
   *
   * @param \Drupal\competition\CompetitionEntryInterface $competition_entry
   *   The competition entry.
   *
   * @return int
   *   Voting interval.
   */
  public function getVotingInterval(CompetitionEntryInterface $competition_entry) {

    $round = $this->getVotingRound($competition_entry);

    if (!$round) {
      return FALSE;
    }

    return $this->currentUser->isAuthenticated()
      ? $round['votes_allowed_interval_authenticated']
      : $round['votes_allowed_interval_anonymous'];

  }

  /**
   * Add an entry to a voting round.
   *
   * This is the voting round equivalent of assigning judges.
   *
   * @param int $round_id
   *   The ID of a voting round.
   * @param \Drupal\competition\CompetitionEntryInterface $competition_entry
   *   The competition entry.
   *
   * @throws \InvalidArgumentException
   *   If round with given ID does not exist or is not a voting round.
   */
  public function addEntryToRound($round_id, CompetitionEntryInterface $competition_entry) {

    if (!$this->isVotingRound($competition_entry->getCompetition(), $round_id)) {
      throw new \InvalidArgumentException("Argument \$round_id must be the ID of a voting round in this competition.");
    }

    // Initialize the total votes count in entry data.
    // (Details of each vote are only stored in the voting table.)
    $data = $competition_entry->getData();
    $data['judging']['rounds'][$round_id] = [
      'votes' => 0,
    ];
    $competition_entry->setData($data);
    $competition_entry->save();

    // Log the action.
    $competition_entry->addJudgingLog($this->currentUser->id(), $round_id, "Entry @ceid promoted to Round @round_id for voting.", ['@round_id' => $round_id]);

  }

  /**
   * Add given entries to a voting round.
   *
   * This kicks off a batch process (unless there is an issue with judging
   * setup such that assignment cannot proceed).
   *
   * @param string $competition_id
   *   The competition entity ID.
   * @param int $round_id
   *   The round ID.
   * @param array $entry_ids
   *   Entry IDs.
   *
   * @return bool|null|\Symfony\Component\HttpFoundation\RedirectResponse
   *   The result of batch_process() call - a redirect response or NULL - or
   *   FALSE if a setup issue prevented running batch.
   *
   * @throws \InvalidArgumentException
   *   If round with given ID does not exist or is not a voting round.
   */
  public function addEntriesToRound($competition_id, $round_id, array $entry_ids) {

    if (!$this->isVotingRound($this->storageCompetition->load($competition_id), $round_id)) {
      throw new \InvalidArgumentException("Argument \$round_id must be the ID of a voting round in this competition.");
    }

    // (This is unexpected.)
    if (empty($entry_ids)) {
      drupal_set_message(t("There are no entries to be promoted to this round."), 'warning');
      return FALSE;
    }

    $batch = [
      'title' => t("Promoting entries to round..."),
      'operations' => [
        [
          // Callback.
          [static::class, 'addEntriesToRoundBatchProcess'],
          // Arguments to pass to callback.
          [
            $competition_id,
            $round_id,
            $entry_ids,
          ],
        ],
      ],
      'finished' => [static::class, 'addEntriesToRoundBatchFinished'],
    ];

    batch_set($batch);

  }

  /**
   * Batch process function to add entries to voting round.
   *
   * @param string $competition_id
   *   The competition entity ID.
   * @param int $round_id
   *   The integer ID of the round.
   * @param array $entry_ids_all
   *   Array of IDs of all competition entries to be added to this round.
   * @param array $context
   *   Key 'sandbox' contains values that persist through all calls to this op.
   *   Key 'results' contains values to pass to the batch-finished function.
   *
   * @throws \InvalidArgumentException
   *   If round with given ID does not exist or is not a voting round.
   */
  public static function addEntriesToRoundBatchProcess($competition_id, $round_id, array $entry_ids_all, array &$context) {

    /* @var \Drupal\competition_voting\CompetitionVoting $voting */
    $voting = \Drupal::service('competition.voting');

    if (!$voting->isVotingRound($voting->storageCompetition->load($competition_id), $round_id)) {
      throw new \InvalidArgumentException("Argument \$round_id must be the ID of a voting round in this competition.");
    }

    if (empty($context['sandbox'])) {

      $context['results']['round_id'] = $round_id;

      // Counter of entries processed currently.
      $context['results']['count_entries'] = 0;

      // Total number of entries to process.
      $context['sandbox']['total'] = count($entry_ids_all);

    }

    $per = \Drupal::config('competition.settings')->get('batch_size');

    $entry_ids = array_slice($entry_ids_all, $context['results']['count_entries'], $per);
    $entries = $voting->storageCompetitionEntry->loadMultiple($entry_ids);

    /* @var \Drupal\competition\CompetitionEntryInterface $entry */
    foreach ($entries as $entry) {

      $voting->addEntryToRound($round_id, $entry);

      $context['results']['count_entries']++;

    }

    // Update progress.
    $context['finished'] = $context['results']['count_entries'] / $context['sandbox']['total'];
  }

  /**
   * Batch completion handler for adding entries to voting round.
   *
   * @param bool $success
   *   TRUE if no PHP fatals.
   * @param array $results
   *   The $context['results'] array built during operation callbacks.
   * @param array $operations
   *   Batch API operations.
   */
  public static function addEntriesToRoundBatchFinished($success, array $results, array $operations) {
    if ($success) {
      drupal_set_message(\Drupal::translation()->formatPlural(
        $results['count_entries'],
        "Promoted <strong>1</strong> entry to Round @round_id for voting.",
        "Promoted <strong>@count</strong> entries to Round @round_id for voting.",
        [
          '@round_id' => $results['round_id'],
        ]
      ));
    }
    else {
      drupal_set_message(t('An error occurred while adding entries to round.'), 'error');
    }
  }

  /**
   * Get the user source ID.
   *
   * @return string|int
   *   Drupal user ID or Supercookie ID.
   */
  public function getUserSourceId() {

    $source_id = NULL;

    if ($this->currentUser->isAuthenticated()) {

      // If user is authenticated then use their user ID.
      $source_id = $this->currentUser->id();

    }
    else {

      // If user is anonymous then use Supercookie.
      $response = $this->supercookieResponse
        ->getResponse()
        ->getContent();

      $_supercookie = json_decode($response);
      $source_id = $_supercookie->scid;

      // Update cookie expiration.
      $this->supercookieManager->save(REQUEST_TIME);

    }

    return $source_id;

  }

  /**
   * Vote for an entry.
   *
   * @param \Drupal\competition\CompetitionEntryInterface $competition_entry
   *   The competition entry.
   * @param int $round_id
   *   Judging round ID.
   *
   * @result bool
   *   Whether the vote record was saved successfully.
   */
  public function vote(CompetitionEntryInterface $competition_entry, $round_id) {

    if (!$this->isVoteAllowed($competition_entry)) {
      return FALSE;
    }

    $vote = [
      'ceid' => $competition_entry->id(),
      'source_id' => $this->getUserSourceId(),
      'round_id' => $round_id,
      'timestamp' => REQUEST_TIME,
      'source_ip' => $this->requestStack->getCurrentRequest()->getClientIp(),
    ];

    $success = $this->database
      ->insert(CompetitionVoting::VOTING_TABLE)
      ->fields(['ceid', 'source_id', 'round_id', 'timestamp', 'source_ip'])
      ->values($vote)
      ->execute();

    if ($success) {

      // Update the vote count for this entry.
      $data = $competition_entry->getData();

      if (!isset($data['judging']['rounds'][$round_id]['votes'])) {
        $data['judging']['rounds'][$round_id]['votes'] = 0;
      }
      $data['judging']['rounds'][$round_id]['votes']++;

      $competition_entry->setData($data);
      $competition_entry->save();

    }

    return $success;

  }

  /**
   * Get votes.
   *
   * @param array $params
   *   Vote search params with these keys:
   *     ceid, round_id, source_id, type, cycle.
   *
   * @result array
   *   Votes.
   */
  public function getVotes(array $params) {

    // Base query.
    $query = $this->database
      ->select('competition_entry_voting', 'v')
      ->fields('v', [
        'vid',
        'ceid',
        'round_id',
        'source_id',
        'source_ip',
        'timestamp',
      ]);
    $query->join('competition_entry', 'ce', 'ce.ceid = v.ceid');
    $query->addField('ce', 'type');
    $query->addField('ce', 'cycle');

    if (isset($params['ceid'])) {
      $query->condition('v.ceid', $params['ceid']);
    }

    // Round ID.
    if (isset($params['round_id'])) {

      $query->condition('v.round_id', $params['round_id']);

    }

    // Source ID (user or supercookie ID).
    if (isset($params['source_id'])) {

      $source_id = $params['source_id'] ?: $this->getUserSourceId();
      $query->condition('v.source_id', $params['source_id']);

    }

    // Type.
    if (isset($params['type'])) {

      $query->condition('ce.type', $params['type']);

    }

    // Cycle.
    if (isset($params['cycle'])) {

      $query->condition('ce.cycle', $params['cycle']);

    }

    // Interval.
    if (isset($params['interval'])) {

      $query->condition('v.timestamp', (REQUEST_TIME - $params['interval']), '>');

    }

    return $query->execute()->fetchAll();

  }

  /**
   * Get vote count.
   *
   * @param array $params
   *   Vote search params.
   *
   * @result int
   *   Vote count.
   */
  public function getVoteCount(array $params) {

    return count($this->getVotes($params));

  }

  /**
   * Delete vote records according to specified parameters.
   *
   * @param array $params
   *   Parameters to limit which votes to delete. All are optional:
   *   'ceid' - ID of competition entry voted for
   *   'round_id' - ID of judging round in which vote occurred
   *   'source_id' - supercookie ID or Drupal user ID of user who voted.
   *
   * @return int
   *   The number of rows deleted.
   */
  public function deleteVotes(array $params) {

    // Filter to valid param keys.
    $params = array_intersect_key($params, array_flip([
      'ceid',
      'round_id',
      'source_id',
    ]));

    $query = $this->database->delete(static::VOTING_TABLE);

    if (!empty($params)) {
      foreach ($params as $k => $v) {
        $query->condition($k, $v);

      }
    }

    return $query->execute();
  }

}
