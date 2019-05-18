<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Drupal\registry_styles\Controller;

use Drupal\isoregistry\Controller\RegistryExceptions;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Description of StylesNode
 *
 * @author Ballibum
 */
class StylesNode {
  
  private $namespace;
  private $style;
  
  function __construct($namespace, $style) {
    $this->namespace = $namespace;
    $this->style = $style;
  }
  
  function getResponse(){
    //check if  namespace exists
    $tids = \Drupal::entityQuery('taxonomy_term')
      ->condition('vid', 'namespaces')
      ->condition('field_shortcut', $this->namespace)
      ->execute();
    
    if(count($tids) < 1 ) {
      $response = new RegistryExceptions(t('keine Namensraum gefunden, der dem angegebenen entspricht'));
      return $response->getDefaultException();
    } 
    $tid = null;
    foreach ($tids as $key => $value) {
      $tid = $value;
    }
    //Check if node exists
    $nids = \Drupal::entityQuery('node')
      ->condition('type', 'styles')
      ->condition('title', $this->style)
      ->condition('field_namespace', $tid)
      ->execute();
    
    if(count($nids) < 1 ) {
      $response = new RegistryExceptions(t('kein entsprechenden Style im angegeben Namensraum gefunden'));
      return $response->getDefaultException();
    } else {
      $nid = null;
      foreach ($nids as $key => $value) {
        $nid = $value;
      }
      global $base_url;
      $response = new RedirectResponse($base_url.'/node/'.$nid);
      return  $response;
    }
  }
}
