<?php

namespace Drupal\inmail;

/**
 * An analyzer result collects analysis reports within a certain topic.
 *
 * Every inheriting class should provide setters and getters for properties
 * within the topic that it covers.
 *
 * @ingroup analyzer
 */
interface AnalyzerResultInterface {

  /**
   * Gives a summary of the analysis results.
   *
   * @return array
   *   An associated array of analysis results.
   */
  public function summarize();

  /**
   * Returns the name of the topic covered by the result.
   *
   * @return string
   *   A translated label for the result object.
   */
  public function label();



}
