<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Drupal\registry_codes\Controller;

use Drupal\isoregistry\Controller\RegistryExceptions;
use Symfony\Component\HttpFoundation\Response;

/**
 * Description of StylesNode
 *
 * @author Ballibum
 */
class CodelistsJSON {
  
  function __construct() {
  }
  
  function getResponse($nids, $namespace){
    if(count($nids) < 1 ) {
      $response = new RegistryExceptions(t('kein entsprechenden Code im angegeben Namensraum gefunden'));
      return $response->getDefaultException();
    } else {
      $nid = null;
      foreach ($nids as $key => $value) {
        $nid = $value;
      };
      
      $response = new Response();
      $response->setContent($this->generateCode($nid, $namespace));
      $response->setStatusCode(Response::HTTP_OK);
      $response->headers->set('Content-Type', 'application/json');
      return $response;
    }
  }
  
  
  private function generateCode($nid, $namespace) {
    $node = \Drupal::entityManager()->getStorage('node')->load($nid);
    /*
     * $template = '<value id="https://registry.gdi-de.org/registry/' . $namespace . '/code/'. $node->get('field_codes_code')->value .'">'
            . '<thisversion>https://registry.gdi-de.org/registry/' . $namespace . '/code/'. $node->get('field_codes_code')->value .':1</thisversion>'
            . '<latestversion>https://registry.gdi-de.org/register/' . $namespace . '/code/'. $node->get('field_codes_code')->value .'</latestversion>'
            . '<label xml:lang="de">'. $node->getTitle() .'</label>'
            . '<definition xml:lang="de">'.$node->get('field_codes_description')->value.'</definition>'
            . '<itemclass uriname="CodeListValue"><label xml:lang="de">Code list value'
            . '</label>'
            . '</itemclass>'
            . '<!-- <status id="http://inspire.ec.europa.eu/registry/status/submitted"><label xml:lang="en">Submitted</label></status>-->'
            . '</value>';
     * 
     */
    return json_encode($template);
  }
}