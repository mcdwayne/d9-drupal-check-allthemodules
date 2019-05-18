<?php

/**
 * Special report embedder
 * @author davidmetzler
 *
 */
namespace Drupal\forena\FrxPlugin\Context;
use Drupal\forena\FrxAPI;

/**
 * Class FrxReport
 * @FrxContext(
 *   id = "FrxReport"
 * )
 */
class FrxReport extends ContextBase{
  use FrxAPI; 

  // Get report based on current context and embed it.
  public function getValue($key) {
    $output = $this->report($key);
    if (is_array($output)) $output = $output['report']['#template'];
    return $output;
  }

  // Override obejct getter. 
  public function __get($key) {
    return $this->getValue($key); 
  }
}