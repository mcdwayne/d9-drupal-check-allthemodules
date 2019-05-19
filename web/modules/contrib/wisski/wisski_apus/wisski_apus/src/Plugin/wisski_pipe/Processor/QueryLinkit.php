<?php

/**
 * @file
 * Contains \Drupal\wisski_pipe\Plugin\wisski_pipe\Processor\Noop.
 */

namespace Drupal\wisski_apus\Plugin\wisski_pipe\Processor;

use Drupal\wisski_pipe\ProcessorInterface;
use Drupal\wisski_pipe\ProcessorBase;
use Drupal\Core\Url;


/**
 * @Processor(
 *   id = "query_linkit",
 *   label = @Translation("Use linkit matcher"),
 *   description = @Translation(""),
 *   tags = { "text", "search" }
 * )
 */
class QueryLinkit extends ProcessorBase {
  
  
  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  
  /**
   * {@inheritdoc}
   */
  public function doRun() {
      
    $term = $this->data;

    if (!is_string($term)) $term = $term->toString();
    
    $profile = entity_load('linkit_profile', 'wurm');
    $mngr = new \Drupal\linkit\ResultManager();
    $results = $mngr->getResults($profile, $term);
    
    $annos = array();
    if (count($results) > 1 || count($results[0]) > 1) {
      // otherwise it's an empty list as linkit adds
      // a title element for "no results"
      global $base_root;
      foreach ($results as $r) {
        $annos[] = array(
          'uri' => strpos($r['path'], '://') ? $r['path'] : URL::fromURI('internal:' . $r['path'], array('absolute' => TRUE))->toString(),
        );
      }
    }
    $this->data = array(
      'annos' => $annos,
    );



    


  }

}
