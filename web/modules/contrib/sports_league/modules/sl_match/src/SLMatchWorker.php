<?php

namespace Drupal\sl_match;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Config\ConfigFactory;
use \Drupal\Component\Utility\Timer;

class SLMatchWorker {

  protected $efq;

  /**
   * When the service is created, set a value for the example variable.
   */
  public function __construct(ConfigFactory $config_factory, QueryFactory $efq) {
    $this->efq = $efq;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity.query')
    );
  }

  public function compute($match_id) {

    if (!is_numeric($match_id)) {
      return ;
    }

    $node_manager = \Drupal::entityTypeManager()->getStorage('node');
    $moments_manager = \Drupal::entityTypeManager()->getStorage('sl_match_moments');
    $rosters_manager = \Drupal::entityTypeManager()->getStorage('sl_rosters');

    $node = $node_manager->load($match_id);
    $official_match = !($node->get('field_sl_stats_disabled')->value);

    $efq = \Drupal::entityQuery('sl_match_moments');
    $efq->condition('field_sl_match', $match_id);
    $moments_ids =  $efq->execute();

    $away_starters = array_column($node->field_sl_match_away_inirosters->getValue(), 'target_id');
    $away_coach = array_column($node->field_sl_match_away_coach->getValue(), 'target_id');
    $home_starters = array_column($node->field_sl_match_home_inirosters->getValue(), 'target_id');
    $home_coach = array_column($node->field_sl_match_home_coach->getValue(), 'target_id');

    $counting = array_merge_recursive($away_starters, $away_coach, $home_starters, $home_coach);

    $in = $out = [];
    if (!empty($moments_ids)) {
      $moments = $moments_manager->loadMultiple($moments_ids);

      foreach ($moments as $mom) {

        $mom_type = $mom->bundle();

        if ($mom_type == 'sl_match_moments_substitution' && !empty($mom->field_sl_match_moments_player->target_id) && !empty($mom->field_sl_match_moments_player_in->target_id)) {
          $out[$mom->field_sl_match_moments_player->target_id] = $mom->field_sl_match_moments_time->value;
          $in[$mom->field_sl_match_moments_player_in->target_id] = $mom->field_sl_match_moments_time->value;
        }

        if ($official_match) {
          $mom->set('field_sl_match_moments_official', $official_match);
          $mom->save();
        }

      }
    }

    $efq = \Drupal::entityQuery('sl_rosters');
    $efq->condition('type', ['sl_rosters', 'sl_rosters_coach'], 'IN');
    $efq->condition('field_sl_match', $match_id);
    $rosters_ids = $efq->execute();

    $rosters = $rosters_manager->loadMultiple($rosters_ids);

    foreach($rosters as $roster) {
      if (in_array($roster->field_sl_roster_player->target_id, array_keys($out))) {
        $roster->set('field_sl_roster_out', $out[$roster->field_sl_roster_player->target_id]);
        $roster->set('field_sl_roster_official_stats', $official_match);
        $roster->save();
      }
      else if (in_array($roster->field_sl_roster_player->target_id, array_keys($in))) {
        $roster->set('field_sl_roster_in', $in[$roster->field_sl_roster_player->target_id]);
        $roster->set('field_sl_roster_out', 90);
        $roster->set('field_sl_roster_official_stats', $official_match);
        $roster->save();
      }
      else if (in_array($roster->id(), $counting)) {

        // only set official the full rosters
        $roster->set('field_sl_roster_official_stats', $official_match);
        $roster->save();
      }

      // compute all players
      if (is_numeric($roster->field_sl_roster_player->target_id)) {
        $player = $node_manager->load($roster->field_sl_roster_player->target_id);
        if (!empty($player)) {
          $player->save();
        }
      }
    }

    return $node;
  }

}
