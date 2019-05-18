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

class CodelistsXML {
  
  private $node;
  private $childrennids = null;
  function __construct() {}
  
  function getResponse($nids, $namespace, $tid, $lang, $extend = false){
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
      if($nid) {
        $config = \Drupal::config('registry_codes.settings');
        $this->childrennids = \Drupal::entityQuery('node')
          ->condition('type', $config->get('enabled_content_types'), 'IN') 
          ->condition('field_codes_parent', $nid)  
          ->condition('field_namespace', $tid)
          ->condition('langcode', $lang)
          ->execute();
      }
     
      
      $response = new Response();
      $response->setContent($this->generateCode($nid, $namespace, $lang, $extend));
      $response->setStatusCode(Response::HTTP_OK);
      $response->headers->set('Content-Type', 'application/xml');
      return $response;
    }
  }
  
  private function generateCode($nid, $namespace,$lang, $extend) {
    
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
    $config = \Drupal::config('isoregistry.settings');
    $template = '<codelist xmlns="http://inspire.ec.europa.eu/codelist_register/codelist" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://inspire.ec.europa.eu/codelist_register/codelist http://inspire.ec.europa.eu/draft-schemas/registry/1.3/CodeList.xsd" id="' . $url . $namespace . '/codelist/' . $this->node->get('field_codes_code')->value . '">'
            . '<thisversion>' . $url . $namespace . '/codelist/' . $this->node->get('field_codes_code')->value . '</thisversion>'
            . '<latestversion>' . $url . $namespace . '/codelist/' . $this->node->get('field_codes_code')->value . '</latestversion>'
            . '<language>' . $lang . '</language>'
            . '<label xml:lang="' . $lang . '">' . $this->node->getTitle() . '"</label>' 
            . '<definition xml:lang="' . $lang . '">' . $this->node->get('field_codes_definition')->value . '</definition>'
            . '<!-- <extensibility id="http://inspire.ec.europa.eu/registry/extensibility/open"><uriname>open</uriname></extensibility><theme id="http://inspire.ec.europa.eu/theme/ps"><label xml:lang="' . $lang . '">Protected Sites</label></theme><applicationschema id="http://inspire.ec.europa.eu/applicationschema/ps"><label xml:lang="' . $lang . '">Protected Sites</label>    </applicationschema><itemclass uriname="CodeList"><label xml:lang="' . $lang . '">Code list</label></itemclass><status id="http://inspire.ec.europa.eu/registry/status/submitted"><label xml:lang="' . $lang . '">Submitted</label></status>-->'
            . '<register id="' . $url . $namespace . '">'
            . '<label xml:lang="' . $lang . '">GDI-DE Registry Codelist Register</label>'
            . '<registry id="' . $config->get('registryurl') . '">'
            . '<label xml:lang="' . $lang . '">' . $config->get('registrylabel') . '</label>'
            . $this->getExtend($extend)
            . '</registry>'
            . '</register>'
            . '<containeditems>'
            . $this->getItems($extend, $namespace, $lang)
            . '</containeditems></codelist>';
    return $template;
  }
  
  
  private function getItems($extend, $namespace ,$lang) { 
    $response = '';
    foreach($this->childrennids as $childId) {
      $response = $response . '<item>';
      $code = new CodesXML();
      $response = $response . $code->generateCode($childId, $namespace, $lang, $extend);
      $response = $response . '</item>';
    }
    return $response;
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
  