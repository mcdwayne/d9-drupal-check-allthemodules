<?php

namespace Drupal\webform_score\Plugin;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\TypedData\TypedDataInterface;

/**
 * Defines the interface for webform score.
 *
 * @see \Drupal\webform_score\Annotation\WebformScore
 * @see \Drupal\webform_score\Plugin\WebformScoreManager
 * @see \Drupal\webform_score\Plugin\WebformScoreManagerInterface
 * @see plugin_api
 */

interface WebformScoreInterface extends PluginInspectionInterface, ConfigurablePluginInterface {

  /**
   * Retrieve maximum possible score for this question.
   *
   * @return int
   *   Maximum possible score for this question.
   */
  public function getMaxScore();

  /**
   * Score a given answer.
   *
   * Calculate score for the provided answer per current configuration of the
   * plugin.
   *
   * @param TypedDataInterface $answer
   *   Answer to score.
   *
   * @return int
   *   Return actual score for the provided answer.
   */
  public function score(TypedDataInterface $answer);

}