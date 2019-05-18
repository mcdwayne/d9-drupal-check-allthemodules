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
 *
 * @author Balschmiter
 * 
 */

class CodesXML {
  
  private $node;
  
  function __construct() {}
  
  function getResponse($nids, $namespace, $lang, $extend = false){
    if(count($nids) < 1 ) {
      $response = new Response();
      $response->setContent('<error>Please check namespace, code and language</error>');
      $response->setStatusCode(Response::HTTP_BAD_REQUEST);
      $response->headers->set('Content-Type', 'application/xml');
      return $response;
    } else {
      $nid = null;
      foreach ($nids as $key => $value) {
        $nid = $value;
      };
      $response = new Response();
      $response->setContent($this->generateCode($nid, $namespace, $lang, $extend));
      $response->setStatusCode(Response::HTTP_OK);
      $response->headers->set('Content-Type', 'application/xml');
      return $response;
    }
  }
  
  public function generateCode($nid, $namespace, $lang, $extend) {
    $this->node = \Drupal::entityManager()->getStorage('node')->load($nid);
    
    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
    if($language != $lang) {
      $this->node = $this->node->getTranslation($lang); 
      $language = $lang;
    }
    
    $config = \Drupal::config('registry_codes.settings');
    $url = $config->get('codeurl');
    
    if($url === "" || $url === null) {
      global $base_url;
      $url = $base_url; 
    }
    $template = '<value id="' . $url . $namespace . '/code/' . $this->node->get('field_codes_code')->value . '">'
            . '<thisversion>https://registry.gdi-de.org/registry/' . $namespace . '/code/' . $this->node->get('field_codes_code')->value . '</thisversion>'
            . '<latestversion>https://registry.gdi-de.org/register/' . $namespace . '/code/' . $this->node->get('field_codes_code')->value . '</latestversion>'
            . '<label xml:lang="'. $language . '">' . $this->node->getTitle() .'</label>'
            . '<definition xml:lang="'. $language . '">' . $this->node->get('field_codes_definition')->value . '</definition>'
            . '<itemclass uriname="CodeListValue"><label xml:lang="'. $language . '">Code list value'
            . '</label>'
            . '</itemclass>'
            . '<!-- <status id="http://inspire.ec.europa.eu/registry/status/submitted"><label xml:lang="'. $language . '">Submitted</label></status>-->'
            . $this->getExtend($extend)
            . '</value>';
    return $template;
  }
  
  private function getExtend($extend) { 
    $extraContent = '';
    if($extend) {
      $entityManager = \Drupal::service('entity_field.manager');
      $fields = $entityManager->getFieldDefinitions('node', $this->node->bundle());
      foreach ($fields as $field_name => $field_definition) {
        if (!empty($field_definition->getTargetBundle()) && \strpos($field_name, '_extra_') !== false) {
          $extraContent = $extraContent . '<' . str_replace('field_','',$field_name) 
            . ' name="' . $field_definition->getLabel() 
            . '" type="' . $field_definition->getType() . '">'
            . $this->node->get($field_name)->value 
            . '</'. str_replace('field_','',$field_name).'>';
          $bundleFields[$entity_type_id][$field_name]['type'] = $field_definition->getType();
          $bundleFields[$entity_type_id][$field_name]['label'] = $field_definition->getLabel();
        }
      }
      return $extraContent;
    }
    return null;
  }
}
  