<?php

/**
 * @file
 * Contains Drupal\wisski_adapter_geonames\Plugin\wisski_salz\Engine\GeonamesEngine.
 */

namespace Drupal\wisski_adapter_geonames\Plugin\wisski_salz\Engine;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\wisski_adapter_geonames\Query\Query;
use Drupal\wisski_pathbuilder\Entity\WisskiPathbuilderEntity; 
use Drupal\wisski_pathbuilder\Entity\WisskiPathEntity; 
use \Drupal\wisski_pathbuilder\PathbuilderEngineInterface;
use Drupal\wisski_salz\NonWritableEngineBase;
use Drupal\wisski_salz\AdapterHelper;
use DOMDocument;
use EasyRdf_Graph;
use EasyRdf_Namespace;
use EasyRdf_Literal;

/**
 * Wiki implementation of an external entity storage client.
 *
 * @Engine(
 *   id = "geonames",
 *   name = @Translation("Geonames"),
 *   description = @Translation("Provides access to Geonames")
 * )
 */
class GeonamesEngine extends NonWritableEngineBase implements PathbuilderEngineInterface {
  
  protected $uriPattern  = "!^http://sws.geonames.org/(\w+)/$!u";
  protected $fetchTemplate = "http://sws.geonames.org/{id}/about.rdf";
  
  /**
   * Workaround for super-annoying easyrdf buggy behavior:
   * it will only work on prefixed properties
   */
  protected $rdfNamespaces = [
    'gn' => 'http://www.geonames.org/ontology#',
    'wgs84' => 'http://www.w3.org/2003/01/geo/wgs84_pos#',
  ];
  


  protected $possibleSteps = [
    'gn:Feature' => [
      'gn:name' => NULL,
      'gn:alternateName' => NULL,
      'wgs84:lat' => NULL,
      'wgs84:long' => NULL,
      // By Mark: this is a generated field - such the strange namespace ;D
      'nosebear:WKT' => NULL,
    ],
  ];


  /**
   * {@inheritdoc} 
   */
  public function hasEntity($entity_id) {
    $uris = AdapterHelper::doGetUrisForDrupalIdAsArray($entity_id);
    if (empty($uris)) return FALSE;
    foreach ($uris as $uri) {
      // fetchData also checks if the URI matches the GND URI pattern
      // and if so tries to get the data.
      if ($this->fetchData($uri)) {
        return TRUE;
      }
    }
    return FALSE;
  }


  public function fetchData($uri = NULL, $id = NULL) {
#    drupal_set_message(serialize($uri) . " asas");    
    if (!$id) {
      if (!$uri) {
        return FALSE;
      } elseif (preg_match($this->uriPattern, $uri, $matches)) {
        $id = $matches[1];
      } else {
        // not a URI
        return FALSE;
      }
    }
    
    // 
    $cache = \Drupal::cache('wisski_adapter_geonames');
    $data = $cache->get($id);
    if ($data) {
      return $data->data;
    }

    $replaces = array(
      '{id}' => $id,
    );
    $fetchUrl = strtr($this->fetchTemplate, $replaces);

    $data = file_get_contents($fetchUrl);
    if ($data === FALSE || empty($data)) {
      return FALSE;
    }

    $graph = new EasyRdf_Graph($fetchUrl, $data, 'rdfxml');
    if ($graph->countTriples() == 0) {
      return FALSE;
    }
    foreach ($this->rdfNamespaces as $prefix => $ns) {
      EasyRdf_Namespace::set($prefix, $ns);
    }
    $data = array();
    foreach ($this->possibleSteps as $concept => $rdfPropertyChains) {
      foreach ($rdfPropertyChains as $propChain => $tmp) {
        $pChain = explode(' ', $propChain);
        $dtProp = NULL;
        if ($tmp === NULL) {
          // last property is a datatype property
          $dtProp = array_pop($pChain);
        }
        $resources = array($uri => $uri);
        foreach ($pChain as $prop) {
          $newResources = array();
          foreach ($resources as $resource) {
            foreach ($graph->allResources($resource, $prop) as $r) {
              $newResources[$r] = $r;
            }
          }
          $resources = $newResources;
        }
        if ($dtProp) {
          if($dtProp == 'nosebear:WKT') {
#            dpm($propChain, "propchain!");
            continue;
            //$data[$concept][$propChain][] = "Miauz, genau.";
          }
          foreach ($resources as $resource) {
            foreach ($graph->all($resource, $dtProp) as $thing) {
              if ($thing instanceof EasyRdf_Literal) {
                $data[$concept][$propChain][] = $thing->getValue();
//              } else {
//                $data[$field][] = $thing->getUri();
              }
            }
          }
        }      
      }
    }
    
#    dpm($data, "yay, data!");
#    dpm($concept, "con");
    if( !empty($data[$concept]['wgs84:lat']) && !empty($data[$concept]['wgs84:long']) ) {
      $data[$concept]['nosebear:WKT'][] = 'POINT(' . $data[$concept]['wgs84:long'][0] . ' ' . $data[$concept]['wgs84:lat'][0] . ')'; 
    }
    
#    dpm($data, "out");

    $cache->set($id, $data);
#    dpm($data);
    return $data;

  }


  /**
   * {@inheritdoc}
   */
  public function checkUriExists ($uri) {
    return !empty($this->fetchData($uri));
  }


  /**
   * {@inheritdoc} 
   */
  public function createEntity($entity) {
    return;
  }
  

  public function getBundleIdsForEntityId($id) {
    $uri = $this->getUriForDrupalId($id);
    $data = $this->fetchData($uri);
    
    $pbs = $this->getPbsForThis();
    $bundle_ids = array();
    foreach($pbs as $key => $pb) {
      $groups = $pb->getMainGroups();
      foreach ($groups as $group) {
        $path = $group->getPathArray(); 
#dpm(array($path,$group, $pb->getPbPath($group->getID())),'bundlep');
        if (isset($data[$path[0]])) {
          $bid = $pb->getPbPath($group->getID())['bundle'];
#dpm(array($bundle_ids,$bid),'bundlesi');
          $bundle_ids[] = $bid;
        }
      }
    }
    
#dpm($bundle_ids,'bundles');

    return $bundle_ids;

  }


  /**
   * {@inheritdoc} 
   */
  public function loadFieldValues(array $entity_ids = NULL, array $field_ids = NULL, $bundle = NULL,$language = LanguageInterface::LANGCODE_DEFAULT) {

    if (!$entity_ids) {
      // TODO: get all entities
      $entity_ids = array(
      );
    }
    
    $out = array();

    foreach ($entity_ids as $eid) {

      foreach($field_ids as $fkey => $fieldid) {  
        
        $got = $this->loadPropertyValuesForField($fieldid, array(), $entity_ids, $bundleid_in, $language);

        if (empty($out)) {
          $out = $got;
        } else {
          foreach($got as $eid => $value) {
            if(empty($out[$eid])) {
              $out[$eid] = $got[$eid];
            } else {
              $out[$eid] = array_merge($out[$eid], $got[$eid]);
            }
          }
        }

      }
 
    }

    return $out;

  }
  
  
  /**
   * {@inheritdoc} 
   */
  public function loadPropertyValuesForField($field_id, array $property_ids, array $entity_ids = NULL, $bundleid_in = NULL,$language = LanguageInterface::LANGCODE_DEFAULT) {

    $main_property = \Drupal\field\Entity\FieldStorageConfig::loadByName('wisski_individual', $field_id);
    if(!empty($main_property)) {
      $main_property = $main_property->getMainPropertyName();
    }
    
#     drupal_set_message("mp: " . serialize($main_property) . "for field " . serialize($field_id));
#    if (in_array($main_property,$property_ids)) {
#      return $this->loadFieldValues($entity_ids,array($field_id),$language);
#    }
#    return array();

    if(!empty($field_id) && empty($bundleid_in)) {
      drupal_set_message("Es wurde $field_id angefragt und bundle ist aber leer.", "error");
      dpm(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS));
      return;
    }
    

    $pbs = array($this->getPbForThis());
    $paths = array();
    foreach($pbs as $key => $pb) {
      if (!$pb) continue;
      $field = $pb->getPbEntriesForFid($field_id);
#dpm(array($key,$field),'öäü');
      if (is_array($field) && !empty($field['id'])) {
        $paths[] = WisskiPathEntity::load($field["id"]);
      }
    }
      
    $out = array();

    foreach ($entity_ids as $eid) {
      
      if($field_id == "eid") {
        $out[$eid][$field_id] = array($eid);
      } elseif($field_id == "name") {
        // tempo hack
        $out[$eid][$field_id] = array($eid);
        continue;
      } elseif ($field_id == "bundle") {
      
      // Bundle is a special case.
      // If we are asked for a bundle, we first look in the pb cache for the bundle
      // because it could have been set by 
      // measures like navigate or something - so the entity is always displayed in 
      // a correct manor.
      // If this is not set we just select the first bundle that might be appropriate.
      // We select this with the first field that is there. @TODO:
      // There might be a better solution to this.
      // e.g. knowing what bundle was used for this id etc...
      // however this would need more tables with mappings that will be slow in case
      // of a lot of data...
        
        if(!empty($bundleid_in)) {
          $out[$eid]['bundle'] = array($bundleid_in);
          continue;
        } else {
          // if there is none return NULL
          $out[$eid]['bundle'] = NULL;
          continue;
        }
      } else {
        
        if (empty($paths)) {
#          $out[$eid][$field_id] = NULL;              
        } else {
          
          foreach ($paths as $key => $path) {
            $values = $this->pathToReturnValue($path, $pbs[$key], $eid, 0, $main_property);
            if (!empty($values)) {
              foreach ($values as $v) {
                $out[$eid][$field_id][] = $v;
              }
            }
          }
        }
      }
    }
   
#dpm($out, 'lfp');   
    return $out;

  }


  public function pathToReturnValue($path, $pb, $eid = NULL, $position = 0, $main_property = NULL) {
#dpm($path->getName(), 'spam');
    $field_id = $pb->getPbPath($path->getID())["field"];

    $uri = AdapterHelper::getUrisForDrupalId($eid, $this->adapterId());
    $data = $this->fetchData($uri);
    if (!$data) {
      return [];
    }
    $path_array = $path->getPathArray();
    $path_array[] = $path->getDatatypeProperty();
    $data_walk = $data;
    do {
      $step = array_shift($path_array);
      if (isset($data_walk[$step])) {
        $data_walk = $data_walk[$step];
      } else {
        continue; // go to the next path
      }
    } while (!empty($path_array));
    // now data_walk contains only the values
    $out = array();
    foreach ($data_walk as $value) {
      if (empty($main_property)) {
        $out[] = $value;
      } else {
        $out[] = array($main_property => $value);
      }
    }
    
    return $out;

  }


  /**
   * {@inheritdoc} 
   */
  public function getPathAlternatives($history = [], $future = []) {
    if (empty($history)) {
      $keys = array_keys($this->possibleSteps);
      return array_combine($keys, $keys);
    } else {
      return array();
    }
  }
  
  
  /**
   * {@inheritdoc} 
   */
  public function getPrimitiveMapping($step) {
    $keys = array_keys($this->possibleSteps[$step]);
    return array_combine($keys, $keys);
  }
  
  
  /**
   * {@inheritdoc} 
   */
  public function getStepInfo($step, $history = [], $future = []) {
    return array($step, '');
  }


  public function getQueryObject(EntityTypeInterface $entity_type,$condition, array $namespaces) {
    return new Query($entity_type,$condition,$namespaces);
  }

  public function providesDatatypeProperty() {
    return TRUE;
  }


} 
