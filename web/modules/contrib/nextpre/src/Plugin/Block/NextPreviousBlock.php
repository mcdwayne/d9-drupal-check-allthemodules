<?php

namespace Drupal\nextpre\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;

/**
 * Provides a 'Next Previous' block.
 *
 * @Block(
 *   id = "next_previous_block",
 *   admin_label = @Translation("Next Previous Block"),
 *   category = @Translation("Blocks")
 * )
 */
class NextPreviousBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    // Get the created time of the current node.
    $node = \Drupal::request()->attributes->get('node');
    $created_time = $node->get('vid')->getValue()[0]['value'];
    $link = "";
    $link .= $this->generatePrevious($created_time);
    $link .= $this->generateNext($created_time);
    return ['#markup' => $link];
  }

  /**
   * Cahce set none.
   */
  public function getCacheMaxAge() {
    return 0;
  }

  /**
   * Lookup the previous node,youngest node which is still older than the node.
   */
  private function generatePrevious($created_time) {
    return $this->generateNextPrevious($created_time, 'prev');
  }

  /**
   * Lookup the next node,oldest node which is still younger than the node.
   */
  private function generateNext($created_time) {
    return $this->generateNextPrevious($created_time, 'next');
  }

  /**
   * Lookup the next or previous node.
   */
  private function generateNextPrevious($created_time, $direction = 'next') {
    if ($direction === 'next') {
      $comparison_opperator = '>';
      $sort = 'ASC';
      $display_text = t('Next Post');
      $class = "blognext";
    }
    elseif ($direction === 'prev') {
      $comparison_opperator = '<';
      $sort = 'DESC';
      $display_text = t('Previous Post');
      $class = "blogprevious";
    }
    // Lookup 1 node younger (or older) than the current node.
    $query = \Drupal::entityQuery('node');
    $next = $query->condition('vid', $created_time, $comparison_opperator)
      ->condition('type', \Drupal::config('nextpre.settings')->get('nextpre_type'))
      ->sort('vid', $sort)
      ->range(0, 1)
      ->execute();

    // If this is not the youngest (or oldest) node.
    if (!empty($next) && is_array($next)) {
      $next = array_values($next);
      $next = $next[0];

      // Find the alias of the next node.
      $nid = $next;
      $url = Url::fromRoute('entity.node.canonical', ['node' => $nid], []);
      return \Drupal::l($display_text, Url::fromUri('internal:/' . $url->getInternalPath(), ['attributes' => ['class' => ['btn', $class]]]));
    }
  }

}
