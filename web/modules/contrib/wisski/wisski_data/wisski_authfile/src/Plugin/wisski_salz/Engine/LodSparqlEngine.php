<?php

/**
 * @file
 * Contains Drupal\wisski_authfile\Plugin\wisski_salz\Engine\LodSparqlEngine.
 */

namespace Drupal\wisski_authfile\Plugin\wisski_salz\Engine;

use DOMDocument;

use Drupal\core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Serialization\Yaml;

use Drupal\wisski_pathbuilder\Entity\WisskiPathbuilderEntity; 
use Drupal\wisski_pathbuilder\Entity\WisskiPathEntity; 
use Drupal\wisski_pathbuilder\PathbuilderEngineInterface;
use Drupal\wisski_salz\NonWritableEngineBase;
use Drupal\wisski_salz\AdapterHelper;
use Drupal\wisski_adapter_sparql11_pb\Plugin\wisski_salz\Engine\Sparql11EngineWithPB;

use EasyRdf_Graph;
use EasyRdf_Namespace;
use EasyRdf_Literal;
use EasyRdf_Resource;


/**
 * A simple adapter engine for linked open data repositories that can be 
 * accessed via the entity's URI. Read-only and not searchable.
 * 
 * This variant stores the fetched data in a triple store and can be queried
 * like a normal Sparql adapter
 *
 * @Engine(
 *   id = "authfile_lod_sparql",
 *   name = @Translation("Simple LOD with Local Triple Store"),
 *   description = @Translation("Provides access to Linked Open Data repositories via cool URIs.")
 * )
 */
class LodSparqlEngine extends Sparql11EngineWithPB {
  

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    parent::setConfiguration($configuration);
    if (is_null($configuration)) {
      $configuration = array();
    }
    $this->configuration = $configuration + $this->defaultConfiguration();
    $this->is_writable = FALSE;
  }


  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return [
      'is_writable' => FALSE,
    ] + parent::getConfiguration();
  }

 
  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + 
    [
      /*
       * A lod engine is never writable
       */
      'is_writable' => FALSE,
      /*
       * Pattern to check URIs against. Only matching URIs will be handled.
       * The pattern MUST include a named subpattern 'id' in order to work
       * correctly. 
       * Example (geonames): "!^http://sws.geonames.org/(?<id>\w+)/$!u"
       */
      'uri_pattern' => '',
      /* 
       * A URL template that will be used to fetch the actual RDF data for a URI.
       * This is used as convenience to circumvent HTTP Content Negotiation or
       * vocabulary providers that do not support negotiation/redirect.
       * The URL MUST contain the string '{id}' that will be replaced by the
       * ID of the entry.
       * Example (geonames): "http://sws.geonames.org/{id}/about.rdf"
       */
      'fetch_template' => '',
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
    
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['isWritable'] = [
      '#default_value' => 0,
      '#disabled' => TRUE,
    ] + $form['isWritable'];
    
    $form['same_as_properties']['sameAsProperties']['#type'] = 'hidden';
    unset($form['same_as_properties']['available_same_as_properties']);
    $form['default_graph']['#type'] = 'hidden';

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
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration = $form_state->getValues() + $this->configuration;
    $this->configuration['preprocessing'] = Yaml::decode($this->configuration['preprocessing']);
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
#dpm([$uri, $id], 'fd');
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
    
    $replaces = array(
      '{id}' => $id,
    );
    $fetchUrl = strtr($this->configuration['fetch_template'], $replaces);
    
    $cached = FALSE;
    $expire = $this->configuration['cache_expire'];
    if ($expire == 0) {
      // disables caching
      $cached = FALSE;
    }
    else {
      // have a look into the triple store if there is a graph
      $q = "SELECT ?t FROM <http://wiss-ki.eu/ont/authfile/metadata> WHERE { <$fetchUrl> <http://wiss-ki.eu/ont/authfile/fetch_timestamp> ?t }";
      $fetch_date = $this->directQuery($q);
      if ($fetch_date->numRows()) {
        if ($expire == -1) {
          // caches permanently
          $cached = TRUE;
        }
        else {
          $fetch_date = $fetch_date[0]->t->getValue();
          $cached = $fetch_date + $expire >= time();
        }
      }
    }

    if ($cached) {
      return TRUE;
    }

    // we fetch the data and preprocess it
    $data = file_get_contents($fetchUrl);
    if ($data === FALSE || empty($data)) {
      return FALSE;
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
    
    $ntriples = $graph->serialise('ntriples');
    
    $now = time();
    $this->directUpdate("DROP GRAPH <$fetchUrl>; INSERT DATA { GRAPH <$fetchUrl> { $ntriples }  GRAPH <http://wiss-ki.eu/ont/authfile/metadata> { <$fetchUrl> <http://wiss-ki.eu/ont/authfile/fetch_timestamp> \"$now\" } }");

    return TRUE;

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
  public function createEntity($entity, $entity_id = NULL) {
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

  
  public function isWritable() {
    return FALSE;
  }
  
  public function isReadOnly() {
    return TRUE;
  }
  
  public function setReadOnly() {
  }
  
  public function setWritable() {
  }


  public function deleteEntity($entity) {
  }
  
  /**
   * {@inheritdoc}
   */
  public function getSameAsProperties() {
    return array();
  }
  
  /**
   * {@inheritdoc}
   */
  public function defaultSameAsProperties() {
    return array();
  }
  

  protected function getAllProperties() {
    $query = 
      'SELECT DISTINCT ?property WHERE { 
        {
          ?property a rdf:Property . 
        } UNION { 
          ?property a owl:DatatypeProperty . 
        } UNION { 
          ?property a owl:ObjectProperty . 
        } UNION { 
          ?property a owl:AnnotationProperty . 
        } UNION { 
          ?property rdfs:range ?r . 
        } UNION { 
          ?property rdfs:domain ?d . 
        } UNION { 
          ?property a rdfs:label . 
        } UNION {
          ?s ?property ?o .
        }
      }';
  
    $result = $this->directQuery($query);
    if (count($result) == 0) return [];
    
    $output = array();
    foreach ($result as $obj) {
      $prop = $obj->property->getUri();
      $output[$prop] = $prop;
    }
    uksort($output,'strnatcasecmp');

    if ($this->allow_inverse_property_pattern) {
      foreach ($output as $p) {
        $output["^$p"] = "^$p";
      }
    }
    return $output;
  } 


  protected function getAllClasses() {
    $query = 
      'SELECT DISTINCT ?class WHERE { 
        {
          ?class a rdf:Class . 
        } UNION { 
          ?class a owl:Class . 
        } UNION { 
          ?o a ?class . 
        }
      }';
  
    $result = $this->directQuery($query);
    if (count($result) == 0) return [];
    
    $output = array();
    foreach ($result as $obj) {
      $class = $obj->class->getUri();
      $output[$class] = $class;
    }
    uksort($output,'strnatcasecmp');
    return $output;
  } 




  /**
   * @{inheritdoc}
   */
  public function getPrimitiveMapping($step) {
    // as external LOD sources may vary in their use and ontology declaration,
    // we offer everything that looks like or is used as a property
    return $this->getAllProperties();
  }


  /**
   * {@inheritdoc} 
   */
  public function getPathAlternatives($history = [], $future = [], $fast_mode = false, $empty_uri = 'empty') {
    if (count($history) % 2 == 0) {
      $keys = $this->getAllClasses();
      return array_combine($keys, $keys);
    }
    else {
      $keys = $this->getAllProperties();
      return array_combine($keys, $keys);
    }
  }

  
}
