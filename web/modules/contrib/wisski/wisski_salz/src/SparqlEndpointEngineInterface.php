<?php

/**
 * @file
 * Contains Drupal\wisski_salz\EngineInterface.
 */

namespace Drupal\wisski_salz;


/**
 * Defines an interface for external entity storage client plugins.
 */
interface SparqlEndpointEngineInterface extends EngineInterface  {
  
  public function directQuery($query);


  public function directUpdate($query);

}


