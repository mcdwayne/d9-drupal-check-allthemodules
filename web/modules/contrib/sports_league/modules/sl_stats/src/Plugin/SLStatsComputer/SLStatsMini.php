<?php

namespace Drupal\sl_stats\Plugin\SLStatsComputer;

use Drupal\sl_stats\SLStatsComputerFull;

/**
 * Provides a basic widget.
 *
 * @SLStatsComputer(
 *   id = "sl_stats_mini",
 *   name = @Translation("SL Stats Mini"),
 *   description = @Translation("SL Stats Mini"),
 * )
 */
class SLStatsMini extends SLStatsComputerFull {

  protected $teamStatsType = 'sl_stats_mini';
  protected $matchesView = NULL;

  public function getTotals() {

  }

  public function isApplicable($player, $team) {
    if ($this->getTeamStatsType($team) == 'sl_stats_mini') {
      return TRUE;
    }

    return FALSE;
  }

  function createEntity($entity, $values, $person_id, $team_id, $competition_id = NULL) {

    $entity->set('field_sl_teams', $team_id);
    $entity->set('field_sl_stats_person', $person_id);

    if (!empty($competition_id)) {
      $entity->set('field_sl_stats_competition', $competition_id);
    }

  }
}