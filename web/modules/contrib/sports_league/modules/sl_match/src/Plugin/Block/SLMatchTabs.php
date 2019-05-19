<?php

namespace Drupal\sl_match\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use \Drupal\Core\Render\Markup;
use \Drupal\views\Views;

/**
 * Provides a 'SL Match tabs' block.
 *
 * @Block(
 *  id = "sl_match_tabs",
 *  admin_label = @Translation("SL Match tabs"),
 * )
 */
class SLMatchTabs extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    $build = [];
    // to be used in sl_person node page
    if ($node = \Drupal::routeMatch()->getParameter('node')) {
      if ($node->bundle() == 'sl_match') {
        $prematch_info = $node->field_sl_match_prematch_info->view('teaser');
        $match_status = $node->field_sl_match_status->value;

        // check what type of stats will be shown
        $efq = \Drupal::entityQuery('sl_stats');
        $efq->condition('field_sl_stats_person', $node->id());
        $stats_ids = $efq->execute();

        if ($match_status == 'played') {

          $rosters = [
            'home_ini' => 'block_2',
            'away_ini' => 'block_1',
            'home_subs' => 'block_4',
            'away_subs' => 'block_3',
            'home_coach' => 'block_5',
            'away_coach' => 'block_6'
          ];

          // rosters
          foreach ($rosters as $key => $block_id) {
            $view = Views::getView('sl_match_rosters');
            $view->setArguments([$node->id()]);
            $view->setDisplay($block_id);
            $view->preExecute();
            $view->execute();
            $view->buildRenderable($block_id, [$node->id()]);
            $build['sl_match_tabs']['#' . $key] = $view->render();
          }

          $moments = [
            'home_moments' => 'block_1',
            'away_moments' => 'block_2',
          ];

          // rosters
          foreach ($moments as $key => $block_id) {
            $view = Views::getView('sl_match_moments');
            $view->setArguments([$node->id()]);
            $view->setDisplay($block_id);
            $view->preExecute();
            $view->execute();
            $view->buildRenderable($block_id, [$node->id()]);
            $build['sl_match_tabs']['#' . $key] = $view->render();
          }

          $extra_classes_rosters[] = [];
          $build['sl_match_tabs']['#extra_classes_rosters'] = implode(" ", [
            'home-' . $node->field_sl_match_team_home->entity->field_sl_club->target_id,
            'away-' . $node->field_sl_match_team_away->entity->field_sl_club->target_id
          ]);


        }
      }
    }

    $build['sl_match_tabs']['#theme'] = 'sl_match_tabs';
    $build['sl_match_tabs']['#prematch_info'] = $prematch_info;
    $build['sl_match_tabs']['#match_status'] = $match_status;

    return $build;
  }

}
