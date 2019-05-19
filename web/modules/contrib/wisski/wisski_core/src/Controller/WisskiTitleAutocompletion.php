<?php

namespace Drupal\wisski_core\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class WisskiTitleAutocompletion {
  
  public $limit = 30;

  public function autocomplete(Request $request) {
    
    $string = $request->query->get('q');

    $bundles = $request->query->get('bundles');
    
    $matches = array($string);
#    dpm($request, "string");    
    if ($string) {
      // just query the ngram table
      $select = \Drupal::service('database')
          ->select('wisski_title_n_grams','w');
      $rows = $select
          ->fields('w', array('ngram'))
          ->condition('ngram', "%" . $select->escapeLike($string) . "%", 'LIKE')
          ->condition('bundle', array_keys($bundles), 'IN')
          ->range(0, $this->limit)
          ->execute()
          ->fetchCol();
      
      $matches = $rows;
      
    } 

    return new JsonResponse($matches);
  }
}
