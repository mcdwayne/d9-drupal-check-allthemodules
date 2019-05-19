<?php

namespace Drupal\webform_score\Plugin\WebformScore;

use Drupal\Core\TypedData\TypedDataInterface;

/**
 * @WebformScore(
 *   id="maximum",
 *   label=@Translation("Max score from a set"),
 *   compatible_data_types={"*"},
 *   is_aggregation=true,
 * )
 */
class Maximum extends WebformScoreAggregateBase {

  /**
   * {@inheritdoc}
   */
  public function getMaxScore() {
    $max_scores = [];
    foreach ($this->createPlugins() as $plugin) {
      $max_scores[] = $plugin->getMaxScore();
    }

    return max($max_scores);
  }

  /**
   * {@inheritdoc}
   */
  public function score(TypedDataInterface $answer) {
    $scores = [];

    foreach ($this->createPlugins() as $plugin) {
      $scores[] = $plugin->score($answer);
    }

    return max($scores);
  }

}
