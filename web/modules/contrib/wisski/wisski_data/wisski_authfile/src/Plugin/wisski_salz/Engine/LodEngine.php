<?php

/**
 * @file
 * Contains Drupal\wisski_authfile\Plugin\wisski_salz\Engine\LodEngine.
 */

namespace Drupal\wisski_authfile\Plugin\wisski_salz\Engine;

use DOMDocument;

use Drupal\core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Serialization\Yaml;

use Drupal\wisski_authfile\Query\Query;
use Drupal\wisski_pathbuilder\Entity\WisskiPathbuilderEntity; 
use Drupal\wisski_pathbuilder\Entity\WisskiPathEntity; 
use Drupal\wisski_pathbuilder\PathbuilderEngineInterface;
use Drupal\wisski_salz\NonWritableEngineBase;
use Drupal\wisski_salz\AdapterHelper;

use EasyRdf_Graph;
use EasyRdf_Namespace;
use EasyRdf_Literal;
use EasyRdf_Resource;


/**
 * A simple adapter engine for linked open data repositories that can be 
 * accessed via the entity's URI. Read-only and not searchable.
 *
 * @Engine(
 *   id = "authfile_lod",
 *   name = @Translation("Simple LOD"),
 *   description = @Translation("Provides access to Linked Open Data repositories via cool URIs.")
 * )
 */
class LodEngine extends NonWritableEngineBase implements PathbuilderEngineInterface {
  
  const DEFAULT_CACHE = 'wisski_authfile_lod_engine';

  
  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + 
    [
      /*
       * Pattern to check URIs against. Only matching URIs will be handled.
       * The pattern MUST include a named subpattern 'id' in order to work
       * correctly. 
       * Example (geonames): "!^http://sws.geonames.org/(?<id>\w+)/$!u"
       */
      'uri_pattern' => '',
      /* 
       * A URL template that will be used to fetch the actual RDF data for a URI.
       * This is used as convenience to circumvent HTTP Content Negotiation.
       * The URL MUST contain the string '{id}' that will be replaced by the
       * ID of the entry.
       * Example (geonames): "http://sws.geonames.org/{id}/about.rdf"
       */
      'fetch_template' => '',
      /*
       * Workaround for super-annoying easyrdf behavior:
       * it will only work on prefixed properties
       * Example (geonames):
       * protected $rdfNamespaces = [
       *   'gn' => 'http://www.geonames.org/ontology#',
       *   'wgs84' => 'http://www.w3.org/2003/01/geo/wgs84_pos#',
       * ];
       */
      'rdf_namespaces' => [],
      /*
       * Simple property chain declaration for pathbuilder-like paths.
       * The array contains property chain paths keyed by a "group/class". 
       * Each group is an array where the keys are chains of properties.
       * Properties are separated by whitespace. The value is either NULL or 
       * non-NULL. If it is NULL, the last property of the chain is regarded
       * to be a data property.
       * Example (geonames):
       * $configuration['possible_steps'] = [
       *   'gn:Feature' => [
       *     'gn:name' => NULL,
       *     'gn:alternateName' => NULL,
       *     'wgs84:lat' => NULL,
       *     'wgs84:long' => NULL,
       *   ],
       * ];
       */
      'possible_steps' => [],
      /*
       * Cache bin to be used to store the fetched data. 
       * This class defines a default bin for all engine instances. However, 
       * instances may choose a different bin to better separate things.
       * As the engine does not create new bins, this setting currently cannot
       * be changed from the GUI.
       */
      'cache_bin' => self::DEFAULT_CACHE,
      /* Time-to-live for cached entries */
      'cache_expire' => Cache::PERMANENT,
      /*
       * Preprocessing of the fetched data.
       * This is an array of preprocessing steps (in execution order), each an 
       * associative array that describes the preprocessing.
       * Example:
       * $configuration['preprocessing'] = [
       *   [
       *     'command' => 'regex',
       *     'args' => [
       *       '!src="http://example.com!u',
       *       'src="https://example.org',
       *     ],
       *   ],
       *   [
       *     'command' => 'xslt',
       *     'args' => 'http://example.org/xslt.xsl',
       *   ],
       * ];
       */
      'preprocessing' => [],
    ];
  }



  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    
    $form['#tree'] = FALSE;

    $form['uri'] = [
      '#type' => 'details',
      '#title' => $this->t('URI and Access'),
    ];
    $form['uri']['uri_pattern'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URI pattern'),
      '#default_value' => $this->configuration['uri_pattern'],
      '#description' => $this->t("Only URI matching the pattern will be handled. The pattern MUST include a named subpattern 'id' in order to work correctly."),
    ];
    
    $form['uri']['fetch_template'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Fetch Template'),
      '#default_value' => $this->configuration['fetch_template'],
      '#description' => $this->t("A URL template that will be used to fetch data for a URI. The URL MUST contain the string '{id}' that will be replaced by the ID of the entry."),
      '#maxlength' => 256,
    ];
    
    $form['data_schema'] = [
      '#type' => 'details',
      '#title' => $this->t('Data schema'),
    ];
    $form['data_schema']['rdf_namespaces'] = [
      '#type' => 'textarea',
      '#title' => $this->t('RDF Namespaces'),
      #'#default_value' => $this->encodeRdfNamespaces($this->configuration['rdf_namespaces']),
      '#default_value' => Yaml::encode($this->configuration['rdf_namespaces']),
      '#description' => $this->t("All classes and properties used must be prefixes"),
    ];
    
    $form['data_schema']['possible_steps'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Classes and property paths'),
      #'#default_value' => $this->encodePossibleSteps($this->configuration['possible_steps']),
      '#default_value' => Yaml::encode($this->configuration['possible_steps']),
      '#description' => $this->t("Property chain declarations for pathbuilder-like groups and paths. For each group/class one or mutliple property chains can be defined. Groups are not indented. Chains are indented and items are separated by whitespace."),
    ];
    $form['data_schema']['preprocessing'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Preprocessing'),
      '#default_value' => Yaml::encode($this->configuration['preprocessing']),
      '#description' => $this->t("The config as YAML"),
    ];
    
    $form['cache'] = [
      '#type' => 'details',
      '#title' => $this->t('Caching for fetched data'),
    ];

    $form['cache']['cache_bin'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Bin'),
      '#disabled' => TRUE,
      '#default_value' => $this->configuration['cache_bin'],
      '#description' => $this->t("This setting can only be changed programmatically"),
    ];
    
    $form['cache']['cache_expire'] = [
      '#type' => 'number',
      '#title' => $this->t('Lifetime'),
      '#min' => -1,
      '#max' => 3600 * 24 * 365,  // one year should suffice
      '#default_value' => $this->configuration['cache_expire'],
      '#field_suffix' => 's',
      '#description' => $this->t("0 disabled caching, -1 caches permanently"),
    ];
    
    return $form;

  }


  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    #parent:submitConfigurationForm($form, $form_state);
    $this->configuration = $form_state->getValues() + $this->configuration;
    $this->configuration['rdf_namespaces'] = Yaml::decode($this->configuration['rdf_namespaces']);
    $this->configuration['possible_steps'] = Yaml::decode($this->configuration['possible_steps']);
    $this->configuration['preprocessing'] = Yaml::decode($this->configuration['preprocessing']);
  }


  /**
   * Helper function that loads the right cache bin
   */
  protected function cacheBin() {
    if (empty($this->cacheBin)) {
      $cache = NULL;
      try {
        $cache = \Drupal::cache($this->configuration['cache_bin']);
      } catch (\Exception $e) {
        $cache = \Drupal::cache(self::DEFAULT_CACHE);
      }
      $this->cacheBin = $cache;
    }
    return $this->cacheBin;
  }

  
  /**
   * {@inheritdoc}
   */
  public function hasEntity($entity_id) {
    $uris = AdapterHelper::getUrisForDrupalId($entity_id);
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
  
  
  /**
   * Helper function that fetches the data for a URI and parses the result
   */
  public function fetchData($uri = NULL, $id = NULL) {
dpm([$uri,    $id], 'fd');
    if (!$id) {
      if (!$uri) {
        return FALSE;
      } elseif (preg_match($this->configuration['uri_pattern'], $uri, $matches)) {
        $id = $matches['id'];
      } else {
        // not a URI
        return FALSE;
      }
    }
    
dpm([$uri,    $id], 'fdd');
    // 
    $cache = $this->cacheBin();
    $data = $cache->get("uri-$uri");
    if ($data) {
dpm($data, 'fdc');
      return $data->data;
    }

    $replaces = array(
      '{id}' => $id,
    );
    $fetchUrl = strtr($this->configuration['fetch_template'], $replaces);
    
    $data = $cache->get("doc-$fetchUrl");
    if ($data) {
dpm($data, 'fdf');
      $data = $data->data;
    }
    else {
      $data = file_get_contents($fetchUrl);
      if ($data === FALSE || empty($data)) {
        return FALSE;
      }
      $cache->set("doc-$fetchUrl", $data, $this->configuration['cache_expire']);
    }
    
    // preprocess the data
    $preprocessing = $this->configuration['preprocessing'];
    if ($preprocessing) {
      foreach ($preprocessing as $step) {
        $data = $this->preprocess($data, $step['command'], $step['args'], ['uri' => $uri, 'id' => $id]);
      }
    }
#dpm($data);
    
    // parse the rdf triples
    $graph = new EasyRdf_Graph($fetchUrl, $data, 'rdfxml');
    if ($graph->countTriples() == 0) {
      return FALSE;
    }
    foreach ($this->configuration['rdf_namespaces'] as $prefix => $ns) {
      EasyRdf_Namespace::set($prefix, $ns);
    }
#dpm(EasyRdf_Namespace::namespaces());
    $data = array();
    foreach ($this->configuration['possible_steps'] as $concept => $rdfPropertyChains) {
      $rdfPropertyChains = (array) $rdfPropertyChains;
      foreach ($rdfPropertyChains as $propChain => $returnType) {
        $pChain = explode(' ', $propChain);
        $lastProp = array_pop($pChain);
        $resources = ['uri' => [$uri => $uri], 'bnode' => [], 'literal' => []];
        $resources = [$uri => $uri];
        $returnType = $returnType ? : "literal";
        foreach ($pChain as $prop) {
          $newResources = array();
          foreach ($resources as $resource) {
#dpm([$resource, $prop, $graph->all($resource, $prop)], 'res');
            foreach ($graph->allResources($resource, $prop) as $r) {
              $ruri = $r->getUri();
              $newResources[$ruri] = $ruri;
            }
          }
          $resources = $newResources;
        }
#dpm([$pChain, $concept, $resources, $lastProp, $graph]);
        foreach ($resources as $resource) {
#dpm([$resource, $lastProp, $graph->all($resource, $lastProp), $returnType], 'reslast');
          foreach ($graph->all($resource, $lastProp) as $thing) {
#dpm([$thing, $thing instanceof EasyRdf_Resource, get_class($thing), strpos($returnType, 'uri') !== FALSE], 'lastres');
            if ($thing instanceof EasyRdf_Literal and strpos($returnType, 'literal') !== FALSE) {
              $data[$concept][$propChain][] = $thing->getValue();
            } elseif ($thing instanceof EasyRdf_Resource and strpos($returnType, 'uri') !== FALSE) {
              $data[$concept][$propChain][] = $thing->getUri();
            }
          }
        }
      }
    }

    $cache->set("uri-$uri", $data, $this->configuration['cache_expire']);
dpm($data, 'fdr');

    return $data;

  }


  
  /**
   * Dispatches a preprocessing step
   * Returns the modified data
   */
  protected function preprocess ($data, $command, $args, $vars) {
    $method = '_preprocess' . str_replace('_', '', ucwords($command, '_'));
    if (method_exists($this, $method)) {
      $new_args = $this->substitute($vars, $args);
      return $this->$method($data, $new_args);
    }
    return $data;
  }


  protected function substitute($substs, $strings) {
    $strs = (array) $strings;
    $sic = [];
    $corr = [];
    foreach ($substs as $k => $v) {
      $sic[] = "{{$k}}";  // the inner {} are bound to the variable $k due to phps var escape mechanism!
      $corr[] = $v;
    }
    foreach ($strs as &$str) {
#      $str = strtr($str, $corr);
      $str = join('{', array_map(function ($a) use ($sic, $corr) { return strtr($a, array_combine($sic, $corr)); } , explode('{{', $str)));
    }
#dpm([$strings, $strs, $sic, $corr], 'substs');
    return is_array($strings) ? $strs : $strs[0];
  }


  /**
   * Preprocess using a regex pattern
   */
  protected function _preprocessRegex($data, $args) {
    list($pattern, $subst) = $args;
    $return = preg_replace($pattern, $subst, $data);
    if ($return !== NULL) {
      return $return;
    }
    return $data;
  }


  /**
   * Preprocess using an xsl stylesheet
   */
  protected function _preprocessXslt($data, $args) {
    $file_uri = array_shift($args);
    $xsl = new \DOMDocument();
    if (!$xsl->load($file_uri)) {
      return $data;
    }
    $doc = new \DOMDocument();
    if (!$doc->loadXML($data)) {
      // $data is not XML!
      return $data;
    }
    $xsltproc = new \XSLTProcessor();
    $xsltproc->importStylesheet($xsl);
    # use rest of args as processor parameters
    foreach ($args as $k => $v) {
      $v = str_replace('{uri}', $uri, $v);
      $xsltproc->setParameter('', $k, $v);
    }
    $a = $xsltproc->transformToXml($doc);
    return $a;
  }
  

  /**
   * Preprocess using an xsl stylesheet and an external xslt processor
   */
  protected function _preprocessExternal($data, $args) {
    $command = escapeshellcmd(array_shift($args));
    foreach ($args as $arg) {
      $command .= ' ' . escapeshellarg($arg);
    }

    $desc_spec = [
      0 => ['pipe', 'r'],
      1 => ['pipe', 'w'],
      2 => ['pipe', 'w'],
    ];
#dpm($command);
#return $data;
    $process = proc_open($command, $desc_spec, $pipes);
    fwrite($pipes[0], $data);
    fclose($pipes[0]);
    $data2 = stream_get_contents($pipes[1]);
    $err = stream_get_contents($pipes[2]);

    return $data2;
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
dpm($path_array, 'pa');
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
  public function getPathAlternatives($history = [], $future = [], $fast_mode = false, $empty_uri = 'empty' ) {
    if (count($history) % 2 == 0) {
      $keys = array_keys($this->configuration['possible_steps']);
      return array_combine($keys, $keys);
    } 
    else {
      $last = $history[count($history) - 1];
      if (!empty($this->configuration['possible_steps'][$last])) {
        $props = $this->configuration['possible_steps'][$last];
        $props = array_filter($props, function ($a) { return strpos($a, 'uri') !== FALSE; });
        $keys = array_keys($props);
        return array_combine($keys, $keys);
      }
      else {
        return [];
      }
    }
  }
  
  
  /**
   * {@inheritdoc} 
   */
  public function getPrimitiveMapping($last) {
    if (!empty($this->configuration['possible_steps'][$last])) {
      $props = $this->configuration['possible_steps'][$last];
      $props = array_filter($props, function ($a) { return strpos($a, 'literal') !== FALSE; });
      $keys = array_keys($this->configuration['possible_steps'][$last]);
      return array_combine($keys, $keys);
    }
    else {
      return [];
    }
  }
  
  
  /**
   * {@inheritdoc} 
   */
  public function getstepinfo($step, $history = [], $future = []) {
    return array($step, '');
  }


  public function getQueryObject(EntityTypeInterface $entity_type,$condition, array $namespaces) {
    return new Query($entity_type,$condition,$namespaces);
  }

  public function providesDatatypeProperty() {
    return TRUE;
  }


} 
