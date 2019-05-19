<?php

namespace Drupal\sl_stats\Plugin\SLStatsComputer;

use Drupal\sl_stats\SLStatsComputerFull;

/**
 * Provides a basic widget.
 *
 * @SLStatsComputer(
 *   id = "sl_stats_player",
 *   name = @Translation("SL Stats Player"),
 *   description = @Translation("SL Stats Player"),
 * )
 */
class SLStatsPlayer extends SLStatsComputerFull {

  protected $teamStatsType = 'sl_stats_player';
  protected $matchesView = 'sl_stats_matches_computing';
  protected $matchesDisplayId = 'attachment_1';
  protected $momentsView = 'sl_stats_match_moments_computing';
  protected $momentsDisplayId = 'attachment_1';

  public function getTotals() {

  }

  public function isApplicable($player, $team) {
    if ($this->getTeamStatsType($team) == 'sl_stats_player') {
      return TRUE;
    }

    return FALSE;
  }

  function createEntity($entity, $values, $person_id, $team_id, $competition_id = NULL) {
    $entity->set('field_sl_stats_matches', $values['matches']);
    $entity->set('field_sl_stats_minutes', $values['minutes']);
    $entity->set('field_sl_stats_goals', !empty($values['moments']['sl_match_moments_goal']) ? $values['moments']['sl_match_moments_goal'] : 0);
    $entity->set('field_sl_stats_yellow_cards', !empty($values['moments']['sl_match_moments_yellow_card']) ? $values['moments']['sl_match_moments_yellow_card'] : 0);
    $entity->set('field_sl_stats_red_cards', !empty($values['moments']['sl_match_moments_red_card']) ? $values['moments']['sl_match_moments_red_card'] : 0);
    $entity->set('field_sl_teams', $team_id);
    $entity->set('field_sl_stats_person', $person_id);

    if (!empty($competition_id)) {
      $entity->set('field_sl_stats_competition', $competition_id);
    }

  }
}