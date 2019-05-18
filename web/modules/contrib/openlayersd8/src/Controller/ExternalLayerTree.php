<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Drupal\openlayers\Controller;

use Symfony\Component\HttpFoundation\Response;
//use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

/**
 * Description of ExternalLayerTree
 *
 * @author Ballibum
 */
class ExternalLayerTree {
  
  private $source_id;
  private $source_url;
  
  public function __construct($source_id = null) {
    $this->source_id = $source_id;
    $this->source_url = $this->getURL();
  }
  
  private function getURL() {
    $source = \Drupal::entityManager()->getStorage('openlayers_source')->load($this->source_id);
    return $source->get('source_url')->value;
  }
  
  public function getSourceURL() {
    return $this->source_url;
  }
  
  public function getOptions() {
    //Get GetCapabilies.
    $client = \Drupal::httpClient();
    $request = $client->get($this->source_url.'service=wms&request=getcapabilities');
    
    //Make XML-DOcument.
    $xml = new \SimpleXMLElement($request->getBody());
    $layer = $xml->xpath("/*/*[local-name()='Capability']/*[local-name()='Layer']/*[local-name()='Layer']");
		
    $options = array();
    foreach($layer as $item) {
      $options[(string)$item->Name] = (string)$item->Title;
    }
    return $options;
  }
}
