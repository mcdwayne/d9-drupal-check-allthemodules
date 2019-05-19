<?php

namespace Drupal\sl_stats;

use Drupal\sl_stats\SLStatsComputerBase;

abstract class SLStatsComputerFull extends SLStatsComputerBase {

  protected $teamStatsType;
  protected $matchesView;
  protected $matchesDisplayId;
  protected $momentsView;
  protected $momentsDisplayId;

  protected $view_matches;
  protected $competitions;

  public function getTotals() {

  }

  function preCompute($player, $team) {
    // computes all matches
    $this->view_matches = $this->getViewsResults($this->matchesView, $this->matchesDisplayId, [
      $player->id(),
      $team->id()
    ]);
    $this->total_team['matches'] = count($this->view_matches);

    if (!empty($this->momentsView)) {
      // computes all moments
      $this->view_moments = $this->getViewsResults($this->momentsView, $this->momentsDisplayId, [
        $player->id(),
        $team->id()
      ]);
    }
  }

  protected function analyzeMatch($match, $player, $team) {

  }

  public function compute($player, $team) {

    $this->competitions = $this->total_team = array();

    // computes all matches done in this team
    $this->preCompute($player, $team);

    // iterate on all matches
    if (!empty($this->view_moments)) {
      foreach ($this->view_moments as $match) {
        $match = (object) $match;
        if (!isset($this->competitions[$match->field_sl_competition]['moments'][$match->type])) {
          $this->competitions[$match->field_sl_competition]['moments'][$match->type] = 1;
        }
        else {
          $this->competitions[$match->field_sl_competition]['moments'][$match->type]++;
        }

        if (!isset($this->total_team['moments'][$match->type])) {
          $this->total_team['moments'][$match->type] = 1;
        }
        else {
          $this->total_team['moments'][$match->type]++;
        }
      }
    }

    $this->total_team['matches'] = count($this->view_matches);

    if (!empty($this->view_matches)) {
      foreach ($this->view_matches as $match) {
        $match = (object) $match;

        $this->analyzeMatch($match, $player, $team);

        if (isset($match->field_sl_roster_in) && isset($match->field_sl_roster_in)) {
          $in = $match->field_sl_roster_in;
          $out = $match->field_sl_roster_out;

          // find match time
          $time = $out - $in;
          $this->total_team['minutes'] += $time;

          if (!isset($this->competitions[$match->field_sl_competition]['minutes'])) {
            $this->competitions[$match->field_sl_competition]['minutes'] = 1;
          }

          $this->competitions[$match->field_sl_competition]['minutes'] += $time;
        }

        $this->competitions[$match->field_sl_competition]['matches']++;
      }
    }
    // depends on the stats entity type
    $entity = $this->stats_manager->create(array('type' => $this->teamStatsType));
    $this->createEntity($entity, $this->total_team, $player->id(), $team->id());
    \Drupal::moduleHandler()->alter('sl_stats_person', $entity, $player);
    $entity->save();

    $player->total_stats['matches'] += $this->total_team['matches'];
    $player->total_stats['goals'] += empty($this->total_team['moments']['sl_match_moments_goal']) ? 0 : $this->total_team['moments']['sl_match_moments_goal'];
    $this->postCompute($player, $team);
  }

  function postCompute($player, $team) {
    if (!empty($this->competitions)) {
      foreach ($this->competitions as $key => $value) {
        if (is_numeric($key)) {
          $entity_c = $this->stats_manager->create(array('type' => $this->teamStatsType));
          $this->createEntity($entity_c, $value, $player->id(), $team->id(), $key);
          \Drupal::moduleHandler()->alter('sl_stats_person', $entity_c, $node);
          $entity_c->save();
        }
      }
    }
  }

}