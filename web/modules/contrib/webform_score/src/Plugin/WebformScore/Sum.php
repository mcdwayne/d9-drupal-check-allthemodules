<?php

namespace Drupal\webform_score\Plugin\WebformScore;

use Drupal\Core\TypedData\TypedDataInterface;

/**
 * @WebformScore(
 *   id="sum",
 *   label=@Translation("Sum score from a set"),
 *   compatible_data_types={"*"},
 *   is_aggregation=true,
 * )
 */
class Sum extends WebformScoreAggregateBase {

  /**
   * {@inheritdoc}
   */
  public function getMaxScore() {
    $max_score = 0;
    foreach ($this->createPlugins() as $plugin) {
      $max_score += $plugin->getMaxScore();
    }

    return $max_score;
  }

  /**
   * {@inheritdoc}
   */
  public function score(TypedDataInterface $answer) {
    $score = 0;

    foreach ($this->createPlugins() as $plugin) {
      $score += $plugin->score($answer);
    }

    return $score;
  }

}
