<?php

namespace Drupal\competition\Entity;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Component\Render\FormattableMarkup;

/**
 * Provides a trait for competition judging.
 */
trait CompetitionEntryJudgingTrait {

  use StringTranslationTrait;

  /**
   * Judging - assign the given judges to this entry for this round.
   *
   * @param int $round_id
   *   Round ID.
   * @param array $judge_uids
   *   ALL judge user IDs that should be assigned to this entry in this round.
   * @param bool $replace_existing
   *   If TRUE, all existing judge assignments/scores in this round will be
   *   removed - replaced by assignments to given judges and empty scores.
   *   If FALSE, then any judges in $judge_uids already assigned will remain
   *   assigned and their scores intact. Judges not in $judge_uids will be
   *   removed; judges in $judge_uids but not assigned yet will be assigned.
   *   Any "unassignments" will be logged only if not replacing all existing.
   */
  public function assignJudges($round_id, array $judge_uids, $replace_existing = FALSE) {
    // Get the criteria configured for this round.
    // Cache because it's expected to call this method on a bunch of entries
    // at once.
    static $num_criteria = NULL;
    if (!isset($num_criteria)) {
      $competition = $this->getCompetition();
      $judging = $competition->getJudging();
      $num_criteria = count($judging->rounds[$round_id]['weighted_criteria']);
    }

    $to_assign = [];
    $to_unassign = [];

    if ($replace_existing) {

      // All existing assignments are removed, and all provided judges are
      // to be assigned.
      $to_assign = $judge_uids;

    }
    else {

      // Check current assignments to determine which judges to unassign and
      // newly assign.
      $current = [];
      $current_indexes = [];

      $data = $this->getData();
      if (!empty($data['judging']['rounds'][$round_id]['scores'])) {
        foreach ($data['judging']['rounds'][$round_id]['scores'] as $i => $score) {
          $current[] = (int) $score->uid;
          $current_indexes[(int) $score->uid] = $i;
        }
      }

      // array_diff(): "...returns the values in array1 that are not present in
      // any of the other arrays.".
      $to_unassign = array_diff($current, $judge_uids);
      $to_assign = array_diff($judge_uids, $current);

    }

    $round_data = [
      'computed' => NULL,
      'scores' => [],
    ];

    $logs_assign = [];

    // Hey entity caching, you're awesome.
    /** @var \Drupal\user\Entity\User[] $judges */
    $judges = \Drupal::entityTypeManager()->getStorage('user')->loadMultiple(array_merge($to_assign, $to_unassign));

    // Collect assignments.
    $scores_add = [];

    if (!empty($to_assign)) {

      $criteria_empty = [];
      for ($i = 0; $i < $num_criteria; $i++) {
        $criteria_empty['c' . $i] = NULL;
      }

      foreach ($to_assign as $uid) {
        $scores_add[] = (object) [
          'uid' => $uid,
          'finalized' => FALSE,
          'criteria' => $criteria_empty,
        ];

        // Using $replace_existing to assume system vs. user action is meh.
        $logs_assign[] = [
          'uid' => ($replace_existing ? NULL : \Drupal::currentUser()->id()),
          'round_id' => $round_id,
          'message' => "Entry @ceid assigned to @name for scoring in Round @round_id.",
          'message_args' => [
            '@ceid' => $this->id(),
            '@name' => $judges[$uid]->getUsername(),
            '@round_id' => $round_id,
          ],
        ];
      }
    }

    // If replacing all existing assignments/scores, just overwrite with the
    // assignments to add.
    // Since none have scores yet, the computed average remains empty.
    if ($replace_existing) {
      $round_data['scores'] = $scores_add;
    }

    // If not replacing all existing assignments/scores...
    $logs_unassign = [];
    if (!$replace_existing) {

      // Start with any existing data.
      $data = $this->getData();
      if (!empty($data['judging']['rounds'][$round_id])) {

        $round_data = $data['judging']['rounds'][$round_id];

        // Do unassignments.
        if (!empty($to_unassign)) {
          foreach ($to_unassign as $uid) {

            $had_score = FALSE;
            $score = $round_data['scores'][$current_indexes[$uid]];
            foreach ($score->criteria as $val) {
              if ($val !== NULL) {
                $had_score = TRUE;
                break;
              }
            }

            unset($round_data['scores'][$current_indexes[$uid]]);

            // Log manual unassignments.
            $logs_unassign[] = [
              'uid' => \Drupal::currentUser()->id(),
              'round_id' => $round_id,
              'message' => "Entry @ceid no longer assigned to @name for scoring in Round @round_id. " . ($had_score ? "(Judge had scored entry.)" : "(Judge had not scored entry yet.)"),
              'message_args' => [
                '@ceid' => $this->id(),
                '@name' => $judges[$uid]->getUsername(),
                '@round_id' => $round_id,
              ],
            ];
          }
        }

      }

      // Add new assignments.
      if (!empty($scores_add)) {
        $round_data['scores'] = array_merge($round_data['scores'], $scores_add);
      }

    }

    // Save assignments.
    $data = $this->getData();
    $data['judging']['rounds'][$round_id] = $round_data;
    $this->setData($data);

    // Post-save hook writes to assignment index table.
    // @see ::postSave()
    $this->save();

    // If we didn't just set all new assignments with empty scores, we need to
    // update score average.
    // (Call this after save() since it pulls $entry->data again.)
    if (!$replace_existing) {
      $this->updateAverageScore($round_id);
    }

    // Save logs.
    if (!empty($logs_assign) || !empty($logs_unassign)) {
      // Log unassignments before assignments; feels more logical.
      $this->addJudgingLogMultiple(array_merge($logs_unassign, $logs_assign));
    }
  }

  /**
   * Retrieve all judges assigned to an entry in a given round, if any.
   *
   * @param int $round_id
   *   Round ID.
   * @param bool $load_entities
   *   Should entities be loaded.
   *
   * @return array|\Drupal\user\UserInterface[]
   *   If $load_entities, array of User entities; else array of user entity IDs
   */
  public function getAssignedJudges($round_id, $load_entities = FALSE) {
    $uids = [];

    $data = $this->getData();

    if (!empty($data['judging']['rounds'][$round_id]['scores'])) {
      foreach ($data['judging']['rounds'][$round_id]['scores'] as $score) {
        $uids[] = $score->uid;
      }
    }

    return !empty($uids) && $load_entities ? \Drupal::entityTypeManager()->getStorage('user')->loadMultiple($uids) : $uids;
  }

  /**
   * Check if a judge is assigned to this entry in given round.
   *
   * @param int $judge_uid
   *   Judge user ID.
   * @param int $round_id
   *   Round ID.
   *
   * @return bool
   *   Is judge assigned.
   */
  public function isJudgeAssigned($judge_uid, $round_id) {
    return in_array($judge_uid, $this->getAssignedJudges($round_id));
  }

  /**
   * Judging - save a judge's scores for a given round.
   *
   * This also recalculates the averaged score across all judges, weighted
   * according to criteria settings, and saves it in 'computed' property.
   *
   * @param int $round_id
   *   Round ID.
   * @param int $judge_uid
   *   Judge user ID.
   * @param array $score_input
   *   Array of score values - keys are criteria (i.e. 'c0', 'c1', etc.) and
   *   values are this judge's submitted points value (out of the number of
   *   possible points - 'criterion_options' value in judging settings).
   *   If judge didn't submit a score for a criterion, its value should be NULL.
   * @param bool $finalized
   *   Whether judge has marked these score values as finalized.
   *
   * @throws \InvalidArgumentException
   *   The $scores array must contain a value for each criterion defined for
   *   the round.
   *
   * @return bool
   *   Whether the input score was saved successfully. (This should only be
   *   FALSE if attempting to save over a score that was previously stored as
   *   finalized.)
   */
  public function setJudgeScore($round_id, $judge_uid, array $score_input, $finalized) {

    $data = $this->getData();

    $round_data = [
      'computed' => NULL,
      'scores' => [],
    ];

    if (!empty($data['judging']['rounds'][$round_id])) {
      $round_data = $data['judging']['rounds'][$round_id];
    }

    // Check for existing score by this judge.
    $existing_score = NULL;
    $score_index = NULL;
    if (!empty($round_data['scores'])) {
      foreach ($round_data['scores'] as $i => $score) {
        if ($score->uid == $judge_uid) {
          $existing_score = $score;
          $score_index = $i;
          break;
        }
      }
    }

    // If existing score was marked 'finalized', score may not be updated -
    // early return FALSE.
    if (!empty($existing_score) && $existing_score->finalized) {
      drupal_set_message(t("This score cannot be updated because it has already been finalized."), 'error');
      return FALSE;
    }

    // Get criteria config.
    $judging = $this->getCompetition()->getJudging();
    $round_type = $judging->rounds[$round_id]['round_type'];
    $criteria = $judging->rounds[$round_id]['weighted_criteria'];
    $points_max = (int) $judging->rounds[$round_id]['criterion_options'];

    // The scores (for multiple criteria) are expected to be submitted all
    // together via a single form. Therefore, $score_input should be passed
    // containing values (even if NULL) for all criteria - replacing all
    // existing score values saved for this judge.
    for ($i = 0; $i < count($criteria); $i++) {
      if (!array_key_exists('c' . $i, $score_input)) {
        throw new \InvalidArgumentException(sprintf('Argument $scores_input is missing one or more score criterion values - Round %d has %d criteria. Existing criteria: %s', $round_id, count($criteria)));
      }
    }

    // Since we have values for all criteria, we can fully overwrite any
    // existing score.
    $score = (object) [
      'uid' => $judge_uid,
      'finalized' => $finalized,
      'criteria' => [],
    ];

    // Convert submitted point values into weighted points per criterion.
    // Note: Pass/Fail rounds are scored as single criterion, either 1/1 (pass)
    // or 0/1 (fail). Thus the weighted score becomes 100 or 0.
    /*
     * Score structure example (1 judge, 3 criteria):
     *
     *      Points | Percent | Weight | Weighted Points
     *      ------   -------   ------   ---------------
     * c0 |  3/4   =   75%      30%      .75 * 30 = 22.5
     * c1 |  1/4   =   25%      20%      .25 * 20 =  5
     * c2 |  2/4   =   50%      50%      .50 * 50 = 25
     *                                  ---------------
     *          Overall weighted score =  52.5 / 100
     */

    // Each criterion has a weight of 1 - 100
    // $criteria is $label => $weight - re-key for ease of accessing weights.
    $criteria_weights = [];
    foreach (array_values($criteria) as $i => $weight) {
      $criteria_weights['c' . $i] = (int) $weight;
    }

    $pass = NULL;

    foreach ($score_input as $key => $points) {
      // Note that 0 is a valid score. If anything other than a numeric value
      // was submitted, set to NULL to indicate no score submitted for this
      // criterion.
      if (is_numeric($points)) {
        $percent = ((int) $points) / $points_max;
        $weighted_points = $percent * $criteria_weights[$key];
        $score->criteria[$key] = floatval($weighted_points);

        if ($round_type == 'pass_fail' && $key == 'c0') {
          // Pass/Fail: 1 => Pass, 0 => Fail.
          $pass = ($points > 0);
        }
      }
      else {
        $score->criteria[$key] = NULL;
      }
    }

    if (isset($score_index)) {
      // Replace this judge's existing score.
      $round_data['scores'][$score_index] = $score;
    }
    else {
      // Add this judge's score.
      $round_data['scores'][] = $score;
    }
    unset($score, $weighted_points);

    // Save new score data. We need to call save() here,
    // since average func will pull $entry->data again.
    $data = $this->getData();
    $data['judging']['rounds'][$round_id] = $round_data;
    $this->setData($data);
    $this->save();

    // Recalculate average score across all judges.
    $this->updateAverageScore($round_id);

    // Log scoring and finalization actions.
    $logs = [];

    $message = NULL;
    switch ($round_type) {
      case 'pass_fail':
        if ($pass !== NULL) {
          $message = ($pass ? "Passed" : "Failed") . " entry @ceid in Round @round_id.";
        }
        break;

      case 'criteria':
        $message = "Scored entry @ceid in Round @round_id.";
        break;
    }

    if (!empty($message)) {
      $logs[] = [
        'uid' => $judge_uid,
        'round_id' => $round_id,
        'message' => $message,
        'message_args' => [
          '@ceid' => $this->id(),
          '@round_id' => $round_id,
        ],
      ];

      if ($finalized) {
        $logs[] = [
          'uid' => $judge_uid,
          'round_id' => $round_id,
          'message' => "Finalized score for entry @ceid in Round @round_id.",
          'message_args' => [
            '@ceid' => $this->id(),
            '@round_id' => $round_id,
          ],
        ];
      }

      $this->addJudgingLogMultiple($logs);
    }

    return TRUE;
  }

  /**
   * Mark a judge's score as finalized (or not).
   *
   * @param int $round_id
   *   Round ID.
   * @param int $judge_uid
   *   Judge user ID.
   * @param bool $finalized
   *   Whether to set this score to finalized or unfinalized. If setting to
   *   finalized, this will ensure that the score is complete (values submitted
   *   for all criteria) first.
   *
   * @throws \InvalidArgumentException
   *   If the entry is not in given round, or judge is not assigned.
   *
   * @return bool
   *   Whether the score finalized status was successfully updated. If
   *   $finalized was TRUE and the score was not complete, this will return
   *   FALSE.
   */
  public function setJudgeScoreFinalized($round_id, $judge_uid, $finalized = TRUE) {

    $round_id = (int) $round_id;
    $judge_uid = (int) $judge_uid;

    $data = $this->getData();

    if (empty($data['judging']['rounds'][$round_id])) {
      throw new \InvalidArgumentException("This competition entry is not in the given round: " . $round_id);
    }

    $round_data = $data['judging']['rounds'][$round_id];

    // Find this judge's score.
    $score = NULL;
    $score_index = NULL;
    if (!empty($round_data['scores'])) {
      foreach ($round_data['scores'] as $i => $s) {
        if ($s->uid == $judge_uid) {
          $score = $s;
          $score_index = $i;
          break;
        }
      }
    }

    if (empty($score)) {
      throw new \InvalidArgumentException("This competition entry is not assigned in Round " . $round_id . " to the given judge user: " . $judge_uid);
    }

    // If attempting to set finalized but score is not complete, return FALSE.
    if ($finalized && !$score->finalized) {

      $complete = TRUE;
      foreach ($score->criteria as $val) {
        if ($val === NULL) {
          $complete = FALSE;
        }
      }

      if (!$complete) {
        return FALSE;
      }

    }

    $score->finalized = $finalized;

    // Save new score data.
    $data['judging']['rounds'][$round_id][$score_index] = $score;

    $this->setData($data);
    $this->save();

    // Log the action.
    $uid = \Drupal::currentUser()->id();

    if ($uid == $judge_uid) {
      $this->addJudgingLog(
        $uid,
        $round_id,
        ($finalized ? "Finalized" : "Un-finalized") . " score for entry @ceid in Round @round_id.",
        [
          '@ceid' => $this->id(),
          '@round_id' => $round_id,
        ]
      );
    }
    else {
      $judge = \Drupal::entityTypeManager()->getStorage('user')->load($judge_uid);
      $this->addJudgingLog(
        $uid,
        $round_id,
        ($finalized ? "Finalized" : "Un-finalized") . " score by @name for entry @ceid in Round @round_id.",
        [
          '@name' => $judge->getAccountName(),
          '@ceid' => $this->id(),
          '@round_id' => $round_id,
        ]
      );
    }

    return TRUE;
  }

  /**
   * Recalculate and store the average of this entry's scores in a round.
   *
   * @param int $round_id
   *   Round ID.
   */
  public function updateAverageScore($round_id) {

    $data = $this->getData();

    if (!empty($data['judging']['rounds'][$round_id]['scores'])) {
      $round_data = $data['judging']['rounds'][$round_id];

      // Collect weighted score (total points / 100) for each judge...
      $overall_scores = [];

      foreach ($round_data['scores'] as $score) {
        // If any criterion score is missing, the overall score is incomplete.
        $complete = TRUE;
        foreach ($score->criteria as $weighted_points) {
          if ($weighted_points === NULL) {
            $complete = FALSE;
            break;
          }
        }

        // Incomplete scores cannot be accurately included in the average.
        if ($complete) {
          $overall_scores[$score->uid] = array_sum($score->criteria);
        }
      }

      // Store average of all complete scores submitted so far (rounded to
      // 2 decimal places).
      if (!empty($overall_scores)) {
        $round_data['computed'] = round(array_sum($overall_scores) / count($overall_scores), 2);
      }
      else {
        $round_data['computed'] = NULL;
      }

      $data['judging']['rounds'][$round_id] = $round_data;
      $this->setData($data);
      $this->save();
    }

  }

  /**
   * Recalculate the average of this entry's scores in a round.
   *
   * @param int $round_id
   *   Round ID.
   *
   * @return float
   *   Calculated average score.
   */
  public function getAverageScore($round_id) {

    $data = $this->getData();

    if (isset($data['judging']['rounds'][$round_id]['computed'])) {
      return $data['judging']['rounds'][$round_id]['computed'];
    }

    return 0;

  }

  /**
   * Retrieve a judge's score for the given round on this entry, if any.
   *
   * @param int $round_id
   *   Round ID.
   * @param int $judge_uid
   *   Judge user ID.
   *
   * @return object|null
   *   If this judge is assigned to this entry in this round, the $score object
   *   as stored in the entry data, containing:
   *     $score->uid - int - this judge's uid
   *     $score->finalized - bool - whether judge marked this score final
   *     $score->criteria - array - weighted scores for each criterion; keys are
   *       'c0', 'c1', etc., values are floats of weighted points or NULL if
   *       judge hasn't submitted a value yet)
   *   Extra properties, added here:
   *     $score->display - array - "original" scores for each criterion; keys
   *     are 'c0', 'c1', etc., values are the score number as displayed in the
   *     dropdown in the judging form (or empty string if judge hasn't submitted
   *     a value yet)
   *
   * @see ::setJudgeScores()
   */
  public function getJudgeScore($round_id, $judge_uid) {

    $score = NULL;

    $data = $this->getData();

    if (!empty($data['judging']['rounds'][$round_id]['scores'])) {
      foreach ($data['judging']['rounds'][$round_id]['scores'] as $s) {
        if ($s->uid == $judge_uid) {
          $score = $s;
          break;
        }
      }

      if (!empty($score)) {
        // Get criteria config.
        $judging = $this->getCompetition()->getJudging();
        $points_max = (int) $judging->rounds[$round_id]['criterion_options'];

        // Each criterion has a weight of 1 - 100
        // Criteria is stored is $label => $weight - re-key for ease of
        // accessing weights.
        $criteria_weights = [];
        foreach (array_values($judging->rounds[$round_id]['weighted_criteria']) as $i => $weight) {
          $criteria_weights['c' . $i] = (int) $weight;
        }

        // Compute original selected point values, for $score->display.
        // Note: Pass/Fail rounds are scored as single criterion, either
        // 1/1 (pass) or 0/1 (fail). Thus the weighted score becomes 100 or 0.
        /*
         * Score structure example (1 judge, 3 criteria):
         *
         *      Points | Percent | Weight | Weighted Points
         *      ------   -------   ------   ---------------
         * c0 |  3/4   =   75%      30%      .75 * 30 = 22.5
         * c1 |  1/4   =   25%      20%      .25 * 20 =  5
         * c2 |  2/4   =   50%      50%      .50 * 50 = 25
         *                                  ---------------
         *          Overall weighted score =  52.5 / 100
         */
        $score->display = [];
        foreach ($score->criteria as $key => $weighted_points) {
          if ($weighted_points === NULL) {
            $score->display[$key] = '';
          }
          else {
            $percent = $weighted_points / $criteria_weights[$key];
            $points = $percent * $points_max;

            // This should convert back to very close to the original integer
            // value... but because floats aren't 'precise', it may not be.
            // Round before casting.
            $points = (int) round($points);

            $score->display[$key] = $points;
          }
        }

      }
    }

    return $score;

  }

  /**
   * Check if this entry has a complete score by this judge.
   *
   * Score must be "complete", i.e. judge has submitted a value for each
   * criterion, to return TRUE. Whether the score is marked "finalized" is
   * irrelevant.
   *
   * @param int $round_id
   *   Round ID.
   * @param int $judge_uid
   *   Judge user ID.
   *
   * @return bool
   *   TRUE if entry has a complete score by this judge, FALSE otherwise.
   */
  public function hasJudgeScore($round_id, $judge_uid) {

    $round_id = (int) $round_id;
    $judge_uid = (int) $judge_uid;

    $judge_score = NULL;
    $complete = FALSE;

    $data = $this->getData();
    if (!empty($data['judging']['rounds'][$round_id]['scores'])) {
      foreach ($data['judging']['rounds'][$round_id]['scores'] as $score) {
        if ($score->uid == $judge_uid) {
          $judge_score = $score;
          break;
        }
      }
    }

    if (!empty($judge_score)) {
      $complete = TRUE;
      foreach ($judge_score->criteria as $val) {
        if ($val === NULL) {
          $complete = FALSE;
        }
      }
    }

    return $complete;
  }

  /**
   * Check if this entry has complete scores by all assigned judges in a round.
   *
   * Each score must be "complete", i.e. judge has submitted a value for each
   * criterion, to return TRUE. Whether the score is marked "finalized" is
   * irrelevant.
   *
   * @param int $round_id
   *   Round ID.
   *
   * @return bool
   *   TRUE if entry has complete scores by all judge, FALSE otherwise.
   */
  public function hasAllJudgeScores($round_id) {

    $round_id = (int) $round_id;

    $complete = TRUE;

    $data = $this->getData();
    if (!empty($data['judging']['rounds'][$round_id]['scores'])) {
      foreach ($data['judging']['rounds'][$round_id]['scores'] as $score) {
        foreach ($score->criteria as $val) {
          if ($val === NULL) {
            $complete = FALSE;
            break 2;
          }
        }
      }
    }

    return $complete;
  }

  /**
   * Check if this entry has a complete, finalized score by this judge.
   *
   * Score must be "complete", i.e. judge has submitted a value for each
   * criterion, and score must be marked finalized, to return TRUE.
   *
   * @param int $round_id
   *   Round ID.
   * @param int $judge_uid
   *   Judge user ID.
   *
   * @return bool
   *   TRUE if entry has a complete, finalized score by this judge, FALSE
   *   otherwise.
   */
  public function hasJudgeScoreFinalized($round_id, $judge_uid) {

    $round_id = (int) $round_id;
    $judge_uid = (int) $judge_uid;

    $complete_final = FALSE;

    if ($this->hasJudgeScore($round_id, $judge_uid)) {

      $judge_score = NULL;

      $data = $this->getData();
      foreach ($data['judging']['rounds'][$round_id]['scores'] as $score) {
        if ($score->uid == $judge_uid) {
          $judge_score = $score;
          break;
        }
      }

      // We've already checked that score is complete; now check finalized.
      $complete_final = $judge_score->finalized;
    }

    return $complete_final;

  }

  /**
   * Render score details.
   *
   * Generate a table render array showing score details across all judges
   * assigned to this entry in a given round.
   *
   * @param int $round_id
   *   Round ID.
   * @param array $options
   *   Options for rendering:
   *   'close' - boolean - whether to include link to remove score table from
   *     DOM. Defaults to FALSE if not provided.
   *
   * @return array
   *   Render array of type 'table'
   */
  public function renderScoreDetailsTable($round_id, array $options = []) {

    $build = [
      '#type' => 'table',
      '#attributes' => [
        'class' => ['judging-score-details'],
      ],
      '#header' => [
        $this->t('Judge'),
        $this->t('Criteria'),
        [
          'data' => $this->t('Score'),
          'class' => ['score'],
        ],
        [
          'data' => $this->t('Display'),
          'class' => 'score',
        ],
        $this->t('Finalized'),
      ],
      '#empty' => $this->t('No judges have been assigned to this entry yet.'),
      '#rows' => [],
    ];

    // TODO: use theme hook + template for this?
    $build['#prefix'] = '<div class="judging-score-details-wrap">';
    if (isset($options['close']) && $options['close'] === TRUE) {
      $build['#prefix'] .= $this->t('<span class="close">Close</span>');
    }
    $build['#prefix'] .= '<p class="header">' . $this->t('Round @round_id score details for entry @entry_id', [
      '@round_id' => $round_id,
      '@entry_id' => $this->id(),
    ]) . '</p>';
    $build['#suffix'] = '</div>';

    // Get criteria config.
    $judging = $this->getCompetition()->getJudging();

    // pass_fail or criteria.
    $round_type = $judging->rounds[$round_id]['round_type'];

    $criteria = $judging->rounds[$round_id]['weighted_criteria'];
    $criteria_meta = [];
    $ci = 0;
    foreach ($criteria as $label => $weight) {
      $criteria_meta['c' . $ci] = [
        'weight' => $weight,
        'label_output' => $label . ' (' . $weight . '%)',
      ];
      $ci++;
    }

    $points_max = (int) $judging->rounds[$round_id]['criterion_options'];

    $data = $this->getData();
    if (!empty($data['judging']['rounds'][$round_id]['scores'])) {
      // TODO: is there a way to inject this?
      $storage_user = \Drupal::entityTypeManager()->getStorage('user');

      // Collect weighted score (total points / 100) for each judge.
      $overall_scores = [];

      foreach ($data['judging']['rounds'][$round_id]['scores'] as $score) {

        $overall_scores[$score->uid] = 0;

        $start_judge = TRUE;

        foreach ($criteria_meta as $key => $meta) {
          $row = [
            'no_striping' => TRUE,
            'data' => [],
          ];

          if ($start_judge) {
            $row['class'] = ['judge-div'];
            $start_judge = FALSE;
          }

          // "Judge" column.
          $row['data']['name'] = $storage_user->load($score->uid)->getUsername();

          // "Criteria" column.
          $row['data']['criterion'] = $meta['label_output'];

          // "Score" and "Display" columns.
          $row['data']['score'] = [
            'data' => '',
            'class' => ['score'],
          ];
          $row['data']['display'] = [
            'data' => '',
            'class' => ['score'],
          ];

          $weighted_points = $score->criteria[$key];
          if ($weighted_points === NULL) {
            // Judge has not yet submitted a value for this criterion.
            $row['data']['score']['data'] = '-';
            $row['data']['display']['data'] = $this->t('- None -');

            // If any criterion value is missing, this judge's overall score
            // is incomplete.
            $overall_scores[$score->uid] = NULL;
          }
          else {
            // "Score" column
            // ex: "15 / 20 = 75.00%".
            $weight = $meta['weight'];
            $percent = $weighted_points / $weight;
            $row['data']['score']['data'] = $weighted_points . " / " . $weight;
            $row['data']['score']['data'] .= " = " . round(($percent * 100), 2) . "%";

            // "Display" column
            // ex: "3 / 4" -or- "Pass" or "Fail".
            $points = $percent * $points_max;
            // This should convert back to very close to the original integer
            // value... but because floats aren't 'precise', it may not be.
            // Round before casting.
            $points = (int) round($points);

            if ($round_type == 'criteria') {
              $row['data']['display']['data'] = $points . " / " . $points_max;
            }
            elseif ($round_type == 'pass_fail') {
              $row['data']['display']['data'] = ($points == 0 ? $this->t("Fail") : $this->t("Pass"));
            }

            // Add criterion value to this judge's overall score.
            if ($overall_scores[$score->uid] !== NULL) {
              $overall_scores[$score->uid] += $weighted_points;
            }
          }

          // "Finalized" column.
          $row['data']['finalized'] = [
            'data' => ($score->finalized ? $this->t('Yes') : $this->t('No')),
          ];

          $build['#rows'][] = $row;
        }

        // Remove incomplete scores so they are excluded from average.
        if ($overall_scores[$score->uid] === NULL) {
          unset($overall_scores[$score->uid]);
        }

      }

      // Overall average score row.
      $row = [
        'no_striping' => TRUE,
        'data' => [],
        'class' => ['average'],
      ];

      $row['data']['average'] = [
        'data' => '',
        'class' => ['average'],
        'colspan' => 3,
      ];

      if (empty($overall_scores)) {
        $row['data']['average']['data'] = $this->t('Average: -');
      }
      else {
        $average = round(array_sum($overall_scores) / count($overall_scores), 2);
        $row['data']['average']['data'] = $this->t('Average: (@scores) / @overall_scores = @average%', [
          '@scores' => implode(' + ', $overall_scores),
          '@overall_scores' => count($overall_scores),
          '@average' => $average,
        ]);
      }

      $row['data']['display'] = '';
      $row['data']['finalized'] = '';

      $build['#rows'][] = $row;
    }

    return $build;
  }

  /**
   * Add single message to entry's judging log.
   *
   * @param int|null $uid
   *   User ID that performed the entry log action.
   *   NULL if performed by the system.
   * @param int|null $round_id
   *   The judging round to which this action is associated, or during which
   *   it occurred. If NULL, the competition's active round will be used.
   *   Note: this is not currently displayed anywhere; it's used as metadata
   *   when deleting log messages along with round assignments/scores.
   * @param string $message
   *   The untranslated log message. May contain placeholders as supported by
   *   translation functions (t(), etc.).
   * @param array $message_args
   *   (Optional) Values to be replaced in the message, when output by a
   *   translation function (t(), etc.).
   */
  public function addJudgingLog($uid, $round_id, $message, array $message_args = []) {

    $log = [
      'uid' => $uid,
      'round_id' => $round_id,
      'message' => $message,
      'message_args' => $message_args,
    ];

    $this->addJudgingLogMultiple([$log]);

  }

  /**
   * Add multiple messages to entry's judging log.
   *
   * For efficiency, this should be used instead of addJudgingLog() whenever
   * multiple messages need to be logged at a single point in the code - as this
   * calls entry save() method only once.
   *
   * @param array $logs
   *   Array of associative arrays, each one defining a single log message,
   *   with keys pointing to corresponding addJudgingLog() method params:
   *     'uid'
   *     'round_id'
   *     'message'
   *     'message_args' (optional)
   *
   * @throws \InvalidArgumentException
   *   Thrown if $logs does not contain properly structured arrays to define
   *   messages.
   */
  public function addJudgingLogMultiple(array $logs) {

    // Validate $logs.
    foreach ($logs as $log) {
      foreach (['uid', 'round_id', 'message'] as $key) {
        if (!array_key_exists($key, $log)) {
          throw new \InvalidArgumentException('Each array in $logs parameter must include at least the keys "uid" and "message".');
        }
      }
    }

    // Fill in timestamp, message_args, default round_id.
    $judging = $this->getCompetition()->getJudging();
    foreach ($logs as &$log) {
      $log['timestamp'] = REQUEST_TIME;

      if (empty($log['message_args'])) {
        $log['message_args'] = [];
      }

      // If round ID not given, use the active round, as that's when the action
      // occurred.
      // While this codebase does not currently do so, it is possible to log a
      // message while no round is active - in which case there's no choice but
      // to store NULL. Such messages would not be deleted when deleting all
      // data for a round (via judging workflow form).
      if ($log['round_id'] === NULL) {
        $log['round_id'] = (!empty($judging->active_round) ? $judging->active_round : NULL);
      }

      if (is_numeric($log['round_id'])) {
        $log['round_id'] = (int) $log['round_id'];
      }
    }

    $data = $this->getData();

    if (!isset($data['judging']['log'])) {
      $data['judging']['log'] = [];
    }
    $data['judging']['log'] = array_merge($data['judging']['log'], $logs);

    $this->setData($data);
    $this->save();

  }

  /**
   * Get judging log items.
   *
   * @return array|null
   *   Judging log notes for this entry
   */
  public function getJudgingLog() {

    $data = $this->getData();

    if (!empty($data['judging']['log'])) {

      return $data['judging']['log'];

    }

    return NULL;

  }

  /**
   * Render judging log.
   *
   * @return array|null
   *   Drupal table render array
   */
  public function renderJudgingLog() {

    $logs = $this->getJudgingLog();

    if ($logs) {

      // Log messages are stored in chronological order; reverse it so that
      // actions are displayed in most-recent to least-recent order.
      $logs = array_reverse($logs);

      $rows = [];
      foreach ($logs as $log) {

        $name = '';
        if (!empty($log['uid'])) {
          // Note: `Entity` class provides entityTypeManager() method, but it's
          // protected, so we can't access it in this trait.
          $user = \Drupal::entityTypeManager()->getStorage('user')->load($log['uid']);
          $name = $user->getUsername();
        }
        else {
          // Automated/system actions.
          $name = $this->t('System');
        }

        $rows[] = [
          [
            'data' => $name,
            'class' => ['nowrap'],
          ],
          [
            'data' => date('m/d/Y g:i a', $log['timestamp']),
            'class' => ['nowrap'],
          ],
          !empty($log['message_args']) ? FormattableMarkup::placeholderFormat($log['message'], $log['message_args']) : $log['message'],
        ];

      }

      return [
        '#type' => 'table',
        '#header' => [
          $this->t('Judge'),
          $this->t('Timestamp'),
          $this->t('Note'),
        ],
        '#empty' => $this->t('There are no log messages for this entry yet.'),
        '#rows' => $rows,
      ];

    }

    return NULL;

  }

  /**
   * Does this entry exist in a queue.
   *
   * @param string $queue
   *   Queue name.
   *
   * @return bool
   *   TRUE if entry exists in queue, FALSE if not.
   */
  public function existsInQueue($queue) {

    $data = $this->getData();

    return in_array($queue, $data['judging']['queues']);

  }

  /**
   * Move entry to a judging queue.
   *
   * @param string $queue
   *   Queue name.
   *
   * @return bool
   *   TRUE if entry added to queue; FALSE if entry was already in queue.
   *
   * @throws \InvalidArgumentException
   *   If $queue is not the name of a queue used in this competition.
   */
  public function queueAdd($queue) {

    $competition = $this->getCompetition();
    $judging = $competition->getJudging();

    if (!array_key_exists($queue, $judging->queues)) {

      throw new \InvalidArgumentException('$queue must be the name of a queue used in this competition.');

    }

    $data = $this->getData();
    if (!isset($data['judging']['queues'])) {

      $data['judging']['queues'] = [];

    }

    if (in_array($queue, $data['judging']['queues'])) {

      // Entry is already in this queue.
      return FALSE;

    }

    // Overwrite the queues array to remove all existing queue assignments.
    $data['judging']['queues'] = [$queue];
    $this->setData($data);
    $this->save();

    // Pass NULL to log this action within the active round, since that's when
    // it occurred.
    $queues = \Drupal::config('competition.settings')->get('queues');
    $this->addJudgingLog(\Drupal::currentUser()->id(), NULL, 'Moved entry @ceid to %queue_label list.', [
      '@ceid' => $this->id(),
      '%queue_label' => $queues[$queue],
    ]);

    return TRUE;

  }

  /**
   * Remove entry from a judging queue.
   *
   * @param string $queue
   *   Queue name.
   *
   * @return bool
   *   TRUE if entry removed from queue; FALSE if entry was not in queue to
   *   begin with.
   *
   * @throws \InvalidArgumentException
   *   If $queue is not the name of a queue used in this competition.
   */
  public function queueRemove($queue) {

    $competition = $this->getCompetition();
    $judging = $competition->getJudging();

    if (!array_key_exists($queue, $judging->queues)) {

      throw new \InvalidArgumentException('$queue must be the name of a queue used in this competition.');

    }

    $data = $this->getData();

    if (!in_array($queue, $data['judging']['queues'])) {

      // Entry was not in this queue - cannot remove it.
      return FALSE;

    }

    foreach ($data['judging']['queues'] as $index => $key) {

      if ($key == $queue) {

        unset($data['judging']['queues'][$index]);

        $this->setData($data);
        $this->save();

        // Pass NULL to log this action within the active round, since that's
        // when it occurred.
        $queues = \Drupal::config('competition.settings')->get('queues');
        $this->addJudgingLog(\Drupal::currentUser()->id(), NULL, 'Removed entry @ceid from %queue_label list.', [
          '@ceid' => $this->id(),
          '%queue_label' => $queues[$queue],
        ]);

        return TRUE;

      }

    }

  }

  /**
   * Alter judging.
   *
   * @param array $data
   *   Judging data.
   */
  protected function dataReportingAlterJudging(array &$data) {

    $judging = $this->getCompetition()->getJudging();

    foreach ($data['judging']['rounds'] as $round_id => &$round) {

      // Add different displays of each judge's criteria scores.
      /*
       * Score structure example (1 judge, 3 criteria):
       *
       *      Points | Percent | Weight | Weighted Points
       *      ------   -------   ------   ---------------
       * c0 |  3/4   =   75%      30%      .75 * 30 = 22.5
       * c1 |  1/4   =   25%      20%      .25 * 20 =  5
       * c2 |  2/4   =   50%      50%      .50 * 50 = 25
       *                                  ---------------
       *          Overall weighted score =  52.5 / 100
       */
      if (!empty($round['scores'])) {
        $round_config = $judging->rounds[$round_id];

        $criteria_weights = [];
        $i = 0;
        foreach ($round_config['weighted_criteria'] as $weight) {
          $criteria_weights['c' . $i] = (int) $weight;
          $i++;
        }

        $points_max = (int) $round_config['criterion_options'];

        // Collect weighted score (total points / 100) for each judge, to
        // determine total weighted points.
        // A judge's score must be complete (values submitted for all criteria)
        // to factor into the total.
        $overall_scores = [];

        foreach ($round['scores'] as &$score) {

          $overall_scores[$score->uid] = 0;

          if (!empty($score->criteria)) {
            foreach ($score->criteria as $key => $weighted_points) {

              if ($weighted_points === NULL) {
                $score->criteria[$key] = [
                  'weighted_points' => NULL,
                  'display_weighted_points' => '-',
                  'display_percent' => '-',
                  'display_points' => '-',
                ];

                $overall_scores[$score->uid] = NULL;
              }
              else {
                $percent = $weighted_points / $criteria_weights[$key];
                $points = $percent * $points_max;
                // This should convert back to very close to the original
                // integer value... but because floats aren't 'precise', it may
                // not be. Round before casting.
                $points = (int) round($points);

                $score->criteria[$key] = [
                  // criterion.weighted_points
                  // 22.5.
                  'weighted_points' => $weighted_points,

                  // criterion.display_weighted_points
                  // "22.5 / 30".
                  'display_weighted_points' => $weighted_points . ' / ' . $criteria_weights[$key],

                  // criterion.display_percent
                  // "75%"
                  // TODO: rounding/decimal points?
                  'display_percent' => ($percent * 100) . '%',

                  // criterion.display_points
                  // "3 / 4".
                  'display_points' => $points . ' / ' . $points_max,
                ];

                if ($overall_scores[$score->uid] !== NULL) {
                  $overall_scores[$score->uid] += $weighted_points;
                }

              }

            }
          }

          // score.weighted_points.
          if ($overall_scores[$score->uid] !== NULL) {
            $score->weighted_points = $overall_scores[$score->uid];
          }
          else {
            $score->weighted_points = NULL;

            // Remove incomplete scores from counting towards average.
            unset($overall_scores[$score->uid]);
          }

        }

        if (count($overall_scores) > 0) {
          $round['display_total_weighted_points'] = array_sum($overall_scores) . ' / ' . (count($overall_scores) * 100);
        }
        else {
          $round['display_total_weighted_points'] = '-';
        }
      }

      // We store computed overall average already; add display version.
      if ($round['computed'] !== NULL) {
        $round['display_average'] = number_format(round($round['computed'], 2), 2) . '%';
      }
      else {
        $round['display_average'] = '-';
      }

    }

  }

}
