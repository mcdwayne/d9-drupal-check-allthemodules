<?php

namespace Drupal\indeed_search\Controller;

use Drupal\Core\Controller\ControllerBase;

class JobsController extends ControllerBase {
  public function content() {
     $block = [];
    // print_r($formof);
    // die(); 
     $block['content'] = indeed_search_xml();
 
    return [
        '#type' => 'markup',
        '#markup' => $block['content'],
    ];
 
  }

  
  
  public function content_k($keyword,$location,$sortby) {
    $block = [];
    $block['content'] = indeed_search_xml($keyword,$location,$sortby);

    return [
        '#type' => 'markup',
        '#markup' => $block['content'],
    ];
 
  } 
}
