<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Drupal\registry_codes\Controller;

use Drupal\isoregistry\Controller\RegistryExceptions;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Description of StylesNode
 *
 * @author Balschmiter
 */
class CodesNode {
  
  private $node = null;
  
  function __construct() {
  }
  
  function getResponse($nids, $lang){
    if(count($nids) < 1 ) {
      $response = new RegistryExceptions(t('Please check namespace, code and language'));
      return $response->getDefaultException();
    } else {
      $nid = null;
      foreach ($nids as $key => $value) {
        $nid = $value;
      }
      $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
      if($language != $lang) {
        $language = $lang;
        global $base_url;
        $response = new RedirectResponse($base_url . '/' . $lang . '/node/' . $nid);
        return  $response;
      } else {
        global $base_url;
        $response = new RedirectResponse($base_url.'/node/'.$nid);
        return  $response;
      }
    }
  }
}
