<?php

/**
 * @file
 *
 * Data contexts are used to respond to toekn
 */

namespace Drupal\forena\FrxPlugin\Context;

interface ContextInterface {
  
  /**
   * Return the object data as a simplexml element so that xpath and evaluate
   * functions may be used on it within the context of a report.
   *
   * @return \SimpleXMLElement| string
   *   Data returned as a string must still be valid parseable XML. 
   * 
   */
  public function asXML();
}