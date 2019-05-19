<?php

namespace Drupal\sl_stats\Plugin\SLStatsComputer;

use Drupal\sl_stats\SLStatsComputerFull;

/**
 * Provides a basic widget.
 *
 * @SLStatsComputer(
 *   id = "sl_stats_coach",
 *   name = @Translation("SL Stats Coach"),
 *   description = @Translation("SL Stats Coach"),
 * )
 */
class SLStatsCoach extends SLStatsComputerFull {

  protected $teamStatsType = 'sl_stats_coach';
  protected $matchesView = 'sl_stats_matches_computing';
  protected $matchesDisplayId = 'attachment_1';

  public function getTotals() {

  }

  protected function getCoachPositionTermId() {
    $efq = $efq = \Drupal::entityQuery('taxonomy_term');
    $efq->condition('vid', 'sl_person_positions');
    $efq->condition('name', 'treinador');

    $results = $efq->execute();
    if (!empty($results)) {
      return reset($results);
    }
  }


  public function isApplicable($player, $team) {
    if ($player->field_sl_person_position->target_id == $this->getCoachPositionTermId()) {
      return TRUE;
    }

    return FALSE;
  }

  protected function analyzeMatch($match, $player, $team) {

    if ($match->field_sl_match_score_home > $match->field_sl_match_score_away) {
      $result = 'win';
    }
    else if ($match->field_sl_match_score_away > $match->field_sl_match_score_home) {
      $result = 'lost';
    }
    else {
      $result = 'draw';
    }

   if ($team->target_id == $match->field_sl_match_team_away) {
      if ($result == 'win') {
        $result = 'lost';
      }
      else {
        if ($result == 'lost') {
          $result = 'win';
        }
      }
    }

    if (!isset($this->competitions[$match->field_sl_competition][$result])) {
      $this->competitions[$match->field_sl_competition][$result] = 1;
    }
    else {
      $this->competitions[$match->field_sl_competition][$result]++;
    }

    if (!isset($this->total_team[$result])) {
      $this->total_team[$result] = 1;
    }
    else {
      $this->total_team[$result]++;
    }
  }

  function createEntity($entity, $values, $person_id, $team_id, $competition_id = NULL) {

    $entity->set('field_sl_stats_match_wins', !empty($values['win']) ? $values['win'] : 0);
    $entity->set('field_sl_stats_match_lost', !empty($values['lost']) ? $values['lost'] : 0);
    $entity->set('field_sl_stats_match_draws', !empty($values['draw']) ? $values['draw'] : 0);

    $entity->set('field_sl_stats_match_perc_win', !empty($values['win']) ? round($values['win']/$values['matches'], 2) : 0);
    $entity->set('field_sl_stats_match_perc_lost', !empty($values['lost']) ? round($values['lost']/$values['matches'], 2) : 0);
    $entity->set('field_sl_stats_match_perc_draws', !empty($values['draw']) ? round($values['draw']/$values['matches'], 2) : 0);

    $entity->set('field_sl_stats_matches', $values['matches']);
    // $entity->set('field_sl_stats_minutes', $values['minutes']);
    // $entity->set('field_sl_stats_goals', !empty($values['moments']['sl_match_moments_goal']) ? $values['moments']['sl_match_moments_goal'] : 0);
    // $entity->set('field_sl_stats_yellow_cards', !empty($values['moments']['sl_match_moments_yellow_card']) ? $values['moments']['sl_match_moments_yellow_card'] : 0);
    // $entity->set('field_sl_stats_red_cards', !empty($values['moments']['sl_match_moments_red_card']) ? $values['moments']['sl_match_moments_red_card'] : 0);
    $entity->set('field_sl_teams', $team_id);
    $entity->set('field_sl_stats_person', $person_id);

    if (!empty($competition_id)) {
      $entity->set('field_sl_stats_competition', $competition_id);
    }

  }
}