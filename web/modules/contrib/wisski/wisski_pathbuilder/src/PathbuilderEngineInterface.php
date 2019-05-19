<?php
/**
 * @file
 * Contains \Drupal\wisski_pathbuilder\PathbuilderEngineInterface
 */

namespace Drupal\wisski_pathbuilder;

use Drupal\wisski_salz\EngineInterface;

/**
 * Provides an interface defining a pathbuilder path entity type.
 */
interface PathbuilderEngineInterface extends EngineInterface {
  
  /**
   * Returns a list of possible steps between history of steps and future of
   * steps.
   *
   * @param history An array of the previous steps or an empty array if this is
   *  the beginning of the path.
   * @param future An array of the following steps or an empty array if this is 
   *  (currently!) the last step.
   *
   * @return array
   *  A list of steps
   *
   */
  public function getPathAlternatives($history = [], $future = []);
  
  
  /**
   * Get the primitive mapping from a certain step in the semantics
   * usually used for primitive datatypes in rdf - like P3 has note in
   * cidoc crm. But can contain a mapping like "the nth column of the table"
   *
   * @param step the step
   *
   * @return array
   *  A list of valid primitive datatypes.
   */
  public function getPrimitiveMapping($step);
  
  /**
   * Returns human readable information for a step.
   *
   * @param step the step
   *
   * @return an array with following keys
   *  label : a human readable label, translatable, one line
   *  description : a description of the step, translatable, multiline
   */
  public function getStepInfo($step, $history = [], $future = []);

}
