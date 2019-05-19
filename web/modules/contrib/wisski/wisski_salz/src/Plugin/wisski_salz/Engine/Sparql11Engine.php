<?php

/**
 * @file
 * Contains \Drupal\wisski_salz\Plugin\wisski_salz\Engine\Sparql11Engine.
 */

namespace Drupal\wisski_salz\Plugin\wisski_salz\Engine;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;

use Drupal\wisski_salz\AdapterHelper;
use Drupal\wisski_salz\EngineBase;
use Drupal\wisski_salz\RdfSparqlUtil;


abstract class Sparql11Engine extends EngineBase {

  protected $read_url;
  protected $write_url;
  
  protected $graph_rewrite;

  protected $default_graph;

  protected $ontology_graphs;

  protected $rdf_sparql_util = NULL;
  
  /** Holds the EasyRDF sparql client instance that is used to
   * query the endpoint.
   * It is not set on construction.
   * Use getEndpoint() for direct access to the API.
   * 
   * However, the API should not be exposed outside this class, rather this
   * class provides directQuery() and directUpdate() for sending sparql queries
   * to the store.
   */ 
  protected $endpoint = NULL;
  
  
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'read_url' => '',
      'write_url' => '',
      'graph_rewrite' => FALSE,
      'default_graph' => 'graf://dr.acula/',
      'ontology_graphs' => array(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {

    // this does not exist
    parent::setConfiguration($configuration);
    $this->read_url = $this->configuration['read_url'];
    $this->write_url = $this->configuration['write_url'];
    $this->graph_rewrite = $this->configuration['graph_rewrite'];
    $this->default_graph = $this->configuration['default_graph'];
    $this->ontology_graphs = $this->configuration['ontology_graphs'];
    $this->store = NULL;
  }


  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return array(
      'read_url' => $this->read_url,
      'write_url' => $this->write_url,
      'graph_rewrite' => $this->graph_rewrite,
      'default_graph' => $this->default_graph,
      'ontology_graphs' => $this->ontology_graphs,
    ) + parent::getConfiguration();
  }


  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildConfigurationForm($form, $form_state);
    
    $form['read_url'] = [
      '#type' => 'textfield',
      '#title' => 'Read URL',
      '#default_value' => $this->read_url,
      '#description' => 'bla.',
    ];
    $form['write_url'] = [
      '#type' => 'textfield',
      '#title' => 'Write URL',
      '#default_value' => $this->write_url,
      '#description' => 'bla.',
    ];
    $form['graph_rewrite'] = array(
      '#type' => 'checkbox',
      '#title' => 'Use graph independent rewriting',
      '#default_value' => $this->graph_rewrite,
      '#return_value' => TRUE,
      '#description' => 'rewrite queries, so that remote SPARQL storages with non-standard dataset handling do always answer right',
    );
    $form['default_graph'] = [
      '#type' => 'textfield',
      '#title' => 'Default Graph URI',
      '#required' => TRUE,
      '#default_value' => $this->default_graph,
      '#description' => 'Graph URI that is used to store triples in by default. May also be used as a base for new entity URIs.',
    ];
    $form['ontology_graphs'] = [
      '#type' => 'textarea',
      '#title' => 'Ontology graphs',
      '#default_value' => join("\n", $this->ontology_graphs),
      '#description' => t('Graphs that are considered to be containing ontology information. These are used to compute class and property information like hierarchies, domain/range, etc. Leave empty let system automatically detect the graphs.'),
    ];

    
    $form['same_as_properties'] = array('#type'=>'container');
    $form['same_as_properties']['sameAsProperties'] = $form['sameAsProperties'];
    unset($form['sameAsProperties']);
    $form['same_as_properties']['available_same_as_properties'] = array(
      '#type' => 'select',
      '#title' => 'Add standard sameAs property',
      '#options' => $this->standardSameAsProperties(),
      '#empty_option' => ' - '.$this->t('select').' - ',
      '#ajax' => array(
        'wrapper' => 'wisski-same-as',
        'callback' => array($this,'sameAsCallback'),
      ),
    );
    
    $selection = $form_state->getUserInput();
    
    if (isset($selection['available_same_as_properties']) && $input = $selection['available_same_as_properties']) {
      $value = $selection['sameAsProperties'];
      $value = $value ? $value.",\n".$input : $input;
      $form['same_as_properties']['sameAsProperties']['#value'] = $value;
    }
    
    return $form;
  }

  public function sameAsCallback(array $form, FormStateInterface $form_state) {
    
    return $form['same_as_properties']['sameAsProperties'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->read_url = $form_state->getValue('read_url');
    $this->write_url = $form_state->getValue('write_url');
    $this->graph_rewrite = $form_state->getValue('graph_rewrite');
    $this->default_graph = $form_state->getValue('default_graph');
    $this->ontology_graphs = preg_split('/[\s\n\r]+/u', $form_state->getValue('ontology_graphs'), PREG_SPLIT_NO_EMPTY); 
  }
  
  /**
   * returns a list of well-known RDF properties saying that two individuals are (mostly) the same
   * provides a selection for the user to choose from
   */
  public function standardSameAsProperties() {
  
    return array(
      'http://www.w3.org/2002/07/owl#sameAs' => 'owl:sameAs',
      'http://www.w3.org/2004/02/skos/core#closeMatch' => 'skos:closeMatch',
      'http://www.w3.org/2004/02/skos/core#exactMatch' => 'skos:exactMatch',
      'http://www.w3.org/2004/02/skos/core#broadMatch' => 'skos:broadMatch',
      'http://www.w3.org/2004/02/skos/core#narrowMatch' => 'skos:narrowMatch',
      'http://www.w3.org/2004/02/skos/core#relatedMatch' => 'skos:relatedMatch',
    );
  }
  
  //*** Implementation of the EngineInterface methods ***//
  

  public function hasEntity($entity_id) {
    return FALSE;
  }
  
  public function createEntity($entity) {
    return FALSE;
  }

  
  /**
   * @deprecated
   * {@inheritdoc}
   */
  public function loadMultiple($entity_ids = NULL) {
    return array("bla", "blubb");
  }
  

  /**
   * {@inheritdoc}
   */
  public function loadFieldValues(array $entity_ids = NULL, array $field_ids = NULL, $bundle = NULL,$language = LanguageInterface::LANGCODE_DEFAULT) {
    return array(
      "foo" => array(
        'x-default' => array(
          'main' => 'abc',
          'value' => 'def',
        )
      )
    );
  }
  

  

  /**
   * {@inheritdoc}
   */
  public function loadPropertyValuesForField($field_id, array $property_ids, array $entity_ids = NULL, $bundle = NULL,$language = LanguageInterface::LANGCODE_DEFAULT) {
    return array(
      "foo" => array(
        'x-default' => array(
          'main' => 'abc',
          'value' => 'def',
        )
      )
    );
  }





  //*** SPARQL 11 specific members and methods ***//


   /** Return the API to connect to the sparql endpoint
  * 
  * This method should be called if you need an endpoint. It lazy loads the Easyrdf instance
  * which may save time.
  * 
  * @return Returns a EasyRdf_Sparql_Client instance (or a subclass) that is inited to
  * connect to the givensparql 1.1 endpoint.
  */
  protected function getEndpoint() {
    
    if ($this->endpoint === NULL) {
      include_once(__DIR__ . '/WissKI_Sparql_Client.php');
      $this->endpoint = new WissKI_Sparql_Client($this->read_url, $this->write_url);
    }
    return $this->endpoint;

  }  
  

  // *** PUBLIC MEMBER FUNCTIONS *** //

  // 
  // Functions for direct access, firstly designed for test purposes  
  // 
    
  /** Can be used to directly access the easyrdf sparql interface
  *
  * If not necessary, don't use this interface. 
  * 
  * @return @see EasyRdf_Sparql_Client->query
  */
  public function directQuery($query) {
    if ($this->graph_rewrite) $query = $this->graphInsertionRewrite($query);
    return $this->doQuery($query);
  }
  
  private function doQuery($query) {
    if (WISSKI_DEVEL) \Drupal::logger('QUERY '.$this->adapterId())->debug('{q}',array('q'=>$query));
    try {
      $result = $this->getEndpoint()->query($query);
      if (WISSKI_DEVEL) \Drupal::logger('QUERY '.$this->adapterId())->debug('result {r}',array('r'=>serialize($result)));
      return $result;
    } catch (\Exception $e) {
      drupal_set_message('Something went wrong in \''.__FUNCTION__.'\' for adapter "'.$this->adapterId().'"','error');
      \Drupal::logger('QUERY '.$this->adapterId())->error('query "{query}" caused error: {exception}',array('query' => $query, 'exception'=> (string) $e));
    }
  }
  
  public function graphInsertionRewrite($query) {
    
    //dpm($query,'input');
    //first gather all variable names
    $vars = array();
    $variable_regex = '\?\w+';
    preg_match_all("/$variable_regex/",$query,$vars);
    $this->vars = array_unique($vars[0]);
    //dpm($this->vars,'Variables');
    //since we introduce new variables for the graphs we must ensure they do not reappear
    $count = 0;
    $new_query = preg_replace('/(SELECT\s+(?:DISTINCT\s+)?)\*/i','$1'.implode(' ',$this->vars),$query,1,$count);
    //if ($count) dpm($new_query,'variable (*) replacement');
    
    $uri_regex = '(?:\<[^\s\<\>\?]+\>|\w+\:[^\:\s\<\>\?\{\}]+|a)';  
    $placeholder_regex = "(?:$uri_regex|$variable_regex)";
    $triple_regex = "$placeholder_regex\s+$placeholder_regex\s+$placeholder_regex\s*(?:\.|(?=\}))";
    
    //if there already is a graph in the query, we must not rewrite that part
    $graph_detection_regex = "(GRAPH\s+\?\w+\s+((?:[^{}]+|\{(?2)\})*))";
    //preg_split with PREG_SPLIT_DELIM_CAPTURE flag gives us a list of query parts where the GRAPH... parts are 
    //divided from the rest, pitily it is not possible to keep preg_split from including the recursive subpatter (?2)
    //in the result array
    $split = preg_split("/$graph_detection_regex/",$new_query,-1,PREG_SPLIT_DELIM_CAPTURE);
    $new_query = '';
    $part = current($split);
    while ($part !== FALSE) {
      if (strpos($part,'GRAPH') === 0) {
        //GRAPH parts must not be rewritten
        $new_query .= $part;
        //move pointer one step forward since the recursive subpattern has been captured twice
        next($split);
      } else {
        //outside GRAPH-subpatterns we have to rewrite triples
        $new_query .= preg_replace_callback("/$triple_regex/",array($this,'graphReplacement'),$part);
      }
      $part = next($split);
    }
    //dpm($new_query,'graph rewrite');
    return $new_query;
  }
  
  public function graphReplacement($matches) {
    //dpm($matches);
    $triple = $matches[0];
    static $num = 0;
    $graph_name = '?g'.$num;
    if (isset($this->vars)) {
      while (in_array($graph_name,$this->vars)) {
        $graph_name = '?g'.$num++;
      }
    }
    $num++;
    return "{{ $triple } UNION {GRAPH $graph_name { $triple }}}";
  }

  
  /**
   * returns TRUE if this engine provides a kind of datatype that shall be used for the end of pathbuilder paths
   * @TODO add this to the interface
   */
  public function providesDatatypeProperty() {
  
    return TRUE;
  }

  /** Can be used to directly access the easyrdf sparql interface
  *
  * If not necessary, don't use this interface
  * 
  * @return @see EasyRdf_Sparql_Client->update
  */
  public function directUpdate($query) {
    #if (WISSKI_DEVEL)    
#    \Drupal::logger('UPDATE IN '.$this->adapterId())->debug('{u}',array('u'=>$query));
#    return;
    try {
      $out = $this->getEndpoint()->update($query);
#      \Drupal::logger('UPDATE OUT '.$this->adapterId())->debug('{u}',array('u'=>$query));
      return $out;
    }
    catch (\Exception $e) {
      drupal_set_message('Something went wrong in \''.__FUNCTION__.'\' for adapter "'.$this->adapterId().'"','error');
      \Drupal::logger('UPDATE '.$this->adapterId())->error('query "{query}" caused error: {exception}',array('query' => $query, 'exception'=> (string) $e));
      return NULL;
    }
  }

  public function checkUriExists($uri) {
    
    //dpm($this,__FUNCTION__);
    if ($this->isValidUri("<$uri>")) {
      $query = "ASK {{<$uri> ?p ?o.} UNION {?s ?p <$uri>.}}";
#      dpm($query);
      $result = $this->directQuery($query);
      
#      dpm($result);
#      dpm($result->isTrue());
      
      $out = FALSE;

      // if we know nothing - stop it!      
      if(!$result) {
        return FALSE;
      }
      
      if($result->isTrue()) {
        $out = TRUE;
      } else if($result->numRows() > 0) {
        foreach($result as $res) {
          if($res->value->getValue() == TRUE)
            $out = TRUE;
        }
      }
            
      return $out;
    }
    return FALSE;
  }

  /**
   * this is not a true alias for {@see self::getDrupalIdForUri}
   * since it is the internal function that needs EXTERNAL information, i.e. from the AdapterHelper
   * while getDrupalIdForUri works fully internally but is only working correctly for the preferred Local Store
   * Additionally, this function here does a format check, too, finding out whether we already have an EID
   * in this case it just returns the input
   */
  public function getDrupalId($uri) {
    
    if (empty($uri)) return FALSE;
    if (is_numeric($uri)) {
      //danger zone, we assume a numeric $uri to be an entity ID itself
      return $uri;
    }
    $id = AdapterHelper::getDrupalIdForUri($uri,TRUE,$this->adapterId());
    if (empty($id)) throw new \Exception('This URI '.$uri.' has no associated ID in '.$this->adapterId());
    return $id;
  }

  public function getDrupalIdForUri($uri,$adapter_id=NULL) {
  #  dpm($uri, "asking for");
    // easy case - the uri has the id itself.
    if(strpos($uri, "/wisski/navigate/") !== FALSE)
      return AdapterHelper::extractIdFromWisskiUri($uri);
    
    // if not, we have to search it.
    $entity_uris = $this->getSameUris($uri,AdapterHelper::getDrupalAdapterNameAlias());
    
    if (empty($entity_uris)) return NULL;

    // our uri has to be something like /wisski/navigate/.../view
#    dpm($entity_uris, "uris!");    
    foreach($entity_uris as $entity_uri) {
      if(strpos($entity_uri, "/wisski/navigate/") !== FALSE)
        return AdapterHelper::extractIdFromWisskiUri($entity_uri);
    }
    
    drupal_set_message("No entity id could be extracted for uri $uri - sorry. ", "error");
    return NULL;
  }
  
  public function getUrisForDrupalId($id) {
    
    $entity_uri = AdapterHelper::generateWisskiUriFromId($id);
    return $this->getSameUris($entity_uri);
  }
  
  /**
   * {@inheritdoc}
   */
  public function getSameUris($uri) {
    
    $orig_prop = $this->getOriginatesProperty();
    
    $same_props = $this->getSameAsProperties();
    if ($prop = current($same_props)) {
      $values = "VALUES ?same_as { ".$this->ensurePointyBrackets($prop);
      while ($prop = next($same_props)) {
        $values .= " ".$this->ensurePointyBrackets($prop);
      }
      $values .= " }";
    } else throw new \Exception('There is no sameAs property set for this adapter');
#    $query = "SELECT DISTINCT ?uri ?adapter WHERE { $values GRAPH <$orig_prop> {<$uri> ?same_as ?uri. ?uri <$orig_prop> ?adapter. }}";
#    $query = "SELECT DISTINCT ?uri ?adapter WHERE { $values GRAPH <$orig_prop> { { <$uri> ?same_as ?uri } UNION { <$uri> ?same_as ?tmp1 . ?tmp1 ?same_as ?uri } . ?uri <$orig_prop> ?adapter .}} ORDER BY DESC(?uri)";
    $query = "SELECT DISTINCT ?uri ?adapter WHERE { $values GRAPH <$orig_prop> { { <$uri> ?same_as ?uri } UNION { <$uri> ?same_as ?tmp1 . ?tmp1 ?same_as ?uri } . OPTIONAL { ?uri <$orig_prop> ?adapter .} . OPTIONAL { ?tmp1 <$orig_prop> ?adapter . } } } ORDER BY DESC(?uri)";
#    dpm($query, "query");
    $results = $this->directQuery($query);
#    dpm($results, "res");
    
    $out = array();
    if (empty($results)) return array();
    foreach ($results as $obj) {
      if(empty($obj->adapter) || empty($obj->uri))
        continue;
#       dpm($obj->adapter->getValue(), "gv");
#       $out[$obj->adapter->dumpValue('text')] = $obj->uri->getUri();
      $out[$obj->adapter->getValue()] = $obj->uri->getUri();
    }
    
#    dpm($out, "aout");
    
    return $out;
  }

  /**
   * {@inheritdoc}
   */
  public function getSameUri($uri, $adapter_id) {
#    dpm($uri, "uri!");
    $orig_prop = $this->getOriginatesProperty();
    $same_props = $this->getSameAsProperties();
    if ($prop = current($same_props)) {
      $values = "VALUES ?same_as { ".$this->ensurePointyBrackets($prop);
      while ($prop = next($same_props)) {
        $values .= " ".$this->ensurePointyBrackets($prop);
      }
      $values .= " }";
    } else throw new \Exception('There is no sameAs property set for this adapter');
    $query = "SELECT DISTINCT ?uri WHERE { $values GRAPH <$orig_prop> { { <$uri> ?same_as ?uri } UNION { <$uri> ?same_as ?tmp1 . ?tmp1 ?same_as ?uri } . ?uri <$orig_prop> '".$this->escapeSparqlLiteral($adapter_id)."'.}} ORDER BY DESC(?uri)";
#    dpm($query, "getSameUri");
    $results = $this->directQuery($query);
#    dpm($results, "result");
    if (empty($results)) return NULL;
    foreach ($results as $obj) {
      return $obj->uri->getUri();
    }
    return NULL;
  }

  
  /**
   * {@inheritdoc}
   */
  public function setSameUris($uris, $entity_id) {
    
    $uris[AdapterHelper::getDrupalAdapterNameAlias()] = AdapterHelper::generateWisskiUriFromId($entity_id);
    //we use the originates property as name fot the graph for sameAs info
    $orig_prop = $this->getOriginatesProperty();

    if(empty($orig_prop)) {
      drupal_set_message("No Default Graph Uri was set in the store configuration. Please fix it!", "error");
      return FALSE;
    }
    
    $origin = "<$orig_prop> a owl:AnnotationProperty. ";
    $same = '';
    foreach ($uris as $adapter_id => $first) {
      #dpm($this->escapeSparqlLiteral($adapter_id), "adapter!!");
      $origin .= "<$first> <$orig_prop> '".$this->escapeSparqlLiteral($adapter_id)."'. ";
      foreach ($uris as $second) {
        if ($first !== $second) {
          foreach ($this->getSameAsProperties() as $prop) {
            $same .= "<$first> ".$this->ensurePointyBrackets($prop)." <$second>. ";
          }
        }
      }
    }
    if (!empty($same)) {  
      try {
#        drupal_set_message(htmlentities("INSERT DATA { GRAPH <$orig_prop> { $origin $same }}"), "yay!");
        $this->directUpdate("INSERT DATA { GRAPH <$orig_prop> { $origin $same }}");
        return TRUE;
      } catch (\Exception $e) {
        \Drupal::logger(__METHOD__)->error($e->getMessage());
      }
    }
    return FALSE;
  }


  public function deleteSameUris($uris, $other_uris = [], $delete_adapter_ref = TRUE) {
    if (empty($uris)) return;
    if (!is_array($uris)) $uris = array($uris);
    
    $orig_prop = $this->getOriginatesProperty();
    
    $values = 'VALUES ?uri { <' . join('> <', $uris) . '> }';
    if (!empty($other_uris)) {
      if (!is_array($other_uris)) $other_uris = array($other_uris);
      $values .= ' VALUES ?other { <' . join('> <', $uris) . '> }';
    }
    
    $qa = array();
    if ($delete_adapter_ref) {
      $qa[] = "DELETE { GRAPH <$orig_prop> { ?uri <$orig_prop> ?aid } } WHERE { $values GRAPH <$orig_prop> { ?uri <$orig_prop> ?aid } }";
    }
    foreach ($this->getSameAsProperties() as $prop) {
      $qa[] = "DELETE { GRAPH <$orig_prop> { ?uri <$prop> ?other } } WHERE { $values GRAPH <$orig_prop> { ?uri <$prop> ?other } }";
      $qa[] = "DELETE { GRAPH <$orig_prop> { ?other <$prop> ?uri } } WHERE { $values GRAPH <$orig_prop> { ?other <$prop> ?uri } }";
    }
    $q = join('; ', $qa);
    try {
      $this->directUpdate($q);
      return TRUE;
    } catch (\Exception $e) {
      \Drupal::logger(__METHOD__)->error($e->getMessage());
      drupal_set_message('Database error occurred. See logs.');
    }

  }
  
   
  public function generateFreshIndividualUri() {
    return uniqid($this->getDefaultDataGraphUri());
  }
  
  public function ensurePointyBrackets($uri) {
    
    if (strpos($uri,'/') !== FALSE) {
      //ensure we have a full uri in < >
      $uri = '<'.trim($uri,'<>').'>';
    }
    return $uri;
  }

  public function defaultSameAsProperties() {
    
    return array('http://www.w3.org/2002/07/owl#sameAs');
  }
  
  public function getOriginatesProperty() {
    
    return $this->getDefaultDataGraphUri()."originatesFrom";
  }

  public function getDefaultDataGraphUri() {
    // here we should return a default graph for this store.
    return $this->default_graph;
    return "graf://dr.acula/";
  }
  
  public function getPathArray($path) {    
    
  }
  
  /** Builds a sparql query from a given path and execute it.
  *
  * !This is thought to be a convenience function!
  *
  * For a documentation of the parameters see buildQuerySinglePath()
  *
  * @return Returns an EasyRDF result class depending on the query (should be
  *  EasyRdfSparqlResult though as the query verb is always SELECT)
  */
  public function execQuerySinglePath(array $path, array $options = array()) {
    
    if (empty($path)) {
      throw new InvalidArgumentException("Empty path given");
    }
    
    if (is_numeric($path)) {
      $path = $this->getPathArray($path);
    }

    if (!is_array($path) || empty($path)) {
      throw new InvalidArgumentException("Bad path given: " . serialize($path));
    }
    
    // prepare query
    $options['fields'] = FALSE;
    
    // build it
    $sparql = $this->buildQuerySinglePath($path, $options);
    
    // exec
    $result = $this->directQuery($sparql);
    
    // postprocess result?
    
    
    return $result;
      
  }
  

  
  /** This function returns a SPARQL 1.1 query for a given path.
  *
  * !This is thought to be a convenience function!
   *
   * @param path is an associative array that may contain
   * the following entries:
   * $key    | $value
   * ------------------------------------------------------------
   * 'path_array'   | array of strings representing owl:ObjectProperties 
   *                    | and owl:Classes in alternating order
   * 'datatype_property'| string representing an owl:DatatypeProperty
   *
   * For the path_array, instead of strings, also arrays with more 
   * sophisticated options are supported. See code comments below for details.
   *
   * @param options is an associative array that may contain the following 
   * entries:
   * $key     | $value
   * ------------------------------------------------------------
   * 'limit'    | int setting the SPARQL query LIMIT
   * 'offset'    | int setting the SPARQL query OFFSET
   * 'vars'     | array with the variables that should be returned.
   *            | the variable name must be preceeded with an '?'.
   *            | Defaults to all variables.
   * 'var_inst_prefix'    | SPARQL variable name prefix for the datatype value
   *                      | the prefix must be without leading '?' or '$'
   *                      | Defaults to 'x'.
   * 'var_offset'  | int offset for SPARQL variable names.
   *              | Variables will be constructed using the var_inst_prefix and
   *              | a number. Specify the offset here. Default is 0.
   * 'var_dt'    | SPARQL variable name for the datatype value. Default: 'out'
   * 'order'    | string containing 'ASC' or 'DESC' (or 'RAND')
   * 'qualifier'  | SPARQL data qualifier e.g. 'STR'
   * 'search_dt'    | a search struct. See _buildSearchFilter()
   * 'uris'    | array of strings representing owl:Individuals on which the
   *      | query is triggered OR
   *      | an assoc array of such arrays where the keys are the variable name
   *      | that the uris shall be bound to
   * 'fields' | if set to TRUE, return the query parts as array
   *
   * @return the sparql query as a string or the query parts if option fields
   *          is TRUE
   */
  public function buildQuerySinglePath(array $path, array $options = []) {
    
    // variable naming
    $varInstPrefix = isset($options['var_inst_prefix']) ? $options['var_inst_prefix'] : 'x';
    $varOffset = isset($options['var_offset']) ? $options['var_offset'] : 0;
    $varDt = '?' . (isset($options['var_dt']) ? $options['var_dt'] : 'out');
        
    // vars for the query parts
    $head = "SELECT DISTINCT ";
    $vars = [];
     $triples = '';
    $constraints = '';
    $order = '';
    $limit = '';
    
    $pathArray = $path['path_array'];
    if (empty($pathArray)) {
      throw new InvalidArgumentException('Path of length zero given.');
    }

    $uris = isset($options['uris']) ? $options['uris'] : [];

    $var = '';
    
    while (!empty($pathArray)) {
      
      // an individual
      //
      // currently supported values:
      // - a string containing a single uri which is the name of the
      //    this individual belongs to
      // - an array with the following supported keys:
      //   - constraints: an assoc array where the keys are properties
      //      and the value is an array of URIs for classes or indivs
      //      the constraints are or'ed

      $indiv = array_shift($pathArray);
      $var = "?$varInstPrefix$varOffset";
      $vars[$var] = $var;

      if (!is_array($indiv)) {
        $indiv = [
          'constraints' => [
            'a' => [$indiv],
          ],
        ];
      }
      
      // constrain possible uris
      if (isset($uris[$var])) {
        $constraints .= "VALUES $var {<" . implode('> <', $uris[$var]) . ">} .\n";
      }
      
      // further triplewise constraints
      foreach ($indiv['constraints'] as $prop => $vals) {
        foreach ($vals as $val) {
          $triples .= $var . ($prop == 'a' ? ' a ' : " <$prop> ") . "<$val> .\n";
        }
      }

      if (!empty($pathArray)) {
        // a property
        //
        // currently supported values:
        // - a string containing the uri of the property
        // - an array with the following supported keys:
        //   - uris: an assoc array where the keys are uris
        //       and the value is either:
        //      1: normal direction
        //      2: inverse direction
        //      3: both directions (symmetric property)
        //   - expand inverses: if TRUE, expand the given uris to all inverses, too

        $prop = array_shift($pathArray);
        
        if (!is_array($elem)) {
          $prop = [
            'uris' => [$prop => 1],  // normal direction
            'expand inverses' => TRUE,
          ];
        }
        
        if (empty($prop['uris'])) {
          throw new InvalidArgumentException('No URIs given for property.');
        } 

        // compute the inverse(s) if not given
        // TODO: magic numbers to constants
        if (!empty($prop['expand inverses'])) {
          foreach ($prop['uris'] as $uri => $direction) {
            if ($direction == 3) continue; // its own inverse => do nothing
            $inv = $this->getInverse($uri);
            if (!empty($inv)) {
              if (!isset($prop['uris'][$inv])) {
                // if prop does not exist, we add it with the opposite direction
                $prop['uris'][$this->getInverse($uri)] = $direction == 2 ? 1 : 2;
              } else {
                // if prop does exist, we or existing and new direction
                // making it possibly symmetric
                $prop['uris'][$this->getInverse($uri)] |= $direction;
              }
            }
          }
        }
        
        // variable for next indiv        
        $varPlus = "?$varInstPrefix" . ($varOffset + 1);
        $vars[$varPlus] = $varPlus;
  
        
        // generate triples for inverse and normal
        $tr = [];
        foreach ($prop['uris'] as $uri => $direction) {
          if ($direction & 1) {
            $tr[] = "$var <$uri> $varPlus . ";
          }
          if ($direction & 2) {
            $tr[] = "$varPlus <$uri> $var . ";
          }
        }
        if (count($tr) == 1) {
          $triples .= $tr[0];
        } else {
          $triples .= '{ { ' . join(' } UNION { ', $tr) . ' } }';
        }
        $triples .= "\n";
        
        // we update the last var here
        $var = $varPlus;

      }
      
      // we always increment the counter, even if a step defines its own name
      // this helps for more opacity
      $varOffset++;  

    } // end path while loop
    
    // add datatype property/ies if there
    if (isset($path['datatype_property'])) {
      
      $vars[$varDt] = $varDt;
      $props = $path['datatype_property'];
      
      if (!is_array($props)) {
        $props = [
          'uris' => [$props],
         ];
      }

      // add the triple(s)
      $tr = [];
      foreach ($props['uris'] as $prop) {
        $tr[] = "$var <$prop> $varDt .";
      }
      if (count($tr) == 1) {
        $triples .= $tr[0];
      } else {
        $triples .= '{ { ' . join(' } UNION { ', $tr) . ' } }';
      }
      $triples .= "\n";

      if (isset($options['search_dt'])) {
        $constraints .= $this->_buildSearchFilter($options['search_dt'], $varDt) . "\n";
      }

    } // end datatype prop
  
    // set order: we either order by 
    // - the variable set in order_var (and it exists)
    // - or the datatype variable (if it exists)
    // otherwise we ignore order option
    if (isset($options['order']) && $options['order'] != 'RAND' &&
        ((isset($options['order_var']) && isset($vars[$options['order_var']])) || isset($path['datatype_property']))
        ) {
      $orderVar = (isset($options['order_var']) && isset($vars[$options['order_var']])) ? $options['order_var'] : $varDt;
      $order .= "ORDER BY";
      $order .= $options['order'] . '(';
      if (isset($options['qualifier'])) {
        $order .= $options['qualifier'] . "($orderVar)";
      } else {
        $order .= $orderVar;
      }
      $order .= ')';
    }
    
    // set limit and offset
    if (!empty($options['limit'])) $limit .= 'LIMIT ' . $options['limit'];
    if (!empty($options['offset'])) $limit .= 'OFFSET ' . $options['offset'];

    // filter out vars that we don't want to have
    if (isset($options['vars'])) {
      $vars = array_intersect($vars, $options['vars']);
    }
    
    // return either a complete query as string or its parts as an array
    return empty($options['fields']) ? 
      $head . join(' ', $vars) . ' WHERE { ' . $triples . $constraints . '} ' . $order . $limit
      : [
        'head' => $head,
        'vars' => $vars,
        'triples' => $triples,
        'constraints' => $constraints,
        'order' => $order,
        'limit' => $limit,
      ];

  }

  
  /** Helper function that parses a search struct and builds a sparql filter
  * from it.
  *
  * The struct will be applied to exactly one variable. The variable must
  * contain a literal value. Search on URIs is not possible. See options[uris]
  * param of buildQuerySinglePath() if you need to restrict the URIs.
  *
  * @param search the search struct. It may be either
  *         a) an array list with possible values
  *         b) an assoc array with two keys
  *           'mode':   the logical or comparison operator to be used.
  *                     Currently supported: AND OR NOT = != < > CONTAINS REGEX
  *           'terms':  Applies to AND and OR. An array of search structs of
  *                     type b that is or'ed/and'ed
  *           'term':   Applies to all other operators. In case of a logical op
  *                     it is a search struct of type b that is applied to the
  *                     operator. In case of a comparison it is a string or
  *                     numeral that is compared to the variable.
  *
  * @param dtVar the name of the variable that is search upon.
  *         With leading '?'!
  *
  * @param depth internal parameter, should be omitted if called from outside
  *         this function
  *
  * @return a sparql statement, usually a FILTER statement
  */
  public function _buildSearchFilter(array $search, $dtVar, $depth = 0) {
    
    if (empty($search)) {

      return '';

    } elseif ($depth == 0 && isset($search['mode'])) {
      
      return "FILTER " . $this->_buildSearchFilter($search, $dtVar, 1);
        
    } elseif ($depth == 0 && !empty($search)) {

      // an easy case: we just search for a list of literals
      // we use the values construct as it may be faster and more readable
      $res = "VALUES $dtVar { ";
      foreach ($search as $t) {
        $res .= "'" . $this->escapeSparqlLiteral($t) . "' ";
      }
      $res .= "}";
      return $res;

    } elseif (isset($search['mode'])) {
      
      $mode = strtoupper($search['mode']);
      switch ($mode) {
        case 'AND':
        case 'OR':
          $res = [];
          $terms = $search['terms'];
          foreach ($terms as $term) {
            $res[] = $this->_buildSearchFilter($term, $dtVar, $depth + 1);
          }
          return '(' . join(" $mode ", $res) . ')';

        case 'NOT':
          $res = $this->_buildSearchFilter($search['term'], $dtVar, $depth + 1);
          return "( NOT $res )";
        
        // comparison of strings and numbers
        case '=':
        case '!=':
        case '<':
        case '>':
          $term = $search['term'];
          if (is_numeric($term)) {
            // TODO: how to cast to a number type in sparql?
            return "($dtVar $mode '" . $this->escapeSparqlLiteral($term) . "')";
          } else {
            return "(STR($dtVar) $mode " . $this->escapeSparqlLiteral($term) . ')';
          }
        case 'CONTAINS':
          // contains behaves like regex but we also have to escape the special
          // regex chars
          $term = $search['term'];
#          dpm(serialize($term), "term");
          return "(REGEX(STR($dtVar), '" . $this->escapeSparqlRegex($term, TRUE) . "'))";
        case 'REGEX':
          $term = $search['term'];
          return "(REGEX(STR($dtVar), '" . $this->escapeSparqlLiteral($term) . "'))";

        default:  
          throw new InvalidArgumentException("Unknown search operator: $mode");
      }

    }

    return '';

  }
  
  
  /** Computes the inverse of a property
  *  @param prop the property
  * @return the inverse or NULL if there is none. 
  *   In case of a symmetric property the property itself is returned
  * @author Martin Scholz
  */
  public function getInverse($prop) {
    // TODO
    return NULL;
  }
    

  /**
   * Lazy-instantiates a util rdf+sparql utility object
   */
  protected function rdfSparqlUtil() {
    if ($this->rdf_sparql_util === NULL) {
      $this->rdf_sparql_util = new RdfSparqlUtil();
    }
    return $this->rdf_sparql_util;
  }


  /**
   * @see \Drupal\wisski_salz\RdfSparqlUtil
   */
  public function escapeSparqlLiteral($literal, $escape_backslash = TRUE) {
    return $this->rdfSparqlUtil()->escapeSparqlLiteral($literal, $escape_backslash);
  }


  /**
   * @see \Drupal\wisski_salz\RdfSparqlUtil
   */
  public function escapeSparqlRegex($regex, $also_literal = FALSE) {
    return $this->rdfSparqlUtil()->escapeSparqlRegex($regex, $also_literal);
  }
  
  

  /** Gathers the quads that contain the given URIs in the given positions.
   *
   * @param uris an array containing the URIs. The value may also be a string
   *             containing a single URI. 
   * @param variables a string containing the triple/quad positions that shall
   *                  be considered for replacement. Possible values are a
   *                  concatenation of these four: g s p o.
   *                  NULL is the default and behaves like 'so'.
   * @param format a string specifying the return value.
   *               Possible values are:
   *               'count': Only the number of quads is returned.
   *               'quads':  an array of quads is returned where each quad is 
   *                         encoded as specified in the nquads format but 
   *                         without the trailing dot.
   *               'triples': an array of arrays is returned where the inner 
   *                          arrays contain triples encoded as in the ntriples
   *                          format but without a dot and the triples are
   *                          grouped by their graphs.
   *
   * @return array|int according to format parameter
   */
  public function getQuadsContainingUris($uris, $variables = NULL, $format = 'quads') {
    
    // make from_uris unique and delete to_uri from it
    $uris = (array) $uris; // make it an array 
    $uris = array_unique($uris);
#    dpm($uris, "uris");
    $variables = array_unique(str_split($variables));
    
    if (empty($uris) || empty($variables)) {
      return $format == 'count' ? 0 : array();
    }
    $uri_values = '<' . join('> <', $uris) . '>';

    // the sparql header differs for count from quads/triples
    // the where clause is the same
    $where_clauses = array();
    foreach ($variables as $v) {
      $where_clauses[$v] = 
        "  {\n" .
        "    VALUES ?$v { $uri_values }\n" .
        "    OPTIONAL { GRAPH ?g { ?s ?p ?o } }\n" . // without optional it returns nothing in case of one part in the union not returning anything
        "  }\n";
    }
    
    // filter if spo is bound - as it must not be in case of optional
    $where_clause = "WHERE {\n" . join("  UNION\n", $where_clauses) . ' FILTER ( bound(?s) ) . FILTER( bound(?p) ) . FILTER( bound(?o) ) }';
    
    if ($format == 'count') {
      $query = "SELECT (count(*) as ?c) $where_clause";
    } 
    else {
      $query = "SELECT ?g ?s ?p ?o $where_clause";
    }
#    dpm($query, "query");
    $result = $this->directQuery($query);
    
    // return value depends on $format
    if ($format == 'count') {
      return $result->current()->c->getValue();
    }
    elseif ($format == 'triples') {
      return $this->rdfSparqlUtil()->sparqlResultToNTriplesByGraph($result);
    } 
    else {
      return $this->rdfSparqlUtil()->sparqlResultToNQuads($result);
    } 

  }

  
  
  /** Updates URIs in all quads.
   *
   * @param from_uris an array of the original URIs. The value may also be a
   *                  string containing a single URI. 
   * @param to_uri a string containing the new URI
   * @param variables a string containing the triple/quad positions that shall
   *                  be considered for replacement. Possible values are a
   *                  concatenation of these four: g s p o.
   *                  The default is 'so'.
   * @param copy a boolean whether to copy or move the original quads, ie. 
   *             whether to perform a DELETE on the original quads
   *                  
   * @return TRUE on success, otherwise FALSE.
   */
  public function replaceUris(array $from_uris, $to_uri, $variables = 'so', $copy = FALSE) {
     
    // make from_uris unique and delete to_uri from it
    $from_uris = (array) $from_uris; // make it an array 
    $from_uris = array_flip($from_uris);
    if (isset($from_uris[$to_uri])) {
      unset($from_uris[$to_uri]);
    }
    $from_uris = array_flip($from_uris);

    $variables = array_unique(str_split($variables));
    
    if (empty($from_uris) || empty($variables)) {
      return TRUE;
    }
    if (empty($to_uri)) {
      return FALSE;
    }
    $from_uri_values = '<' . join('> <', $from_uris) . '>';
    
    // for each URI position we do a separate SPARQL update
    // TODO: make a separate update for g using ADD(+DROP)
    $updates = array();
    foreach ($variables as $v) {
      $delete = "GRAPH ?g { ?s ?p ?o }";
      $insert = str_replace("?$v", "<$to_uri>", "GRAPH ?g { ?s ?p ?o }");
      $where = "VALUES ?$v { $from_uri_values }\n  $delete";
      if ($copy) {
        $updates[] = "INSERT {  $insert\n}\nWHERE {\n  $where\n}";
      }
      else {
        $updates[] = "DELETE {\n  $delete\n}\nINSERT {  $insert\n}\nWHERE {\n  $where\n}";
      }
    }
    $update = join(";\n", $updates);

    $this->directUpdate($update);

    return TRUE;

  }


  /** Retrieves all URIs that match a certain pattern.
   *
   * @param pattern The pattern to match against. The pattern must be in Sparql
   *                regex syntax. The matching is case sensitive.
   * @param variables a string containing the triple/quad positions that shall
   *                  be considered for matching. Possible values are a
   *                  concatenation of these four: g s p o.
   *                  The default is 'gspo'.
   * @param operator the Sparql matching operator. Currently supported ops are
                     CONTAINS (default), STRSTARTS, and REGEX.
   *                  
   * @return array with keys and values being the matched URIs.
   */
  public function getMatchingUris($pattern, $variables = 'gspo', $operator = 'CONTAINS') {
    $variables = array_unique(str_split($variables));
    if (empty($variables)) return [];
    // the pattern must be escaped as it is embedded as literal.
    // as the caller knows it is a regex pattern, it is their responsibility
    // to escape special regex sequences with escapeSparqlRegex().
    $pattern = $this->escapeSparqlLiteral($pattern);
    // build the quads first: for every var position to check, we generate one
    // quad with the var replaced by x
    $quad_temp = "  GRAPH ?g { ?s ?p ?o } .";
    $quads = [];
    foreach ($variables as $v) {
      // we append the filter to avoid matching bnodes or literals
      $quads[] = '{ ' . str_replace("?$v", '?x', $quad_temp) . ' FILTER(isURI(?x)) }';
    }
    $quads = join("\n  UNION\n", $quads);
    // the final query
    $select  = "SELECT DISTINCT ?x WHERE {";
    $select .= "\n$quads\n";
    $operator = strtoupper($operator);
    if (in_array($operator, ['CONTAINS', 'STRSTARTS', 'REGEX'])) {
      $select .= "  FILTER ($operator(str(?x), \"$pattern\"))";
    }
    else {
      throw new \InvalidArgumentException("bad sparql operator: $operator");
    }
    $select .= "}";
    $result = $this->directQuery($select);
    // collect the matched uris
    $uris = [];
    foreach ($result as $row) {
      $uri = $row->x->getUri();
      $uris[$uri] = $uri;
    }  
    return $uris;
  }

}
