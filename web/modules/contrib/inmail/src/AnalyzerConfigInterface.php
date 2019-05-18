<?php

namespace Drupal\inmail;

/**
 * Thin interface for the analyzer config.
 *
 * @package Drupal\inmail
 */
interface AnalyzerConfigInterface extends InmailPluginConfigInterface {

  /**
   * Returns the weight.
   *
   * @return int
   *   The weight of analyzer configuration.
   */
  public function getWeight();

  /**
   * Sets the weight.
   *
   * @param string|int $weight
   *   The weight of analyzer configuration.
   */
  public function setWeight($weight);

}
