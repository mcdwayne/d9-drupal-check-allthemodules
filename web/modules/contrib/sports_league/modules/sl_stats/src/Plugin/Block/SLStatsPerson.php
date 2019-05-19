<?php

namespace Drupal\sl_stats\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use \Drupal\Core\Render\Markup;
use \Drupal\views\Views;

/**
 * Provides a 'SLStats Person' block.
 *
 * @Block(
 *  id = "slstats_person",
 *  admin_label = @Translation("SL Stats Person"),
 * )
 */
class SLStatsPerson extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    $content = [];

    // to be used in sl_person node page
    if ($node = \Drupal::routeMatch()->getParameter('node')) {
      if ($node->bundle() == 'sl_person') {

        $stats_manager = \Drupal::entityTypeManager()->getStorage('sl_stats');

        // check what type of stats will be shown
        $efq = \Drupal::entityQuery('sl_stats');
        $efq->condition('field_sl_stats_person', $node->id());
        $stats_ids = $efq->execute();

        if (!empty($stats_ids)) {
          $results = \Drupal::database()
            ->select('sl_stats')
            ->fields('sl_stats', ['id', 'type'])
            ->condition('id', $stats_ids, 'IN')
            ->execute()
            ->fetchAll();

          foreach ($results as $key => $stats) {
            if ($stats->type == 'sl_stats_player') {
              $block = 'block_1';
            }
            else {
              if ($stats->type == 'sl_stats_coach') {
                $block = 'block_3';
              }
              else {
                if ($stats->type == 'sl_stats_mini') {
                  $block = 'block_5';
                }
              }
            }
          }

          // show them
          $view = Views::getView('sl_stats_players');
          if (is_object($view)) {
            $view->setArguments([$node->id()]);
            $view->setDisplay($block);
            $view->preExecute();
            $view->execute();
            $view->buildRenderable($block, [$node->id()]);
            $content = $view->render();
          }
        }
      }
    }
    $build = [];
    $build['slstats_person'] = $content;

    return $build;
  }

}
