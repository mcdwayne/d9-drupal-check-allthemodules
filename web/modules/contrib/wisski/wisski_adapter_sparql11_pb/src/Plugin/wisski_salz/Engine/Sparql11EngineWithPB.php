<?php

/**
 * @file
 * Contains \Drupal\wisski_salz\Plugin\wisski_salz\Engine\Sparql11EngineWithPB.
 */

namespace Drupal\wisski_adapter_sparql11_pb\Plugin\wisski_salz\Engine;

use Drupal\Core\Form\FormStateInterface;
use Drupal\wisski_salz\Plugin\wisski_salz\Engine\Sparql11Engine;
use Drupal\wisski_pathbuilder\PathbuilderEngineInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\wisski_salz\AdapterHelper;

use Drupal\wisski_core\Entity\WisskiEntity;

use Drupal\wisski_adapter_sparql11_pb\Query\Query;
use \EasyRdf;

/**
 * Standard Sparql 1.1 endpoint adapter engine.
 *
 * @Engine(
 *   id = "sparql11_with_pb",
 *   name = @Translation("Sparql 1.1 With Pathbuilder"),
 *   description = @Translation("Provides access to a SPARQL 1.1 endpoint and is configurable via a Pathbuilder")
 * )
 */
class Sparql11EngineWithPB extends Sparql11Engine implements PathbuilderEngineInterface  {

  protected $allow_inverse_property_pattern;

  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'allow_inverse_property_pattern' => FALSE,
    ];
  }


  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {

    // this does not exist
    parent::setConfiguration($configuration);
    $this->allow_inverse_property_pattern = $this->configuration['allow_inverse_property_pattern'];
  }


  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return array(
      'allow_inverse_property_pattern' => $this->allow_inverse_property_pattern,
    ) + parent::getConfiguration();
  }





  /******************* BASIC Pathbuilder Support ***********************/

  /**
   * {@inheritdoc}
   */
  public function providesFastMode() {
    return TRUE;
  }
  
  /**
   * {@inheritdoc}
   */
  public function providesCacheMode() {
  
    return TRUE;
  }
  
  /**
   * returns TRUE if the cache is pre-computed and ready to use, FALSE otherwise
   */
  public function isCacheSet() {
    //see $this->doTheReasoning()
    // and $this->getPropertiesFromCache() / $this->getClassesFromCache
    //the rasoner sets all reasoning based caches i.e. it is sufficient to check, that one of them is set
    
    //if ($cache = \Drupal::cache()->get('wisski_reasoner_properties')) return TRUE;
    //return FALSE;
    
    return $this->isPrepared();
  }

  /**
   * {@inheritdoc}
   * returns the possible next steps in path creation, if $this->providesFastMode() returns TRUE then this
   * MUST react fast i.e. in the blink of an eye if $fast_mode = TRUE and it MUST return the complete set of options if $fast_mode=FALSE
   * otherwise it should ignore the $fast_mode parameter
   */  
  public function getPathAlternatives($history = [], $future = [],$fast_mode=FALSE,$empty_uri='empty') {

#    \Drupal::logger('WissKI path alternatives: '.($fast_mode ? 'fast mode' : "normal mode"))->debug('History: '.serialize($history)."\n".'Future: '.serialize($future));
    $search_properties = NULL;
    
    $last = NULL;
    if (!empty($history)) {
      $candidate = array_pop($history);
      $inv_sign = '';
      if ($this->allow_inverse_property_pattern && $candidate[0] == '^') {
        $candidate = substr($candidate, 1);
        $inv_sign = '^';
      }
      if ($candidate === $empty_uri) {
//        \Drupal::logger('WissKI path alternatives')->error('Not a valid URI: "'.$candidate.'"');
        //as a fallback we assume that the full history is given so that every second step is a property
        //we have already popped one element, so count($history) is even when we need a property
        $search_properties = (0 === count($history) % 2);
      }
      elseif ($this->isValidUri('<'.$candidate.'>')) {
        $last = "$inv_sign$candidate";
        if ($this->isAProperty($candidate) === FALSE) $search_properties = TRUE; 
      } else {
        if (WISSKI_DEVEL) \Drupal::logger('WissKI path alternatives')->debug('invalid URI '.$candidate);
        return array();
      }
    }
    
    $next = NULL;
    if (!empty($future)) {
      $candidate = array_shift($future);
      $inv_sign = '';
      if ($this->allow_inverse_property_pattern && $candidate[0] == '^') {
        $candidate = substr($candidate, 1);
        $inv_sign = '^';
      }
      if ($candidate !== $empty_uri) {
        if ($this->isValidUri('<'.$candidate.'>')) {
          $next = "$inv_sign$candidate";
          if ($search_properties === NULL) {
            if ($this->isAProperty($candidate) === FALSE) $search_properties = TRUE;
          } elseif ($this->isAProperty($candidate) === $search_properties) {
            drupal_set_message('History and Future are inconsistent','error');
          }
        } else {
          if (WISSKI_DEVEL) \Drupal::logger('WissKI path alternatives')->debug('invalid URI '.$candidate);
          return array();
        }
      }
    }
#  dpm(serialize($search_properties), "sp");    
#    \Drupal::logger('WissKI next '.($search_properties ? 'properties' : 'classes'))->debug('Last: '.$last.', Next: '.$next);
    //$search_properties is TRUE if and only if last and next are valid URIs and no owl:Class-es
    if ($search_properties) {
      $return = $this->nextProperties($last,$next,$fast_mode);
    } else {
      $return = $this->nextClasses($last,$next,$fast_mode);
    }

#    dpm(func_get_args()+array('result'=>$return),__FUNCTION__);
    return $return;
  }
  
  /**
   * @{inheritdoc}
   */
//  public function getPathAlternatives($history = [], $future = []) {
//
//  \Drupal::logger('WissKI SPARQL Client')->debug("normal mode");
//    if (empty($history) && empty($future)) {
//      
//      return $this->getClasses();
//
//    } elseif (!empty($history)) {
//      
//      $last = array_pop($history);
//      $next = empty($future) ? NULL : $future[0];
//
//      if ($this->isaProperty($last)) {
//        return $this->nextClasses($last, $next);
//      } else {
//        return $this->nextProperties($last, $next);
//      }
//    } elseif (!empty($future)) {
//      $next = $future[0];
//      if ($this->isaProperty($next))
//        return $this->getClasses();
//      else
//        return $this->getProperties();
//    } else {
//      return [];
//    }
//
//    
//  }
  
  /**
   * @{inheritdoc}
   */
  public function getPrimitiveMapping($step) {
    
    // in case of properties we can skip this
    if ($step[0] == '^') return array();

    $info = [];

    // this might need to be adjusted for other standards than rdf/owl
    $query = 
      "SELECT DISTINCT ?property "
      ."WHERE { { GRAPH ?g1 {"
        ."?property a owl:DatatypeProperty . } . } . "
        ."{ GRAPH ?g3 { ?property rdfs:subPropertyOf* ?d_subprop } } . { GRAPH ?g4 { ?d_subprop rdfs:domain ?d_superclass . } . } . "
        ."{ GRAPH ?g5 { <$step> rdfs:subClassOf* ?d_superclass. } . } . }"
      ;
/*      
      // By Mark: TODO: Please check this. I have absolutely
      // no idea what this does, I just copied it from below
      // and I really really hope that Dorian did know what it
      // does and it will work forever.      

      $query .= 
        "{"
          ."{?d_def_prop rdfs:domain ?d_def_class.}"
          ." UNION "
          ."{"
            ."?d_def_prop owl:inverseOf ?inv. "
            ."?inv rdfs:range ?d_def_class. "
          ."}"
        ."} "
        ."<$step> rdfs:subClassOf* ?d_def_class. "
        ."{"
          ."{?d_def_prop rdfs:subPropertyOf* ?property.}"
          ." UNION "
          ."{ "
            ."?property rdfs:subPropertyOf+ ?d_def_prop. "
            ." FILTER NOT EXISTS {"
              ."{ "
                ."?mid_prop rdfs:subPropertyOf+ ?d_def_prop. "
                ."?property rdfs:subPropertyOf* ?mid_prop. "
              ."}"
              ."{"
                ."{?mid_prop rdfs:domain ?any_domain.}"
                ." UNION "
                ."{ "
                  ."?mid_prop owl:inverseOf ?mid_inv. "
                  ."?mid_inv rdfs:range ?any_range. "
                ."}"
              ."}"
            ."}"
          ."}"
        ."}}}";
*/
    $result = $this->directQuery($query);
#    dpm($query, 'res');

    if (count($result) == 0) return array();
    
    $output = array();
    foreach ($result as $obj) {
      $prop = $obj->property->getUri();
      $output[$prop] = $prop;
    }
    uksort($output,'strnatcasecmp');
    return $output;
  } 

  public function getStepInfo($step, $history = [], $future = []) {
    
    $info = [];

    $query = "SELECT DISTINCT ?label WHERE { GRAPH ?g { <$step> <http://www.w3.org/2000/01/rdf-schema#label> ?label . } } LIMIT 1";
    $result = $this->directQuery($query);
    if (count($result) > 0) {
      $info['label'] = $result[0]->label->getValue();
    }

    $query = "SELECT DISTINCT ?comment WHERE { GRAPH ?g { <$step> <http://www.w3.org/2000/01/rdf-schema#comment> ?comment . } } LIMIT 1";
    $result = $this->directQuery($query);
    if (count($result) > 0) {
      $info['comment'] = $result[0]->comment->getValue();
    }


    return $info;
  }

  public function isaProperty($p) {

    //you cannot use GRAPH in an ASK query
    //return $this->directQuery("ASK { GRAPH ?g { <$p> a owl:ObjectProperty . } }")->isTrue(); 

    //we obviously have to solve it via SELECT
    $result = $this->directQuery(
      "SELECT * WHERE {"
        ."VALUES ?x {<$p>} "
        ."GRAPH ?g { "
          ."{ ?x a owl:ObjectProperty . } UNION { ?x a rdf:Property.}"
        ."}"
      ."}"   
    );   
    return $result->numRows() > 0;
  } 
  
  public function getClasses() {
  
    $out = $this->retrieve('classes','class');
    if (!empty($out)) return $out;
    $query = "SELECT DISTINCT ?class WHERE { "
        ."{ GRAPH ?g1 {?class a owl:Class} }"
        ."UNION "
        ."{ GRAPH ?g2 {?class a rdfs:Class} }"
        ."UNION "
        ."{ GRAPH ?g3 {?ind a ?class. ?class a ?type} }"
        ." . FILTER(!isBlank(?class))"
        ."} ";  
    $result = $this->directQuery($query);
    
    if (count($result) > 0) {
      $out = array();
      foreach ($result as $obj) {
        $class = $obj->class->getUri();
        $out[$class] = $class;
      }
      uksort($out,'strnatcasecmp');
      return $out;
    }
    return FALSE;
  }
  
  public function getProperties() {
  
    $out = $this->retrieve('properties','property');
    if (!empty($out)) return $out;
    $query = "SELECT DISTINCT ?property WHERE { "
        ."{ GRAPH ?g1 {?property a owl:ObjectProperty .} } "
        ."UNION "
        ."{ GRAPH ?g2 {?property a rdf:Property .} } "
      ." . "
      ."FILTER(!isBlank(?property))"
    ."} ";  
    $result = $this->directQuery($query);
    
    if (count($result) > 0) {
      $out = array();
      foreach ($result as $obj) {
        $class = $obj->property->getUri();
        $out[$class] = $class;
      }
      uksort($out,'strnatcasecmp');
      return $out;
    }
    return FALSE;
  }

  public function nextProperties($class=NULL,$class_after = NULL,$fast_mode=FALSE) {

    if (!isset($class) && !isset($class_after)) return $this->getProperties();
#    \Drupal::logger(__METHOD__)->debug('class: '.$class.', class_after: '.$class_after);

    $output = $this->getPropertiesFromCache($class,$class_after);

    if ($output === FALSE) {
      //drupal_set_message('none in cache');
      $output = $this->getPropertiesFromStore($class,$class_after,$fast_mode);
    }

    if ($this->allow_inverse_property_pattern) {
      // we get all the inverse properties by reverting class before and class
      // after and adding a "^"
      $output2 = $this->getPropertiesFromCache($class_after,$class);
      if ($output2 === FALSE) {
        //drupal_set_message('none in cache');
        $output2 = $this->getPropertiesFromStore($class_after,$class,$fast_mode);
      }
      foreach ($output2 as $p) {
        $output["^$p"] = "^$p";
      }
    }

    uksort($output,'strnatcasecmp');

    return $output;
  }

  /**
   * returns an array of properties for which domain and/or range match the input
   * @param an associative array with keys 'domain' and/or 'range'
   * @return array of matching properties | FALSE if there was no cache data
   */
  protected function getPropertiesFromCache($class,$class_after = NULL) {

/* cache version
    $dom_properties = array();
    $cid = 'wisski_reasoner_reverse_domains';
    if ($cache = \Drupal::cache()->get($cid)) {
      $dom_properties = $cache->data[$class]?:array();
    } else return FALSE;
    $rng_properties = array();
    if (isset($class_after)) {
      $cid = 'wisski_reasoner_reverse_ranges';
      if ($cache = \Drupal::cache()->get($cid)) {
        $rng_properties = $cache->data[$class_after]?:array();
      } else return FALSE;
    } else return $dom_properties;
    return array_intersect_key($dom_properties,$rng_properties);
    */

    //DB version
    $dom_properties = $this->retrieve('domains','property','class',$class);
    if (isset($class_after)) $rng_properties = $this->retrieve('ranges','property','class',$class_after);
    else return $dom_properties;
    return array_intersect_key($dom_properties,$rng_properties);
  }
    

  /** Gets all graphs that are considered containing ontology information / 
   * triples by this engine.
   *
   * This defaults to the graphs retrieved from the triple store but may also
   * be overridden by the user.
   * 
   * @return an array of graph URIs
   */
  public function getOntologyGraphs() {
    $graph_uris = $this->ontology_graphs;
    if (empty($graph_uris) || ( isset($graph_uris[0]) && empty($graph_uris[0])) ) {
      $graph_uris = $this->queryOntologyGraphsFromStore();
    }
    return $graph_uris;
  } 
  

  /** Gets all graphs that contain an ontology
   * 
   * @return an array of graph URIs
   */
  public function queryOntologyGraphsFromStore() {
    $graph_uris = array();
    $query = "SELECT ?g WHERE { GRAPH ?g { ?ont a owl:Ontology} }";
    $result = $this->directQuery($query);
    foreach ($result as $obj) {
      $uri = $obj->g->getUri();
      $graph_uris[$uri] = $uri;
    }
    return $graph_uris;
  }


  public function getPropertiesFromStore($class=NULL,$class_after = NULL,$fast_mode=FALSE) {
    
     if ($fast_mode) {
      // the fast mode will only gather properties that are declared as 
      // domain/range directly. This will only return an incomplete set of
      // properties unless we have resp. reasoning capabilities
      
      $query = "SELECT DISTINCT ?property WHERE { \n";
      if (isset($class)) $query .= "  GRAPH ?g3 { ?property rdfs:domain <$class>. }\n";
      if (isset($class_after)) $query .= "  GRAPH ?g4 { ?property rdfs:range <$class_after>. }\n";
      $query .= "  { { GRAPH ?g1 { ?property a owl:ObjectProperty. } } UNION { GRAPH ?g2 { ?property a rdf:Property. } } }\n";
      $query .= "}";

    } 
    else {
      // the complete mode makes more sophisticated queries that also return
      // properties that are declared domain/range indirectly, i.e. somewhere
      // appropriate within the class and property hierarchies.
      // This is way more inefficient.

      // We build up a default graph by using the FROM GRAPH clause.
      // Use all the graphs that contain ontology/tbox triples.
      // We have to use FROM GRAPH in complete mode as the
      // */+ modifiers will not work on multiple graphs if combined with a
      // GRAPH ?g {} statement. And we have to use GRAPH ?g {} for fuseki 
      // support...
      // If there are no ontology graphs, we use the default graph
      $ontology_graphs = $this->getOntologyGraphs();
      $from_graphs = " "; 
      if (!empty($ontology_graphs)) {
        $from_graphs = "\nFROM <" . join(">\nFROM <", $ontology_graphs) . ">\n";
      }
      $query = "SELECT DISTINCT ?property${from_graphs}WHERE {\n";
    
      if (isset($class)) {
        $query .= 
           "  <$class> rdfs:subClassOf* ?d_def_class.\n"
          ."  {\n"
          ."    { ?d_def_prop rdfs:domain ?d_def_class. }\n"
          ."    UNION\n"
          ."    {\n"
          ."       ?d_def_prop owl:inverseOf ?inv.\n"
          ."       ?inv rdfs:range ?d_def_class.\n"
          ."    }\n"
          ."  }\n"
          ."  {\n"
          ."    { ?d_def_prop rdfs:subPropertyOf* ?property. }\n"
          ."    UNION\n"
          ."    {\n"
          ."      ?property rdfs:subPropertyOf+ ?d_def_prop.\n"
          ."      FILTER NOT EXISTS {\n"
          ."        ?mid_prop rdfs:subPropertyOf+ ?d_def_prop.\n"
          ."        ?property rdfs:subPropertyOf* ?mid_prop.\n"
          ."        {\n"
          ."          { ?mid_prop rdfs:domain ?any_domain. }\n"
          ."          UNION\n"
          ."          {\n"
          ."            ?mid_prop owl:inverseOf ?mid_inv.\n"
          ."            ?mid_inv rdfs:range ?any_range.\n"
          ."          }\n"
          ."        }\n"
          ."      }\n"
          ."    }\n"
          ."  }\n";
      }
      if (isset($class_after)) {
        $query .= 
           "  <$class_after> rdfs:subClassOf* ?r_def_class.\n"
          ."  {\n"
          ."    { ?r_def_prop rdfs:range ?r_def_class. }\n"
          ."    UNION\n"
          ."    {\n"
          ."      ?r_def_prop owl:inverseOf ?inv.\n"
          ."      ?inv rdfs:domain ?inv.\n"
          ."    }\n"
          ."  }\n"
          ."  {\n"
          ."    { ?r_def_prop rdfs:subPropertyOf* ?property. }\n"
          ."    UNION\n"
          ."    {\n"
          ."      ?property rdfs:subPropertyOf+ ?r_def_prop.\n"
          ."      FILTER NOT EXISTS {\n"
          ."        ?mid_prop rdfs:subPropertyOf+ ?r_def_prop.\n"
          ."        ?property rdfs:subPropertyOf* ?mid_prop.\n"
          ."        {\n"
          ."          { ?mid_prop rdfs:range ?any_range. }\n"
          ."          UNION\n"
          ."          {\n"
          ."            ?mid_prop owl:inverseOf ?mid_inv.\n"
          ."            ?mid_inv rdfs:domain ?any_domain.\n"
          ."          }\n"
          ."        }\n"
          ."      }\n"
          ."    }\n"
          ."  }\n";
      }  
    $query .= "  { { ?property a owl:ObjectProperty. } UNION { ?property a rdf:Property. } }\n";
    $query .= "}";
/*      if (isset($class)) {
        $query .= 
           "  GRAPH ?g8 { <$class> rdfs:subClassOf* ?d_def_class. }\n"
          ."  {\n"
          ."    { GRAPH ?g5 { ?d_def_prop rdfs:domain ?d_def_class.}}\n"
          ."    UNION\n"
          ."    {\n"
          ."       GRAPH ?g6 { ?d_def_prop owl:inverseOf ?inv. }\n"
          ."       GRAPH ?g7 { ?inv rdfs:range ?d_def_class. }\n"
          ."    }\n"
          ."  }\n"
          ."  {\n"
          ."    { GRAPH ?g9 { ?d_def_prop rdfs:subPropertyOf* ?property.}}\n"
          ."    UNION\n"
          ."    {\n"
          ."      GRAPH ?g10 { ?property rdfs:subPropertyOf+ ?d_def_prop. }\n"
          ."      FILTER NOT EXISTS {\n"
          ."        {\n"
          ."          GRAPH ?g11 { ?mid_prop rdfs:subPropertyOf+ ?d_def_prop. }\n"
          ."          GRAPH ?g12 { ?property rdfs:subPropertyOf* ?mid_prop. }\n"
          ."        }\n"
          ."        {\n"
          ."          { GRAPH ?g13 { ?mid_prop rdfs:domain ?any_domain.}}\n"
          ."          UNION\n"
          ."          {\n"
          ."            GRAPH ?g14 { ?mid_prop owl:inverseOf ?mid_inv. }\n"
          ."            GRAPH ?g15 { ?mid_inv rdfs:range ?any_range. }\n"
          ."          }\n"
          ."        }\n"
          ."      }\n"
          ."    }\n"
          ."  }\n";
      }
      if (isset($class_after)) {
        $query .= 
           "  GRAPH ?g19 { <$class_after> rdfs:subClassOf* ?r_def_class.}\n"
          ."  {\n"
          ."    { GRAPH ?g16 { ?r_def_prop rdfs:range ?r_def_class.} }\n"
          ."    UNION\n"
          ."    {\n"
          ."      GRAPH ?g17 { ?r_def_prop owl:inverseOf ?inv. }\n"
          ."      GRAPH ?g18 { ?inv rdfs:domain ?inv. }\n"
          ."    }\n"
          ."  }\n"
          ."  {\n"
          ."    { GRAPH ?g20 { ?r_def_prop rdfs:subPropertyOf* ?property.} }\n"
          ."    UNION\n"
          ."    {\n"
          ."      GRAPH ?g21 { ?property rdfs:subPropertyOf+ ?r_def_prop. }\n"
          ."      FILTER NOT EXISTS {\n"
          ."        {\n"
          ."          GRAPH ?g22 { ?mid_prop rdfs:subPropertyOf+ ?r_def_prop. }\n"
          ."          GRAPH ?g23 { ?property rdfs:subPropertyOf* ?mid_prop. }\n"
          ."        }\n"
          ."        {\n"
          ."          { GRAPH ?g24 { ?mid_prop rdfs:range ?any_range. } }\n"
          ."          UNION\n"
          ."          {\n"
          ."            GRAPH ?g25 { ?mid_prop owl:inverseOf ?mid_inv. }\n"
          ."            GRAPH ?g26 { ?mid_inv rdfs:domain ?any_domain. }\n"
          ."          }\n"
          ."        }\n"
          ."      }\n"
          ."    }\n"
          ."  }\n";
        $query .= "  { { GRAPH ?g1 { ?property a owl:ObjectProperty. } } UNION { GRAPH ?g2 { ?property a rdf:Property. } } }\n";
        $query .= "}";
      }  */
    }

    $result = $this->directQuery($query);
    $output = array();
    foreach ($result as $obj) {
      $prop = $obj->property->getUri();
      $output[$prop] = $prop;
    }

    return $output;
  }

  public function nextClasses($property=NULL,$property_after = NULL,$fast_mode=FALSE) {
    if (!isset($property) && !isset($property_after)) {
      return $this->getClasses();
    }
    elseif (isset($property) && $property[0] == '^') {
      if (isset($property_after)) {
        if ($property_after[0] == '^') {
          return $this->nextClasses(substr($property_after, 1), substr($property, 1), $fast_mode);
        }
        else {
          $classes1 = $this->nextClasses(NULL, $property_after, $fast_mode);
          $classes2 = $this->nextClasses(NULL, substr($property, 1), $fast_mode);
          return array_intersect($classes1, $classes2);
        }
      }
      else {  // $property_after == NULL
        return $this->nextClasses(NULL, substr($property, 1), $fast_mode);
      }
    }
    elseif (isset($property_after) && $property_after[0] == '^') {
      // remember: $property[0] != '^' (otherwise we would be in branch above!)
      if (isset($property)) {
        $classes1 = $this->nextClasses($property, NULL, $fast_mode);
        $classes2 = $this->nextClasses(substr($property_after, 1), NULL, $fast_mode);
        return array_intersect($classes1, $classes2);
      }
      else {
        return $this->nextClasses(substr($property_after, 1), NULL, $fast_mode);
      }
    }
    else {
#    \Drupal::logger(__METHOD__)->debug('property: '.$property.', property_after: '.$property_after);
      $output = $this->getClassesFromCache($property,$property_after);
#    dpm($output, "output");
      if ($output === FALSE) {
        //drupal_set_message('none in cache');
        $output = $this->getClassesFromStore($property,$property_after,$fast_mode);
      }
      uksort($output,'strnatcasecmp');
      return $output;
    }
  }

  protected function getClassesFromCache($property,$property_after = NULL) {

  /* cache version
    $dom_classes = array();
    $cid = 'wisski_reasoner_ranges';
    if ($cache = \Drupal::cache()->get($cid)) {
      $rng_classes = $cache->data[$property]?:array();
    } else return FALSE;
    $dom_classes = array();
    if (isset($property_after)) {
      $cid = 'wisski_reasoner_domains';
      if ($cache = \Drupal::cache()->get($cid)) {
        $dom_classes = $cache->data[$property_after]?:array();
      } else return FALSE;
    } else return $rng_classes;
    return array_intersect_key($rng_classes,$dom_classes);
    */
#    dpm(func_get_args()+array('result'=>$return),__FUNCTION__);    
    //DB version
    $rng_classes = $this->retrieve('ranges','class','property',$property);
#    dpm($rng_classes, "ranges");
    if (isset($property_after)) $dom_classes = $this->retrieve('domains','class','property',$property_after);
    else return $rng_classes;
    return array_intersect_key($rng_classes,$dom_classes);
  }

  public function getClassesFromStore($property=NULL,$property_after = NULL,$fast_mode=FALSE) {
  
    $query = "SELECT DISTINCT ?class WHERE {  {"
      ."{ {?class a owl:Class. } UNION { ?class a rdfs:Class.} }"
      ;
    if ($fast_mode) {  
      if (isset($property)) $query .= "<$property> rdfs:range ?class. ";
      if (isset($property_after)) $query .= "<$property_after> rdfs:domain ?class. ";
    } else {
      if (isset($property)) {
        $query .= "<$property> rdfs:subPropertyOf* ?r_super_prop. "
          ."?r_super_prop rdfs:range ?r_super_class. "
          ."FILTER NOT EXISTS { "
            ."?r_sub_prop rdfs:subPropertyOf+ ?r_super_prop. "
            ."<$property> rdfs:subPropertyOf* ?r_sub_prop. "
            ."?r_sub_prop rdfs:range ?r_any_class. "
          ."} "
          ."?class rdfs:subClassOf* ?r_super_class. ";
      }
      if (isset($property_after)) {
        $query .= "<$property_after> rdfs:subPropertyOf* ?d_super_prop. "
          ."?d_super_prop rdfs:domain ?d_super_class. "
          ."FILTER NOT EXISTS { "
            ."?d_sub_prop rdfs:subPropertyOf+ ?d_super_prop. "
            ."<$property_after> rdfs:subPropertyOf* ?d_sub_prop. "
            ."?d_sub_prop rdfs:domain ?d_any_class. "
          ."} "
          ."?class rdfs:subClassOf* ?d_super_class. ";
      }  
    }
    $query .= "} }";

#    drupal_set_message(serialize($query));
    $result = $this->directQuery($query);
    
    if (count($result) == 0) return array();

    $output = array();
    foreach ($result as $obj) {
      $class = $obj->class->getUri();
      $output[$class] = $class;
    }
    natsort($output);
    return $output;

  }

  /******************* End of BASIC Pathbuilder Support ***********************/

  // copy from yaml-adapter - likes camels.
  
  private $entity_info;

  /*
   * Load the image data for a given entity id
   * @return an array of values?
   *
   */
  public function getImagesForEntityId($entityid, $bundleid) {
    $pbs = $this->getPbsForThis();

    $entityid = $this->getDrupalId($entityid);
    
    $ret = array();
      
    foreach($pbs as $pb) {
#    drupal_set_message("yay!" . $entityid . " and " . $bundleid);
    
      $groups = $pb->getGroupsForBundle($bundleid);
    
      foreach($groups as $group) {
        $paths = $pb->getImagePathIDsForGroup($group->id());
    
#      drupal_set_message("paths: " . serialize($paths));
            
        foreach($paths as $pathid) {
    
          $path = \Drupal\wisski_pathbuilder\Entity\WisskiPathEntity::load($pathid);
        
#        drupal_set_message(serialize($path));
        
#        drupal_set_message("thing: " . serialize($this->pathToReturnValue($path->getPathArray(), $path->getDatatypeProperty(), $entityid, 0, NULL, 0)));
        
          // this has to be an absulte path - otherwise subgroup images won't load.
          $new_ret = $this->pathToReturnValue($path, $pb, $entityid, 0, NULL, FALSE);
#          if (!empty($new_ret)) dpm($pb->id().' '.$pathid.' '.$entitid,'News');
          $ret = array_merge($ret, $new_ret);
          
        } 
      }
    }    
#    drupal_set_message("returning: " . serialize($ret));
    //dpm($ret,__FUNCTION__);
    return $ret;
  }
  
  /**
   * @see getBundleIdsForUri()
   *
   *
   */
  public function getBundleIdsForEntityId($entityid) {
    
    if (is_numeric($entityid)) {
      $uri = $this->getUriForDrupalId($entityid, FALSE);    
    } else {
      $uri = $entityid;
    }
    $url = parse_url($uri);

    if(!empty($url["scheme"]))
      return $this->getBundleIdsForUri($uri);
    else {
      //it is possible, that we got an entity URI instead of an entity ID here, so try that one first
      $url = parse_url($entityid);
      if (!empty($url['scheme'])) {
        $uri = $entityid;
        return $this->getBundleIdsForUri($uri);
      }
    }
    
#    wsmlog(__METHOD__ . ": could not find URI for entityid '$entityid'", 'warning');
    drupal_set_message("Could not find URI for entityid '$entityid'", 'error');
    return array();

  }
        
  /** Get the IDs of all bundles that can be used for the given instance.
   *
   * It returns only bundles defined via pathbuilders that are associated with
   * this engine/adapter.
   * The URI must be without prefix but with namespace. Prefixed or abbreviated
   * URIs will not be handled correctly! If you have an entity id or a
   * non-standardized URI you may want to use getBundleIdsForEntityId().
   *
   * @param uri a URI of the instance
   * @return an array of bundle ids
   *
   */
  public function getBundleIdsForUri($uri) {
    $pbs = $this->getPbsForThis();
    
    $query = "SELECT ?class WHERE { GRAPH ?g { <" . $uri . "> a ?class } }";
    
    $result = $this->directQuery($query);

    $out = array();
    foreach($result as $thing) {
      $uri_to_find = $thing->class->getUri();
  
      $topbundles = array();
      $nontopbundles = array();

      foreach($pbs as $pb) {
        $bundles = $pb->getAllBundleIdsAboutUri($uri_to_find);

        if(empty($bundles))
          continue;
          
        list($tmptopbundles, $tmpnontopbundles) = $bundles;
 
        $topbundles = array_merge($topbundles, $tmptopbundles);
        $nontopbundles = array_merge($nontopbundles, $tmpnontopbundles);
      }
#      dpm($topbundles);
      
      foreach($nontopbundles as $key => $value) {
        $out = array_merge(array($key => $value), $out);
      }
      
      foreach($topbundles as $key => $value) {
        $out = array_merge(array($key => $value), $out);
      }
      
    }

#    dpm($out, "out");

    return $out;    
    
  }
  
  /**
   * Gets the array part to get from one subgroup to another
   *
   */
  public function getClearGroupArray($group, $pb) {
    // we have to modify the group-array in case of jumps
    // from one subgroup to another
    // if you have a groups with grouppaths:
    // g1: x0
    // g2: x0 y0 x1
    // g3: x0 y0 x1 y1 x2 y2 x3
    // then the way from g2 to g3 is x1 y1 x2 y2 x3
    // this should be calculated here.
    $patharraytoget = $group->getPathArray();
    $allpbpaths = $pb->getPbPaths();
    $pbarray = $allpbpaths[$group->id()];

    // do some error handling    
    if(!$group->isGroup()) {
      drupal_set_message("getClearGroupArray called with something that is not a group: " . serialize($group), "error");
      return;
    }
        
    // if we are a top group, won't do anything.
    if($pbarray['parent'] > 0) {
        
      // first we have to calculate our own ClearPathArray
      $clearGroupArray = $this->getClearPathArray($group, $pb);
    
      // then we have to get our parents array
      $pbparentarray = $allpbpaths[$pbarray['parent']];
      
      $parentpath = \Drupal\wisski_pathbuilder\Entity\WisskiPathEntity::load($pbarray["parent"]);
      
      // if there is nothing, do nothing!
      // I am unsure if that ever could occur
      if(empty($parentpath))
        return;
      
      // -1 because we don't want to cut our own concept
      $parentcnt = count($parentpath->getPathArray())-1;

#      drupal_set_message("before cut: " . serialize($patharraytoget));
      
      for($i=0; $i<$parentcnt; $i++) {
        unset($patharraytoget[$i]);
      }
      
#      drupal_set_message("in between: " . serialize($patharraytoget));
      
      $patharraytoget = array_values($patharraytoget);
      
#      drupal_set_message("cga: " . serialize($clearGroupArray));
      
      $max = count($patharraytoget);
      
      // we have to cut away everything that is in $cleargrouparray
      // so we take the whole length and subtract that as a starting point
      // and go up from there
      for($i=(count($patharraytoget)-count($clearGroupArray)+1);$i<$max;$i++)
        unset($patharraytoget[$i]);
      
#      drupal_set_message("after cut: " . serialize($patharraytoget));
      
      $patharraytoget = array_values($patharraytoget);      
      
    }
    return $patharraytoget;    
  }
  
  /**
   * This is Obsolete!
   *
   * Gets the common part of a group or path
   * that is clean from subgroup-fragments
   */
  public function getClearPathArray($path, $pb) {
    // We have to modify the path-array in case of subgroups.
    // Usually if we have a subgroup path x0 y0 x1 we have to skip x0 y0 in
    // the paths of the group.
    if (!is_object($path) || !is_object($pb)) {
      drupal_set_message('getClearPathArray found no path or no pathbuilder. Error!', 'error');
      return array();
    }
    
    $patharraytoget = $path->getPathArray();
    $allpbpaths = $pb->getPbPaths();
    $pbarray = $allpbpaths[$path->id()];
    
#    dpm($pbarray, "pbarray!");
    // is it in a group?
    if(!empty($pbarray['parent'])) {

      $pbparentarray = $allpbpaths[$pbarray['parent']];
      
      // how many path-parts are in the pb-parent?
      $parentpath = \Drupal\wisski_pathbuilder\Entity\WisskiPathEntity::load($pbarray["parent"]);
            
      // if there is nothing, do nothing!
      // I am unsure if that ever could occur
      if(empty($parentpath))
        return;
      
      
      // we have to handle groups other than paths
      if($path->isGroup()) {
        // so this is a subgroup?
        // in this case we have to strip the path of the parent and
        // one object property from our path
        $pathcnt = count($parentpath->getPathArray()) +1;

        // strip exactly that.
        for($i=0; $i< $pathcnt; $i++) {
          unset($patharraytoget[$i]);
        }        
      
      } else {
        // this is no subgroup, it is a path
#        if(!empty($pbparentarray['parent'])) {
          // only do something if it is a path in a subgroup, not in a main group  
          
#          $parentparentpath = \Drupal\wisski_pathbuilder\Entity\WisskiPathEntity::load($pbparentarray["parent"]);
          
          // in that case we have to remove the subgroup-part, however minus one, as it is the       
#          $pathcnt = count($parentpath->getPathArray()) - count($this->getClearPathArray($parentpath, $pb));
          $pathcnt = count($parentpath->getPathArray()) - 1; #count($parentparentpath->getPathArray());        

#          dpm($pathcnt, "pathcnt");
#          dpm($parentpath->getPathArray(), "pa!");
        
          for($i=0; $i< $pathcnt; $i++) {
            unset($patharraytoget[$i]);
          }
#        }
      }
    }
          
#          drupal_set_message("parent is: " . serialize($pbparentarray));
          
#          drupal_set_message("I am getting: " . serialize($patharraytoget));
          
    $patharraytoget = array_values($patharraytoget);
    
    return $patharraytoget;
  }

  /**
   * Gets the bundle and loads every individual in the TS
   * and returns an array of ids if there is something...
   *
   */ 
  public function loadIndividualsForBundle($bundleid, $pathbuilder, $limit = NULL, $offset = NULL, $count = FALSE, $conditions = FALSE) {

    $conds = array();
    // see if we have any conditions
    foreach($conditions as $cond) {
      if($cond["field"] != "bundle") {
        // get pb entries
        $pbentries = $pathbuilder->getPbEntriesForFid($cond["field"]);
        
        if(empty($pbentries))
          continue;
        
        $path = \Drupal\wisski_pathbuilder\Entity\WisskiPathEntity::load($pbentries['id']);
        
        if(empty($path))
          continue;
        
        $conds[] = $path;
      }
    }

    // build the query
    if(!empty($count))
      $query = "SELECT (COUNT(?x0) as ?cnt) WHERE {";
    else
      $query = "SELECT ?x0 WHERE {";
    

    if(empty($conds)) {
      
      // there should be someone asking for more than one...
      $groups = $pathbuilder->getGroupsForBundle($bundleid);
      
      
      // no group defined in this pb - return   
      if(empty($groups)) {
        if ($count) return 0;
        return array();
      }

      // for now simply take the first one
      // in future: iterate here!
      // @TODO!
      $group = $groups[0];
    
      // get the group 
      // this does not work for subgroups! do it otherwise!
      #$grouppath = $group->getPathArray();    
#      $grouppath = $this->getClearPathArray($group, $pathbuilder);
      $grouppath = $pathbuilder->getRelativePath($group, FALSE);
                         
      foreach($grouppath as $key => $pathpart) {
        if($key % 2 == 0)
          $query .= " ?x" . $key . " a <". $pathpart . "> . ";
        else
          $query .= " ?x" . ($key-1) . " <" . $pathpart . "> ?x" . ($key+1) . " . "; 
      }
    } else {
      foreach($conds as $path) {
        $query .= $this->generateTriplesForPath($pathbuilder, $path, '', NULL, NULL, 0, 0, FALSE);
      }
    }

    $query .= "}";
    
    if(is_null($limit) == FALSE && is_null($offset) == FALSE && empty($count))
      $query .= " LIMIT $limit OFFSET $offset ";
     
#    drupal_set_message("query: " . serialize($query) . " and " . microtime());
    
#    return;
    //dpm($query,__FUNCTION__.' '.$this->adapterId());
    // ask for the query

    $result = $this->directQuery($query);

    $outarr = array();

    // for now simply take the first element
    // later on we need names here!
    foreach($result as $thing) {

      // if it is a count query, return the integer      
      if(!empty($count)) {
        //dpm($thing,'Count Thing');
        return $thing->cnt->getValue();
      }
      
      $uri = $thing->x0->dumpValue("text");
      
      #$uri = str_replace('/','\\',$uri);
      // this is no uri anymore - rename this variable.
      
      $uriname = $this->getDrupalId($uri);
      
      // store the bundleid to the bundle-cache as it might be important
      // for subsequent queries.      
      $pathbuilder->setBundleIdForEntityId($uriname, $bundleid);

      $outarr[$uriname] = array('eid' => $uriname, 'bundle' => $bundleid, 'name' => $uri);
    }
    //dpm($outarr, "outarr");

    #    return;

    if (empty($outarr) && $count) return 0;
    return $outarr;
  }

  public function loadEntity($id) {    
    // simply give back something without thinking about it.
    $out['eid'] = $id;
        
    return $out;
  }
  
  /**
   * This is deprecated and unfunctional
   */
  public function loadMultipleEntities($ids = NULL) {
    drupal_set_message("I may not be called: loadMultipleEntities. ", "error");
    return;
  }
    
  /**
   * @inheritdoc
   */
  public function hasEntity($entity_id) {
  
    $uri = $this->getUriForDrupalId($entity_id, FALSE);

    $out = NULL;
    
    if($uri)
      $out = $this->checkUriExists($uri);

    return $out;
  }
  
  /**
   * The elemental data aggregation function
   * fetches the data for display purpose
   */
  public function pathToReturnValue($path, $pb, $eid = NULL, $position = 0, $main_property = NULL, $relative = TRUE) {
#    drupal_set_message("I got: $eid " . serialize($path));
#$tmpt1 = microtime(TRUE);            
    if(empty($path)) {
      drupal_set_message("No path supplied to ptr. This is evil.", "error");
      return array();
    }

    if(!$path->isGroup())
      $primitive = $path->getDatatypeProperty();
    else
      $primitive = NULL;
      
    $disamb = $path->getDisamb();
    
    // also
    if($disamb > 0)
      $disamb = ($disamb-1)*2;
    else
      $disamb = NULL;

    $sparql = "SELECT DISTINCT ";
    
    if($relative) {
      $starting_position = count($path->getPathArray()) - count($pb->getRelativePath($path));
    } else {
      $starting_position = $position;
    }

    // in case of disamb we contradict the theory below.
    if(!is_null($disamb)) { //&& $disamb === $starting_position) {
      $sparql .= "?x" . $disamb . " ";
    }
    
    // $starting_position+2 because we can omit x0 in this place - it will always be replaced
    // by the eid of this thing here.
    /*
    // We try to be more precise than this approach as it is rather costly...
    for($i = $starting_position+2; $i <= count($path->getPathArray()); $i+=2) {
      $sparql .= "?x" . $i . " ";
    }
    */
    
    // get the queried one.
    $name = 'x' . (count($path->getPathArray())-1);
    
    // in case of primitives it is not the above one but "out"
    if(!empty($primitive) && $primitive != "empty")
      $sparql .= "?out ";
    else if($name != "x" . $disamb) // but in all other cases it is the above one.
      $sparql .= "?" . $name . " ";
          
    $sparql .= "WHERE { ";

    if(!empty($eid)) {
      // rename to uri
#$tmpt5 = microtime(TRUE);            
      $eid = $this->getUriForDrupalId($eid, TRUE);
#$tmpt6 = microtime(TRUE);            

      // if the path is a group it has to be a subgroup and thus entity reference.
      if($path->isGroup()) {
      
        // it is the same as field - so entity_reference is basic shit here
        $sparql .= $this->generateTriplesForPath($pb, $path, '', $eid, NULL, 0,  ($starting_position/2), FALSE, NULL, 'entity_reference', $relative);
      }
      else {
        $sparql .= $this->generateTriplesForPath($pb, $path, '', $eid, NULL, 0, ($starting_position/2), FALSE, NULL, 'field', $relative);
      }
#$tmpt7 = microtime(TRUE);            

    } else {
      drupal_set_message("No EID for data. Error. ", 'error');
    }


    // if disamb should be in the query we can bind it.
    if ($starting_position === $disamb) {
      // TODO/WARNING by Martin: I think this does not work! It should be VALUES {} instead!
      // afaik BIND only works for unbound variables.
      // I do not fix it now as i don't get the idea behind why this is done
      // here! When can starting and disamb position be the same and why and
      // what is expected to happen?
      $sparql .= " BIND( <" . $eid . "> AS ?x" . $disamb . ") . ";
    }

    $sparql .= " } ";
    
#    drupal_set_message(serialize($sparql) . " on " . serialize($this));
#    dpm(microtime(), "mic1");
#$tmpt2 = microtime(TRUE);            
    $result = $this->directQuery($sparql);
#$tmpt3 = microtime(TRUE);  
#    dpm(microtime(), "mic2");          
#    drupal_set_message(serialize($result));

    $out = array();
    foreach($result as $thing) {
      
      // if $thing is just a true or not true statement
      // TODO: by Martin: is this really working and if so in which case?
      // Also, what does true/false statement mean? we know that it's not an ASK query
      if($thing == new \StdClass()) {
        drupal_set_message("This is ultra-evil!", "error");
        continue;
      }
      
#      $name = 'x' . (count($patharray)-1);
#      $name = 'x' . (count($path->getPathArray())-1);
      if(!empty($primitive) && $primitive != "empty") {
        if(empty($main_property)) {
          $out[] = $thing->out->getValue();
        } else {
          
          $outvalue = $thing->out->getValue();

          // special case: DateTime... render this as normal value for now.
          if(is_a($outvalue, "DateTime")) {
            $outvalue = (string)$outvalue->format('Y-m-d\TH:i:s.u');;
          }
          
#          if($path->isGroup() && $main_property == "target_id")
#            $outvalue = $this->getDrupalId($outvalue);
#          dpm($outvalue . " and " . serialize($disamb));          
          if(is_null($disamb) == TRUE) {
            $out[] = array($main_property => $outvalue);
          }
          else {
            $disambname = 'x'.$disamb;
            if(!isset($thing->{$disambname})) {
              $out[] = array($main_property => $outvalue);
            }
            else {
              $out[] = array($main_property => $outvalue, 'wisskiDisamb' => $thing->{$disambname}->dumpValue("text"));
            }
          }
        }
      } else {
        if(empty($main_property)) {
          $out[] = $thing->{$name}->dumpValue("text");
        } else { 
          $outvalue = $thing->{$name}->dumpValue("text");
          
#          if($path->isGroup() && $main_property == "target_id")
#            $outvalue = $this->getDrupalId($outvalue);
        
          if(is_null($disamb) == TRUE)
            $out[] = array($main_property => $outvalue);
          else {
            $disambname = 'x'.$disamb;
            if(!isset($thing->{$disambname}))
              $out[] = array($main_property => $outvalue);
            else
              $out[] = array($main_property => $outvalue, 'wisskiDisamb' => $thing->{$disambname}->dumpValue("text"));
          }
        }
      }
    }
#$tmpt4 = microtime(TRUE);            
#\Drupal::logger('WissKI Adapter ptrv')->debug($pb->id() . " " . $path->id() ."::". htmlentities(\Drupal\Core\Serialization\Yaml::encode( [$tmpt4-$tmpt1,$tmpt7-$tmpt6, $tmpt5-$tmpt1, $tmpt6-$tmpt5, $tmpt2-$tmpt7, $tmpt3-$tmpt2, $tmpt4-$tmpt3])));
#dpm([$tmpt4-$tmpt1,$tmpt5-$tmpt1, $tmpt6-$tmpt5, $tmpt2-$tmpt6, $tmpt3-$tmpt2, $tmpt4-$tmpt3], $pb->id() . " " . $path->id());

    return $out;
    
  }
  
  /**
   * @inheritdoc
   */
  public function loadFieldValues(array $entity_ids = NULL, array $field_ids = NULL, $bundleid_in = NULL, $language = LanguageInterface::LANGCODE_DEFAULT) {

    // tricky thing here is that the entity_ids that are coming in typically
    // are somewhere from a store. In case of rdf it is easy - they are uris.
    // In case of csv or something it is more tricky. So I don't wan't to 
    // simply go to the store and tell it "give me the bundle of this".
    // The field ids come in handy here - fields are typically attached
    // to a bundle anyway. so I just get the bundle from there. I think it is
    // rather stupid that this function does not load the field values per 
    // bundle - it is implicitely anyway like that.
    // 
    // so I ignore everything and just target the field_ids that are mapped to
    // paths in the pathbuilder.

#    drupal_set_message("I am asked for " . serialize($entity_ids) . " and fields: " . serialize($field_ids));
$tsa = ['start'=>microtime(true)];
    $pb_ids = array();
    $pb_man = \Drupal::service('wisski_pathbuilder.manager');
    $bundle_infos = $pb_man->getPbsUsingBundle($bundleid_in);
#dpm([$this->adapterId(), $bundle_infos]);
    foreach($bundle_infos as $bundle_info) {
      if ($bundle_info['adapter_id'] == $this->adapterId()) {
        $pb_id = $bundle_info['pb_id'];
        $pb_ids[$pb_id] = $pb_id;
      }
    }
    $pbs = \Drupal\wisski_pathbuilder\Entity\WisskiPathbuilderEntity::loadMultiple($pb_ids);
$tsa['pbs'] = join(' ', $pb_ids);

    $out = array();
        
    // get the adapterid that was loaded
    // haha, this is the engine-id...
    //$adapterid = $this->getConfiguration()['id'];
        
    foreach($pbs as $pb) {
      
      // if we find any data, we set this to true.
      $found_any_data = FALSE;
        
      foreach($field_ids as $fkey => $fieldid) {  
        #drupal_set_message("for field " . $fieldid . " with bundle " . $bundleid_in . " I've got " . serialize($this->loadPropertyValuesForField($fieldid, array(), $entity_ids, $bundleid_in, $language)));
$tmpc=microtime(true);
        $got = $this->loadPropertyValuesForField($fieldid, array(), $entity_ids, $bundleid_in, $language);
$tsa[$pb->id()][$fieldid] = (microtime(TRUE)-$tmpc);

#        drupal_set_message("I've got: " . serialize($got));
        
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
        
#        drupal_set_message("out after got: " . serialize($out));
      }
      
#      drupal_set_message("out is empty? " . serialize($out) . serialize(empty($out)));
      
      // @TODO this is a hack.
      // if we did not find any data we unset this part so we don't return anything
      // however this might be evil in cases of edit or something...
      if(empty($out))
        return array();
    }
    
#    drupal_set_message("I return: " . serialize($out));
$tsa['ende'] = microtime(TRUE)-$tsa['start'];
#\Drupal::logger('Sparql Adapter')->debug(str_replace("\n", '<br/>', htmlentities("loadFieldValues:\n".\Drupal\Core\Serialization\Yaml::encode($tsa))));
    
    return $out;

  }

  /**
   * @inheritdoc
   * The Yaml-Adapter cannot handle field properties, we insist on field values being the main property
   */
  public function loadPropertyValuesForField($field_id, array $property_ids, array $entity_ids = NULL, $bundleid_in = NULL, $language = LanguageInterface::LANGCODE_DEFAULT) {
#    drupal_set_message("a1: " . microtime());
#    drupal_set_message("fun: " . serialize(func_get_args()));
#    drupal_set_message("2");
#   
#    drupal_set_message("muha: " . serialize($field_id));
    $field_storage_config = \Drupal\field\Entity\FieldStorageConfig::loadByName('wisski_individual', $field_id);#->getItemDefinition()->mainPropertyName();
    // What does it mean if the field storage config is empty?
    //=>  it means it is a basic field

    $out = array();

    if (empty($field_storage_config)) {
      // this is the case for base fields (fields that are defined directly in
      // the entity class)
      
      // we can handle base-fields in advance.
      if($field_id == "bundle" || $field_id == "eid") {
        foreach($entity_ids as $eid) {

          if($field_id == "bundle" && !empty($bundleid_in)) {
            $out[$eid]["bundle"] = array($bundleid_in);
            continue;
          }
#        drupal_set_message("I am asked for fids: " . serialize($field_ids));
  
          if($field_id == "eid") {
            $out[$eid][$field_id] = array($eid);
            continue;
          }
      
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
          if($field_id == "bundle") {
            
            if(!empty($bundleid_in)) {
              $out[$eid]['bundle'] = array($bundleid_in);
              continue;
            }

            // get all the bundles for the eid from us
            $bundles = $this->getBundleIdsForEntityId($eid);
          
            if(!empty($bundles)) {
              // if there is only one, we take that one.
              #foreach($bundles as $bundle) {
              $out[$eid]['bundle'] = array_values($bundles);
              #  break;
              #}
              continue;
            } else {
              // if there is none return NULL
              $out[$eid]['bundle'] = NULL;              
              continue;
            }
          }
        }
      }
      return $out;
    }
    

    if(!empty($field_storage_config)) {
      $main_property = $field_storage_config->getMainPropertyName();
      $target_type = $field_storage_config->getSetting("target_type");
    }

    if(!empty($field_id) && empty($bundleid_in)) {
      drupal_set_message("$field_id was queried but no bundle given.", "error");
      return;
    }
    
    // make an entity query for all relevant pbs with this adapter.
    $relevant_pb_ids = \Drupal::service('entity.query')
      ->get('wisski_pathbuilder')
      ->condition('adapter', $this->adapterId())->execute();

    
    // this approach will be not fast enough in the future...
    // the pbs have to have a better mapping of where and how to find fields
    $pbs = \Drupal\wisski_pathbuilder\Entity\WisskiPathbuilderEntity::loadMultiple($relevant_pb_ids);
    
    // what we loaded once we don't want to load twice.
    $adapter_cache = array();
        
    // go through all relevant pbs        
    foreach($pbs as $pb) {

      // get the pbarray for this field
      $pbarray = $pb->getPbEntriesForFid($field_id);
          
      // if there is no data about this path - how did we get here in the first place?
      // fields not in sync with pb?
      if(empty($pbarray["id"]))
        continue;

      $path = \Drupal\wisski_pathbuilder\Entity\WisskiPathEntity::load($pbarray["id"]);

      // if there is no path we can skip that
      if(empty($path))
        continue;
                          
      // if we find any data, we set this to true.
      $found_any_data = FALSE;
      
      foreach($entity_ids as $eid) {
        // every other field is an array, we guess
        // this might be wrong... cardinality?          
        if(!isset($out[$eid][$field_id]))
          $out[$eid][$field_id] = array();

        // if this is question for a subgroup - handle it otherwise
        if($pbarray['parent'] > 0 && $path->isGroup()) {
          // @TODO: ueberarbeiten
#          drupal_set_message("danger zone!");
          $tmp = $this->pathToReturnValue($path, $pb, $eid, 0, $main_property);            

          foreach($tmp as $key => $item) {
            $tmp[$key]["target_id"] = $this->getDrupalId($item["target_id"]);
          }
            
          $out[$eid][$field_id] = array_merge($out[$eid][$field_id], $tmp);
        } else {
          // it is a field?
          // get the parentid
          $parid = $pbarray["parent"];
            
          // get the parent (the group the path belongs to) to get the common group path
          $par = \Drupal\wisski_pathbuilder\Entity\WisskiPathEntity::load($parid);

          // if there is no parent it is a ungrouped path... who asks for this?
          if(empty($par)) {
            drupal_set_message("Path " . $path->getName() . " with id " . $path->id() . " has no parent.", "error");
            continue;
          }

          // get the clear path array            
          $clearPathArray = $pb->getRelativePath($path, FALSE);
#$tmpt=microtime(true);
          $tmp = $this->pathToReturnValue($path, $pb, $eid, count($path->getPathArray()) - count($clearPathArray), $main_property);            
#\Drupal::logger('WissKI Import lpvff')->debug((microtime(TRUE)-$tmpt). ": $field_id ".$path->id());

          if ($main_property == 'target_id') {
              
            $oldtmp = $tmp;
            
            // special case for files - do not ask for a uri.
            if($target_type == "file") {
              foreach($tmp as $key => $item) {
                $tmp[$key]["target_id"] = $item["target_id"];
                $tmp[$key]["original_target_id"] = $item["target_id"];
              }
            } else {
              
              foreach($tmp as $key => $item) {
                $tmp[$key]["original_target_id"] = $item["target_id"];
                $tmp[$key]["target_id"] = $this->getDrupalId(isset($item['wisskiDisamb']) ? $item["wisskiDisamb"] : $item["target_id"]);
              }
            }

          }

          // merge it manually as recursive merge does not work properly in case of multi arrays.
          if ($main_property == 'target_id') {
            foreach($tmp as $key => $item) {
              $skip = false;
            // check if the value is already there...
              foreach($out[$eid][$field_id] as $field) {
                if($field["target_id"] == $item["target_id"]) {
                  $skip = TRUE;
                  break;
                }
              }
              
              // if we don't skip, add it via array_merge...
              if(!$skip) 
                $out[$eid][$field_id] = array_merge($out[$eid][$field_id], $tmp);
            
            }
          } else { // "normal" behaviour
//          dpm($tmp, "merging with " . serialize($out[$eid][$field_id]));
            $out[$eid][$field_id] = array_merge($out[$eid][$field_id], $tmp);
          }
        }
        
        if(empty($out[$eid][$field_id]))
          unset($out[$eid]);
      }
    }

    return $out;
  }
  
  
  public function getQueryObject(EntityTypeInterface $entity_type,$condition,array $namespaces) {
    //do NOT copy this to parent, this is namespace dependent  
    return new Query($entity_type,$condition,$namespaces,$this);
  }
  
  public function deleteOldFieldValue($entity_id, $fieldid, $value, $pb, $count = 0, $mainprop = FALSE) {
 #   drupal_set_message("entity_id: " . $entity_id . " field id: " . $fieldid . " value " . serialize($value));
    // get the pb-entry for the field
    // this is a hack and will break if there are several for one field
    $pbarray = $pb->getPbEntriesForFid($fieldid);

    $field_storage_config = \Drupal\field\Entity\FieldStorageConfig::loadByName('wisski_individual', $fieldid);
    
    // store the target type to see if it references to a file for special handling
    $target_type = NULL;
    
    if(!empty($field_storage_config)) {
      $target_type = $field_storage_config->getSetting("target_type");
    }

    // if there is absolutely nothing, we don't delete something.
    if(empty($pbarray)) {
      return;
    }

    if(!isset($pbarray['id'])) {
      drupal_set_message("Danger zone: in PB " . $pb->getName() . " field $fieldid was queried with value $value in deleteOldFieldValue, but the path with array " . serialize($pbarray) . " has no id.", "warning.");
      return;
    }
    
    // so what? delete nothing?!
    if(empty($value)) {
      return;
    }
    
    // get path/field-related config
    // and do some checks to ensure that we are acting on a
    // well configured field
    $path = \Drupal\wisski_pathbuilder\Entity\WisskiPathEntity::load($pbarray['id']);
    if(empty($path)) {
      return;
    }

    // this is an important distinction till now!
    // TODO: maybe we can combine reference delete and value delete
    // in case of file we don't need to have a disamb and it is not a real reference.
    // however we have to change the object.
    $is_reference = (($mainprop == 'target_id' && !empty($path->getDisamb()))  || $path->isGroup() || ($pbarray['fieldtype'] == 'entity_reference'));
#    dpm($is_reference, "main");

#    $image_value = NULL;

    // this is the special case for files... we have to adjust the object here.
    // the url of the file is directly used as value to have some more meaning in the
    // triple store than just an entity id
    // this might have sideeffects... we will see :)
    if($target_type == "file" && $mainprop == 'target_id') {
      if( is_numeric($value)) 
        $value = $this->getUriForDrupalId($value, FALSE);
    }

#    dpm($value, "value");
#dpm($pbarray, "pbarr");
#dpm($mainprop, "main");
#    dpm(serialize($is_reference), "is ref");

    if ($is_reference) {
      // delete a reference
      // this differs from normal field values as there is no literal
      // and the entity has to be matched to the uri
      
      $subject_uri = $this->getUriForDrupalId($entity_id, FALSE);
#      dpm($subject_uri, "subj");
      if (empty($subject_uri)) {
        // the adapter doesn't know of this entity. some other adapter needs
        // to handle it and we can skip it.
        return;
      }
      $subject_uris = array($subject_uri);
      
      // value is the Drupal id of the referenced entity
      $object_uri = $this->getUriForDrupalId($value, FALSE);
#      dpm($object_uri, "obj");
      if (empty($object_uri)) {
        // the adapter doesn't know of this entity. some other adapter needs
        // to handle it and we can skip it.
        return;
      }

      $path_array = $path->getPathArray();

      if (count($path_array) < 3) {
        // This should never occur as it would mean that someone is deleting a
        // reference on a path with no triples!
        drupal_set_message("Bad path: trying to delete a ref with a too short path.", 'error');
        return;
      }
      elseif (count($path_array) == 3) {
        // we have the spacial case where subject and object uri are directly
        // linked in a triple <subj> <prop> <obj> / <obj> <inverse> <subj>.
        // So we know which triples to delete and can skip costly search for 
        // the right triple.

        // nothing to do!
      }
      else {
        // in all other cases we need to readjust the subject uri to cut the 
        // right triples.

        $pathcnt = 0;
        $parent = \Drupal\wisski_pathbuilder\Entity\WisskiPathEntity::load($pbarray['parent']);
        if (empty($parent)) {
          // lonesome path!?
        }
        else {
          // we cannot use clearPathArray() here as path is a group and 
          // the function would chop off too much
          // TODO: check if we can use the new *relative* methods
          $parent_path_array = $parent->getPathArray();
          $pathcnt = (count($parent_path_array) + 1) / 2;
        }

        // we have to set disamb manually to the last instance
        // otherwise generateTriplesForPath() won't produce right triples
        if($path->isGroup()) { // only do this for groups - for fields we have to handle this otherwise.
          $disamb = (count($path_array) + 1) / 2;
        
          // the var that interests us is the one before disamb.
          // substract 2 as the disamb count starts from 1 whereas vars start from 0!
          // in W8, the x increases by 2!
          $subject_var = "x" . (($disamb - 2) * 2);

          // build up a select query that get us 
          $select  = "SELECT DISTINCT ?$subject_var WHERE {";
          $select .= $this->generateTriplesForPath($pb, $path, "", $subject_uri, $object_uri, $disamb, $pathcnt, FALSE, NULL, 'entity_reference');
          $select .= "}";

#          dpm($select, "select");
          
          $result = $this->directQuery($select);

          if ($result->numRows() == 0) {
            // there is no relation any more. has been deleted before!?
            return;
          }
#ddl(array($disamb, $subject_var, $select,$result, $result->numRows()), 'delete disamb select');

          // reset subjects
          $subject_uris = array();
          foreach ($result as $row) {
            $subject_uris[] = $row->{$subject_var}->getUri();
          }
        } else { // this is the case for the entity-reference fields that are not made by wisski
          $subject_uris = array($subject_uri);
        }

      }
            
#      $deltriples = $this->generateTriplesForPath($pb, $path, $object_uri, $subject_uri, NULL, $path->getDisamb(), $pathcnt-1, FALSE, NULL, 'normal');
      
      // if it is a entity reference we take that before the disamb!
      if(!$path->isGroup() && $is_reference && $path->getDisamb())     
        $prop = $path_array[(($path->getDisamb()-1) * 2) - 1];
      else #if($path->isGroup())
        $prop = $path_array[count($path_array) - 2];

      $inverse = $this->getInverseProperty($prop);
      $delete  = "DELETE DATA {\n";
      foreach ($subject_uris as $subject_uri) {
        $delete .= "  <$subject_uri> <$prop> <$object_uri> .\n";
        $delete .= "  <$object_uri> <$inverse> <$subject_uri> .\n";
      }
      $delete .= ' }';
#      dpm($delete, "sparql");

      $result = $this->directUpdate($delete);    

    } // end reference branch
    else {  // no reference
    
      $subject_uri = $this->getUriForDrupalId($entity_id, FALSE);
      $starting_position = $pb->getRelativeStartingPosition($path, TRUE);
      $clearPathArray = $pb->getRelativePath($path, TRUE);
          
      // delete normal field value
      $sparql = "SELECT DISTINCT ";
      
      for($i=($starting_position*2); $i <= count($path->getPathArray()); $i+=2) {
        $sparql .= "?x" . $i . " ";
      }
      
      // I am unsure about this.
      $sparql .= "?out ";
            
      $sparql .= "WHERE { ";

      // I am unsure if this is correct
      // probably it needs to be relative - but I am unsure
      //$triples = $this->generateTriplesForPath($pb, $path, $value, $eid, NULL, 0, $diff, FALSE);
      // make a query without the value - this is necessary
      // because we have to think of the weight.
      $triples = $this->generateTriplesForPath($pb, $path, '', $subject_uri, NULL, 0, $starting_position, FALSE);
      
      $sparql .= $triples;
      
      $sparql .= " }";
#      dpm($sparql, "sparql");
      
      $result = $this->directQuery($sparql);

      $outarray = array();
#      dpm($result, "result");
#      dpm($count, "count");

      $loc_count = 0;
      $break = FALSE;
      
      $position = NULL;
      $the_thing = NULL;
      
      foreach($result as $key => $thing) {
        
        $image_value = NULL;
        
#        dpm($target_type, "tt");
#        dpm($mainprop, "mp");
        // special hack for images which might be received via uri.
        if($target_type == "file" && $mainprop == 'target_id') {

#          dpm("hacking...");

          $loc = NULL;
          $fid = \Drupal::entityManager()->getStorage('wisski_individual')->getFileId($thing->out, $loc);
#          dpm($fid, "fid");
          $public = \Drupal::entityManager()->getStorage('wisski_individual')->getPublicUrlFromFileId($fid);
          
          $image_value = $public;
          
        }
        
#        dpm($image_value, "img");
#        dpm($value, "val");
        
        // Easy case - it is at the "normal position"
        if($key === $count && ( $thing->out == $value || $image_value == $value)) {
          $the_thing = $thing;
          $position = $count;
          break;
        }
        
        // not so easy case - it is somewhere else 
        if($thing->out == $value) {
          $position = $key;
          $the_thing = $thing;
          if($key >= $count)
            break;
        }
      }
            
      if(is_null($position)) {
        drupal_set_message($this->t(
            "For path %name (%id): Could not find old value '@v' and thus could not delete it.", 
            array(
              '%name' => $path->getName(),
              '%id' => $path->id(),
              '@v' => $value
            )
          ),
          "error"
        );
        return;
      }
      
      
      /* We cannot use DELETE DATA as we do not know the graph(s) of the triple.
      * The code below would only delete the triple if it is in the default
      * graph. we cannot omit the graph as fuseki needs it.
      * so we instead make a DELETE WHERE update and leave the graph unspecified.
      // for fuseki we need graph
      $delete  = "DELETE DATA { GRAPH <".$this->getDefaultDataGraphUri()."> {";

#      drupal_set_message("cpa: " . serialize($clearPathArray));

      // the datatype-property is not directly connected to the group-part
      if(count($clearPathArray) >= 3) {
        $prop = array_values($clearPathArray)[1];
        $inverse = $this->getInverseProperty($prop);

        $name = "x" . ($starting_position * 2 +2);

        $object_uri = $the_thing->{$name}->getUri();

        $delete .= "  <$subject_uri> <$prop> <$object_uri> .\n";
        $delete .= "  <$object_uri> <$inverse> <$subject_uri> .\n";

      }
      else {
        $primitive = $path->getDatatypeProperty();
        
        if(!empty($primitive)) {
          if(!empty($value)) {
            $value = $this->escapeSparqlLiteral($value);
            // Evil: no datatype or lang check!
            $delete .= "  <$subject_uri> <$primitive> '$value' .\n";
#dpm([$primitive, $value, $fieldid,$delete], __METHOD__.__LINE__);
          }
          else {
            drupal_set_message($this->t(
                "Path %name (%id) has primitive but no value given.",
                array(
                  '%name' => $path->getName(),
                  '%id' => $path->id()
                )
              ),
              "error"
            );
            return; 
          }
        }
      }
      
      $delete .= ' }}';
      */

      $delete_clause = "DELETE {\n  GRAPH ?g {\n";
      $where_clause = "WHERE {\n  GRAPH ?g {\n";
      
      // we have to distinguish between whether to delete a datatype prop
      // or an object prop.
      // first object prop
      if(count($clearPathArray) >= 3) {
        // the datatype-property is not directly connected to the group-part
        $prop = array_values($clearPathArray)[1];
        $inverse = $this->getInverseProperty($prop);
        $name = "x" . ($starting_position * 2 +2);
        $object_uri = $the_thing->{$name}->getUri();
        $delete_clause .= "    <$subject_uri> <$prop> <$object_uri> .\n";
        $delete_clause .= "    <$object_uri> <$inverse> <$subject_uri> .\n";
        $where_clause  .= "    {  <$subject_uri> <$prop> <$object_uri> . }\n";
        $where_clause  .= "    UNION\n";
        $where_clause  .= "    {  <$object_uri> <$inverse> <$subject_uri> . }\n";
      }
      // now datatype prop
      else {
        $primitive = $path->getDatatypeProperty();
        
        if(!empty($primitive)) {
          if(!empty($value)) {
            $escaped_value = $this->escapeSparqlLiteral($value);
            // Evil: no datatype or lang check!
            $delete_clause .= "    <$subject_uri> <$primitive> ?out .\n";
            $where_clause  .= "    <$subject_uri> <$primitive> ?out .\n    FILTER (STR(?out) = '$escaped_value')\n";
          }
          else {
            drupal_set_message($this->t(
                "Path %name (%id) has primitive but no value given.",
                array(
                  '%name' => $path->getName(),
                  '%id' => $path->id()
                )
              ),
              "error"
            );
            return; 
          }
        }
      }
      
      // assemble the clauses
      $delete = "$delete_clause  }\n}\n$where_clause  }\n}";
#      dpm(htmlentities($delete), "delete!");      
      $result = $this->directUpdate($delete);

    }

  }

  /**
   * Delete an entity
   * @param $entity an entity object
   * @return True or false if it did not work
   */
  public function deleteEntity($entity) {
    $eid = $entity->id();
    
    if(empty($eid)) {
      drupal_set_message("This entity could not be deleted as it has no eid.", "error");
      return;
    }
    
    $pbs = \Drupal\wisski_pathbuilder\Entity\WisskiPathbuilderEntity::loadMultiple();
    
    //if there is an eid we try to get the entity URI form cache
    //if there is none $uri will be FALSE
    $uri = $this->getUriForDrupalId($eid, FALSE);
    
#    $sparql = "DELETE { GRAPH <" . $this->getDefaultDataGraphUri() . "> { ?s ?p ?o } } " . 
#              "WHERE { { GRAPH <" . $this->getDefaultDataGraphUri() . "> { ?s ?p ?o . FILTER ( <$uri> = ?s ) } } " .
#              "UNION { GRAPH <" . $this->getDefaultDataGraphUri() . "> { ?s ?p ?o . FILTER ( <$uri> = ?o ) } } }";
    // we can't use the default graph here as the uri may also occur in other graphs
    $sparql = "DELETE { GRAPH ?g { ?s ?p ?o } } " . 
              "WHERE { { GRAPH ?g { ?s ?p ?o . FILTER ( <$uri> = ?s ) } } " .
              "UNION { GRAPH ?g { ?s ?p ?o . FILTER ( <$uri> = ?o ) } } }";
    #\Drupal::logger('WissKIsaveProcess')->debug('sparql deleting: ' . htmlentities($sparql));
    
    $result = $this->directUpdate($sparql);

    return $result;
  
  }

  /**
   * Create a new entity
   * @param $entity an entity object
   * @param $entity_id the eid to be set for the entity, if NULL and $entity dowes not have an eid, we will try to create one
   * @return the Entity ID
   */
  public function createEntity($entity,$entity_id=NULL) {
#    dpm("create was called");
    #$uri = $this->getUri($this->getDefaultDataGraphUri());
#    dpm(func_get_args(),__FUNCTION__);
#    \Drupal::logger('WissKIsaveProcess')->debug(__METHOD__ . " with values: " . serialize(func_get_args()));
        
    $bundleid = $entity->bundle();

    $pbs = \Drupal\wisski_pathbuilder\Entity\WisskiPathbuilderEntity::loadMultiple();
    
    $out = array();
    
    //might be empty, but we can use it later
    $eid = $entity->id() ? : $entity_id;
    $uri = NULL;
    //dpm($eid,$entity_id);
    //if there is an eid we try to get the entity URI form cache
    //if there is none $uri will be FALSE
    if (!empty($eid)) $uri = $this->getUriForDrupalId($eid, TRUE);
    else $uri = $this->getUri($this->getDefaultDataGraphUri());
    // get the adapterid that was loaded
    // haha, this is the engine-id...
    //$adapterid = $this->getConfiguration()['id'];

#    \Drupal::logger('WissKIsaveProcess')->debug(__METHOD__ . " with values: " . serialize(func_get_args()) . " gets id: " . $eid . " and uri: " . $uri);
        
    foreach($pbs as $pb) {
      //drupal_set_message("a2: " . microtime());
      // if we have no adapter for this pb it may go home.
      if(empty($pb->getAdapterId()))
        continue;
        
      $adapter = \Drupal\wisski_salz\Entity\Adapter::load($pb->getAdapterId());

      // if we have not adapter, we may go home, too
      if(empty($adapter))
        continue;
      
      // if he didn't ask for us...    
      if($this->adapterId() !== $adapter->id())
        continue;
      
      //dpm('I can create',$adapter->id());
      $groups = $pb->getGroupsForBundle($bundleid);

      // for now simply take the first one.    
      if ($groups = current($groups)) {
        
        $triples = $this->generateTriplesForPath($pb, $groups, '', $uri, NULL, 0, ((count($groups->getPathArray())-1)/2), TRUE, NULL, 'group_creation');
        //dpm(array('eid'=>$eid,'uri'=>$uri,'group'=>$groups->getPathArray()[0],'result'=>$triples),'generateTriplesForPath');
        
        $sparql = "INSERT DATA { GRAPH <" . $this->getDefaultDataGraphUri() . "> { " . $triples . " } } ";
#        \Drupal::logger('WissKIsaveProcess')->debug('sparql writing in create: ' . htmlentities($sparql));
#        dpm($sparql, "group creation");  
        $result = $this->directUpdate($sparql);
    
        if (empty($uri)) {
        
          // first adapter to write will create a uri for an unknown entity
          $uri = explode(" ", $triples, 2);
      
          $uri = substr($uri[0], 1, -1);  
        }
      }     
    }
#    dpm($groups, "bundle");
        
#    $entity->set('id',$uri);

    if (empty($eid)) {
      $eid = $this->getDrupalId($uri);
    }
#    dpm($eid,$adapter->id().' made ID');
    $entity->set('eid',$eid);
#    "INSERT INTO { GRAPH <" . $this->getDefaultDataGraphUri() . "> { " 
    return $eid;
  }

  public function getUri($prefix) {
    return uniqid($prefix);
  }
  
  /**
   * Generate the triple part for the statements (excluding any Select/Insert or
   * whatever). This should be used for any pattern generation. Everything else
   * is evil.
   *
   * @param $pb	a pathbuilder instance
   * @param $path the path as a path object of which the triple parts should be 
   *              generated. May also be a group.
   * @param $primitiveValue The primitive data value that should be stored or
   *              asked for in the query.
   * @param $subject_in If there should be any subject on a certain position 
   *              this could be encoded by using $subject_in and the 
   *              $startingposition parameter.
   * @param $object_in If there should be any object. The position of the object
   *              may be encoded in the disambposition.
   * @param $disambposition The position in the path where the object or the
   *              general disambiguation of this path lies. 0 means no disamb,
   *              1 means disamb on the first concept, 2 on the second concept
   *              and so on.
   * @param $startingposition From where on the path should be generated in means
   *              of concepts from the beginning (warning: not like disamb! 0 means start here!).
   * @param $write Is this a write or a read-request?
   * @param $op How should it be compared to other data
   * @param $mode defaults to 'field' - but may be 'group' or 'entity_reference' in special cases
   * @param $relative should it be relative to the other groups?
   * @param $variables the variable of index i will be set to the value of key i.
   *              The variable ?out will be set to the key "out".
   * @param $numbering the initial numbering for the results - typically starting with 0.
   *              But can be modified by this.
   */
  public function generateTriplesForPath($pb, $path, $primitiveValue = "", $subject_in = NULL, $object_in = NULL, $disambposition = 0, $startingposition = 0, $write = FALSE, $op = '=', $mode = 'field', $relative = TRUE, $variable_prefixes = array(), $numbering = 0) {
#     \Drupal::logger('WissKIsaveProcess')->debug('generate: ' . serialize(func_get_args()));
#    if($mode == 'entity_reference')
#      dpm(func_get_args(), "fun");
    // the query construction parameter
    $query = "";
    // if we disamb on ourself, return.
    if($disambposition == 0 && !empty($object_in)) return "";

    // we get the sub-section for this path
    $clearPathArray = array();
    if($relative) {
      // in case of group creations we just need the " bla1 a type " triple
      if($mode == 'group_creation') 
        $clearPathArray = $pb->getRelativePath($path, FALSE);
      else // in any other case we need the relative path
        $clearPathArray = $pb->getRelativePath($path);
    } else { // except some special cases.
      $clearPathArray = $path->getPathArray();
    }
     
    // the RelativePath will be keyed like the normal path array
    // meaning that it will not necessarily start at 0
        
 #   \Drupal::logger('WissKIsaveProcess')->debug('countdiff ' . $countdiff . ' cpa ' . serialize($clearPathArray) . ' generate ' . serialize(func_get_args()));
    
    // old uri pointer
    $olduri = NULL;
    $oldvar = NULL;
    
    // if the old uri is empty we assume there is no uri and we have to
    // generate one in write mode. In ask mode we make variable-questions
    
    // get the default datagraphuri    
    $datagraphuri = $this->getDefaultDataGraphUri();
    
    $first = TRUE;
    
    // iterate through the given path array
    $localkey = 0;
    foreach($clearPathArray as $key => $value) {
      $localkey = $key;

      if($first) {
        if($key > ($startingposition *2) || ($startingposition *2) > ($key+count($clearPathArray))) {
#          dpm($key, "key");
#          dpm($startingposition, "starting");
#          dpm($clearPathArray, "cpa");
          drupal_set_message("Starting Position is set to a wrong value: '$startingposition'. See reports for details", "error");
          if (WISSKI_DEVEL) \Drupal::logger('WissKIsaveProcess')->debug('ERROR: ' . serialize($clearPathArray) . ' generate ' . serialize(func_get_args()));
          if (WISSKI_DEVEL) \Drupal::logger('WissKIsaveProcess')->debug('ERROR: ' . serialize(debug_backtrace()[1]['function']) . ' and ' . serialize(debug_backtrace()[2]['function']));
        }
        


        if($disambposition > 0 && !empty($object_in)) {
          if($key > (($disambposition-1) *2) || (($disambposition-1) *2) > ($key+count($clearPathArray))) {
            drupal_set_message("Disambposition is set to a wrong value: '$disambposition'. See reports for details.", "error");
            if (WISSKI_DEVEL) \Drupal::logger('WissKIsaveProcess')->debug('ERROR: ' . serialize($clearPathArray) . ' generate ' . serialize(func_get_args()));
            if (WISSKI_DEVEL) \Drupal::logger('WissKIsaveProcess')->debug('ERROR: ' . serialize(debug_backtrace()[1]['function']) . ' and ' . serialize(debug_backtrace()[2]['function']));
          }
        }
      }
      
      $first = false;
            
      // skip anything that is smaller than $startingposition.
      if($key < ($startingposition*2)) 
        continue;
      
      // basic initialisation
      $uri = NULL;
            
      // basic initialisation for all queries
      $localvar = "?" . (isset($variable_prefixes[$key]) ? $variable_prefixes[$key] : "x" . ($numbering + $key));
      if (empty($oldvar)) {
        // this is a hack but i don't get the if's below
        // and when there should be set $oldvar
        // TODO: fix this!
        $oldvar = "?" . (isset($variable_prefixes[$key]) ? $variable_prefixes[$key] : "x" . ($numbering + $key));
      }
      $graphvar = "?g_" . (isset($variable_prefixes[$key]) ? $variable_prefixes[$key] : "x" . ($numbering + $key));
      
      if($key % 2 == 0) {
        // if it is the first element and we have a subject_in
        // then we have to replace the first element with subject_in
        // and typically we don't do a type triple. So we skip the rest.
        if($key == ($startingposition*2) && !empty($subject_in)) {
          $olduri = $subject_in;
          
          if(!$write)
            $query .= "GRAPH $graphvar { <$olduri> a <$value> } . ";
          else
            $query .= " <$olduri> a <$value> . ";

          continue;
        }
        
        // if the key is the disambpos
        // and we have an object
        if($key == (($disambposition-1)*2) && !empty($object_in)) {
          $uri = $object_in;
          if ($write) {
            // we also write the class in case the object is already known to
            // the system but not to this adapter and there are no further
            // data values to be written in this adapter. in this case we would
            // end up with the uri correctly written to this adapter but the
            // reading query needs to check the class as well which wouldn't be
            // there, then.
            $query .= "<$uri> a <$value> . ";
          }
        } else {
                  
          // if it is not the disamb-case we add type-triples        
          if($write) {
            // generate a new uri
            $uri = $this->getUri($datagraphuri);
            $query .= "<$uri> a <$value> . ";
          }
          else
            $query .= "GRAPH $graphvar { $localvar a <$value> } . ";
        }
        
        // magic function
        // this if writes the triples from one x to another (x_1 y_1 x_n+1)
        // for x0 $prop is not set and thus it does not match!
        //
        // for $prop we recognize a leading '^' for inverting the property
        // direction, see '^' as sparql property chain operator
        if($key > 0 && !empty($prop)) {
        
          if($write) {
            
            if ($prop[0] == '^') {
              // we cannot use the '^' for sparql update
              $prop = substr($prop, 1);
              $query .= "<$uri> <$prop> <$olduri> . ";
            }
            else {
              $query .= "<$olduri> <$prop> <$uri> . ";
            }

          } else {
            
            $inv_sign = '';
            if ($prop[0] == '^') {
              $inv_sign = '^';
              $prop = substr($prop, 1);
            }

            $query .= "GRAPH ${graphvar}_1 { ";
            $inverse = $this->getInverseProperty($prop);
            // if there is not an inverse, don't do any unions
            if(empty($inverse)) {
              if(!empty($olduri))
                $query .= "<$olduri> ";
              else
                $query .= "$oldvar ";
          
              $query .= "$inv_sign<$prop> ";
                    
              if(!empty($uri))
                $query .= "<$uri> . ";
              else
                $query .= "$localvar . ";
            } else { // if there is an inverse, make a union
              $query .= "{ { ";
              // Forward query part
              if(!empty($olduri))
                $query .= "<$olduri> ";
              else
                $query .= "$oldvar ";
          
              $query .= "$inv_sign<$prop> ";
                    
              if(!empty($uri))
                $query .= "<$uri> . ";
              else
                $query .= "$localvar . ";
              
              $query .= " } UNION { ";

              // backward query part
          
              if(!empty($uri))
                $query .= "<$uri> ";
              else
                $query .= "$localvar "; 
          
              $query .= "$inv_sign<$inverse> ";

              if(!empty($olduri))
                $query .= "<$olduri> . ";
              else
                $query .= "$oldvar . ";
              
 
              $query .= " } } . "; 
            }
            
            $query .= " } . ";
          }
        }
         
        // if this is the disamb, we may break.
        if($key == (($disambposition-1)*2) && !empty($object_in)) {
          break;
        }
          
        $olduri = $uri;
        $oldvar = $localvar;
      } else {
        $prop = $value;
      }
    }

#\Drupal::logger('testung')->debug($path->getID() . ":".htmlentities($query));
    // get the primitive for this path if any    
    $primitive = $path->getDatatypeProperty();
    
    $pb_path_info = $pb->getPbPath($path->id());
    $has_primitive = !empty($primitive) && $primitive != "empty";
    // all paths should have a primitive. only this cases usually have no 
    // primitive:
    // - a group
    // - a path belonging to an entity reference field
    // - a path that has no corresp field (depending on use it may or may not 
    //   have a primitive)
    $should_have_primitive = 
         !$path->isGroup() 
      && $pb_path_info['fieldtype'] != 'entity_reference'
      && $pb_path_info['field'] != $pb::CONNECT_NO_FIELD;

    if(!$has_primitive && $should_have_primitive) {
      drupal_set_message("There is no primitive Datatype for Path " . $path->getName(), "error");
    }
    // if write context and there is an object, we don't attach the primitive
    // also if we create a group
    elseif ( ($write && !empty($object_in) && !empty($disambposition) ) || $mode == 'group_creation' || $mode == 'entity_reference') {
      // do nothing!
    }
    elseif ($has_primitive) {
      $outvar = "?" . (isset($variable_prefixes["out"]) ? $variable_prefixes["out"] : "out");
      $outgraph = "?g_" . (isset($variable_prefixes["out"]) ? $variable_prefixes["out"] : "out");

      if(!$write)
        $query .= "GRAPH $outgraph { ";
      else
        $query .= "";
        
      if(!empty($olduri)) {
        $query .= "<$olduri> ";
      } else {
        // if we initialized with a nearly empty path oldvar is empty.
        // in this case we assume x at the startingposition
        if(empty($oldvar))
          $query .= "?" . (isset($variable_prefixes[$localkey]) ? $variable_prefixes[$localkey] : "x" . $startingposition);
        else
          $query .= "$oldvar ";
      }
      
      $query .= "<$primitive> ";

      
      if(!empty($primitiveValue)) {
#        dpm($primitiveValue, "prim");
        
        if ($write) {
          // we have to escape it otherwise the sparql query may break
          $primitiveValue = $this->escapeSparqlLiteral($primitiveValue);
          $query .= "\"$primitiveValue\"";
        } else {

/* putting there the literal directly is not a good idea as 
  there may be problems with matching lang and datatype
        if($op == '=') 
          $query .= "'" . $primitiveValue . "' . ";
        else {
  Instead we compare it to the STR()-value 

*/

          $regex = FALSE;
          $negate = FALSE;
          $safe = FALSE;
          $cast_outvar = "STR($outvar)";
          
          if($op == "STARTS")
            $op = "STARTS_WITH";
          
          if($op == "ENDS")
            $op = "ENDS_WITH";
          
          if($op == '<>') {
            $op = '!=';
          }
          elseif($op == 'EMPTY' || $op == 'NOT_EMPTY') {
            $regex = FALSE;
            $safe = TRUE;
          }
          elseif($op == 'STARTS_WITH' || $op == 'starts_with') {
            $regex = FALSE;
            $safe = TRUE;
            // we don't do this here anymore, but do it with strStarts below
            // this should be faster on most triplestores than REGEX.
            //$primitiveValue = '^' . $this->escapeSparqlRegex($primitiveValue, TRUE);
          }
          elseif($op == 'ENDS_WITH' || $op == 'ends_with') {
            $regex = true;
            $safe = TRUE;
//            $primitiveValue = $this->escapeSparqlRegex($primitiveValue, TRUE) . '$';
          }
          elseif($op == 'CONTAINS' || $op == 'contains' || $op == 'NOT') {
            $regex = true;
            $safe = TRUE;
            
#            dpm($op, "op");
            
            if($op == "NOT") {
              $negate = TRUE;
              $op = "CONTAINS";  
            }
            
#            dpm($primitiveValue, "prim");
            $primitiveValue = $this->escapeSparqlRegex($primitiveValue, TRUE);
#            dpm($primitiveValue, "prim");
          }
          elseif ($op == 'IN' || $op == 'NOT IN' || $op == 'in' || $op == 'not in' || $op == 'not_in') {
            $regex = TRUE;
            $negate = ($op == 'NOT IN' || $op == 'not in');
            $safe = TRUE;
            $values = is_array($primitiveValue) ? $primitiveValue : explode(",", $primitiveValue);
            foreach ($values as &$v) {
              $v = $this->escapeSparqlRegex($v, TRUE);
            }
            $primitiveValue = join('|', $values);
          } else if(strtoupper($op) == "LONGERTHAN" || strtoupper($op) == "SHORTERTHAN") {
            $regex = FALSE;
            $safe = TRUE;
          } 
#          dpm($primitiveValue, "prim");
          
          if (!$safe) {
            if (is_numeric($primitiveValue)) {
#              dpm("yo!");
#              $escapedValue = intval($primitiveValue);
              $escapedValue = $primitiveValue;
              $cast_outvar = "xsd:decimal($outvar)";
            }
            else {
              $escapedValue = '"' . $this->escapeSparqlLiteral($primitiveValue) . '"';
            }
          } else {
            $escapedValue = '"' . $primitiveValue . '"';
          }
          
#          dpm($escapedValue, "esc");

          if ($op == 'BETWEEN' || $op == 'between') {
            list($val_min, $val_max) = is_array($primitiveValue) ? $primitiveValue : explode(";", $primitiveValue, 2);
            if (is_numeric($val_min) && is_numeric($val_max)) {
              $val_min = intval($val_min);
              $val_max = intval($val_max);
              $cast_outvar = "xsd:decimal($outvar)";
            }
            else {
              $val_min = $this->escapeSparqlLiteral($val_min);
              $val_max = $this->escapeSparqlLiteral($val_max);
            }
            $filter = "$cast_outvar >= $val_min & $cast_outvar <= $val_max";
          } else  if($op == "STARTS_WITH" || $op == 'starts_with') {
            $filter = "strStarts($cast_outvar, $escapedValue)";
          } else if ($op == "ENDS_WITH" || $op == 'ends_with') {
            $filter = "strEnds($cast_outvar, $escapedValue)";
          } /* This is wrong here...
          else if($op == "EMPTY") {
            $filter = "!bound(" . $outvar . ")";
          } else if($op == "NOT_EMPTY") {
            $filter = "bound(" . $outvar . ")";
          } */
          else if (strtoupper($op) == "LONGERTHAN" || strtoupper($op) == "SHORTERTHAN") {
            $filter = "strlen($outvar) ";
            if(strtoupper($op) == "LONGERTHAN")
              $filter .= ">";
            else
              $filter .= "<";
            $filter .= " $primitiveValue";
          } elseif($regex) {
            // we have to use STR() otherwise we may get into trouble with
            // datatype and lang comparisons
            $filter = "REGEX($cast_outvar, " . $escapedValue . ', "i" )';
          } else {
            // we have to use STR() otherwise we may get into trouble with
            // datatype and lang comparisons
            $filter = "$cast_outvar " . $op . ' ' . $escapedValue;
          }

          if ($negate && $op != "CONTAINS") {
            $filter = "NOT( $filter )";
          } else if($negate && $op == "CONTAINS") {
            $filter = "!" . $filter;
          }

          // speed up in case of equivalence
          if($op == "=" ) {
            if(is_numeric($primitiveValue))
              $escapedValue = "'" . $escapedValue . "'";
            $query .= " " . $escapedValue . " . ";
          } elseif( $op == "EMPTY" || $op == "NOT EMPTY") { 
            $query .= " $outvar . ";
          } else {         
            $query .= " $outvar . FILTER( $filter ) . ";
          }
        }
      } else {
        $query .= " $outvar . ";
      }
      if(!$write)
        $query .= " } . ";
    }
#    \Drupal::logger('WissKIsaveProcess')->debug('erg generate: ' . htmlentities($query));
#    if($mode == 'entity_reference')
#      \Drupal::logger('WissKIsaveProcess')->debug('erg generate: ' . htmlentities($query));

#    dpm($query, "query"); 
#\Drupal::logger('Sparql Adapter gtfp')->debug(str_replace("\n", '<br/>', htmlentities($path->id().":\n".\Drupal\Core\Serialization\Yaml::encode($tsa))));
    
    // in case of empty it must be optional or we never will get something, because the path may not be there and be not there at the same time.
    if($op == "EMPTY") {
      $query = " OPTIONAL { " . $query . " } . FILTER(!bound($outvar)) . ";
    }
    
    return $query;
  }
  
  public function addNewFieldValue($entity_id, $fieldid, $value, $pb, $mainprop = FALSE) {
#    drupal_set_message("I get: " . $entity_id.  " with fid " . $fieldid . " and value " . $value . ' for pb ' . $pb->id() . ' er ' . serialize($value_is_entity_ref));
#    drupal_set_message(serialize($this->getUri("smthg")));
    $datagraphuri = $this->getDefaultDataGraphUri();

    $pbarray = $pb->getPbEntriesForFid($fieldid);

#    drupal_set_message("I fetched path: " . serialize($pbarray));    

    $path = \Drupal\wisski_pathbuilder\Entity\WisskiPathEntity::load($pbarray['id']);

    if(empty($path))
      return;

    $field_storage_config = \Drupal\field\Entity\FieldStorageConfig::loadByName('wisski_individual', $fieldid);
    
    $target_type = NULL;
    
    if(!empty($field_storage_config)) 
      $target_type = $field_storage_config->getSetting("target_type");
    
    // we distinguish two modes of how to interpret the value: 
    // entity ref: the value is an entity id that shall be linked to 
    // normal: the value is a literal and may be disambiguated
    $is_entity_ref = ($mainprop == 'target_id' && ($path->isGroup() || $pb->getPbEntriesForFid($fieldid)['fieldtype'] == 'entity_reference'));

    // special case for files:
    if($target_type == "file" && $mainprop == 'target_id') {
      if( is_numeric($value)) 
        $value = $this->getUriForDrupalId($value, TRUE);
#      else { // it might be that there are spaces in file uris. These are bad for TS-queries.
#        $strrpos = strrpos($value, '/');
#        if($strrpos) { // only act of there is a / in it.
#          $value = substr($value, 0, $strrpos) . rawurlencode(substr($value, $strrpos));
#        }
#      }
    }
    
    // in case of no entity-reference we do not search because we
    // already get what we want!
    if($path->getDisamb() && !$is_entity_ref) {
      $sparql = "SELECT ?x" . (($path->getDisamb()-1)*2) . " WHERE { ";

      // starting position one before disamb because disamb counts the number of concepts, startin position however starts from zero
      $sparql .= $this->generateTriplesForPath($pb, $path, $value, NULL, NULL, NULL, $path->getDisamb()-1, FALSE);
       
      $sparql .= " }";
#      drupal_set_message("spq: " . ($sparql));
#      dpm($path, "path");
      $disambresult = $this->directQuery($sparql);
#dpm(array($sparql, $disambresult), __METHOD__ . " disamb query");
      if(!empty($disambresult))
        $disambresult = current($disambresult);      
    } 
    
    // rename to uri
    $subject_uri = $this->getUriForDrupalId($entity_id, TRUE);
    

    $sparql = "INSERT DATA { GRAPH <" . $datagraphuri . "> { ";

    // 1.) A -> B -> C -> D -> E (l: 9) and 2.) C -> D -> E (l: 5) is the relative, then
    // 1 - 2 is 4 / 2 is 2 - which already is the starting point.
    $start = ((count($path->getPathArray()) - (count($pb->getRelativePath($path))))/2);
    
    if($is_entity_ref) {
      // if it is a group - we take the whole group path as disamb pos
      if($path->isGroup())
        $sparql .= $this->generateTriplesForPath($pb, $path, "", $subject_uri, $this->getUriForDrupalId($value, TRUE), (count($path->getPathArray())+1)/2, $start, TRUE, '', 'entity_reference');
      else // if it is a field it has a disamb pos!
        $sparql .= $this->generateTriplesForPath($pb, $path, "", $subject_uri, $this->getUriForDrupalId($value, TRUE), $path->getDisamb(), $start, TRUE, '', 'entity_reference');        
    } else {
      if(empty($path->getDisamb()))
        $sparql .= $this->generateTriplesForPath($pb, $path, $value, $subject_uri, NULL, NULL, $start, TRUE);
      else {
#        drupal_set_message("disamb: " . serialize($disambresult) . " miau " . $path->getDisamb());
        if(empty($disambresult) || empty($disambresult->{"x" . ($path->getDisamb()-1)*2}) )
          $sparql .= $this->generateTriplesForPath($pb, $path, $value, $subject_uri, NULL, NULL, $start, TRUE);
        else
          // we may not set a value here - because we have a disamb result!
          $sparql .= $this->generateTriplesForPath($pb, $path, $value, $subject_uri, $disambresult->{"x" . ($path->getDisamb()-1)*2}->dumpValue("text"), $path->getDisamb(), $start, TRUE);
      }
    }
    $sparql .= " } } ";
#     \Drupal::logger('WissKIsaveProcess')->debug('sparql writing in add: ' . htmlentities($sparql));
#dpm($sparql, __METHOD__ . " sparql");
    $result = $this->directUpdate($sparql);
    
    
#    drupal_set_message("I add field $field from entity $entity_id that currently has the value $value");
  }
  
  public function writeFieldValues($entity_id, array $field_values, $pathbuilder, $bundle_id=NULL,$old_values=array(),$force_new=FALSE, $initial_write = FALSE) {
#    dpm($old_values, "ov");
#    drupal_set_message(serialize("Hallo welt!") . serialize($entity_id) . " " . serialize($field_values) . ' ' . serialize($bundle));
#    dpm(func_get_args(), __METHOD__);    
#    \Drupal::logger('WissKIsaveProcess')->debug(__METHOD__ . " with values: " . serialize(func_get_args()));
    // tricky thing here is that the entity_ids that are coming in typically
    // are somewhere from a store. In case of rdf it is easy - they are uris.
    // In case of csv or something it is more tricky. So I don't wan't to 
    // simply go to the store and tell it "give me the bundle of this".
    // The field ids come in handy here - fields are typically attached
    // to a bundle anyway. so I just get the bundle from there. I think it is
    // rather stupid that this function does not load the field values per 
    // bundle - it is implicitely anyway like that.
    // 
    // so I ignore everything and just target the field_ids that are mapped to
    // paths in the pathbuilder.
#$tmpt = microtime(TRUE); 
    
    $out = array();
    
#    return $out;                    
      
    // here we should check if we really know the entity by asking the TS for it.
    // this would speed everything up largely, I think.
    // by mark: additionally the below code always is true.
    // this results in default values of entities not getting initiated. I added
    // another parameter ($initial_write) for that which simply
    // ignores the old field values and forces a write.
    $init_entity = $this->loadEntity($entity_id);
    #$init_entity = $this->hasEntity($entity_id);
#\Drupal::logger('WissKI Import tmpc')->debug("l:".(microtime(TRUE)-$tmpt));
    
#    dpm($init_entity, "init");
#    dpm($force_new, "force!");
    
    // if there is nothing, continue.
    // by mark: currently the storage calls the createEntity. So this never may be used.
    // simply don't worry about it.
    if (empty($init_entity)) {
#      dpm('empty entity',__FUNCTION__);
      if ($force_new) {
        $entity = new WisskiEntity(array('eid' => $entity_id,'bundle' => $bundle_id),'wisski_individual',$bundle_id);
        $this->createEntity($entity,$entity_id);
      } else return;
    }
#\Drupal::logger('WissKI Import tmpc')->debug("c:".(microtime(TRUE)-$tmpt));
    
    if(empty($entity) && !empty($init_entity))
      $entity = $init_entity;
    
    if (!isset($old_values) && !empty($init_entity)) {
      // it would be better to gather this information from the form and not from the ts
      // there might have been somebody saving in between...
      // @TODO !!!
      $ofv = \Drupal::entityManager()->getFieldDefinitions('wisski_individual', $bundle_id);
#      dpm(array_keys($ofv), "ak");
      $old_values = $this->loadFieldValues(array($entity_id), array_keys($ofv), $bundle_id);
      
      if(!empty($old_values))
        $old_values = $old_values[$entity_id];
    }
#\Drupal::logger('WissKI Import tmpc')->debug("lf:".(microtime(TRUE)-$tmpt));

    //drupal_set_message("the old values were: " . serialize($old_values));
#    dpm($old_values,'old values');
#    dpm($field_values,'new values');
#    dpm($initial_write, "init");

    // in case of an initial write we forget the old values.
    if($initial_write)
      $old_values = array();


    // if there are fields in the old_values that were deleted in the current
    // version we have to get rid of these.
    // also if you delete some string completely it might be
    // that the key is not set in the new values anymore.

    // check if we have to delete some values
    // we go thru the old values and search for an equal value in the new 
    // values array
    // as we do this we also keep track of values that haven't changed so that we
    // do not have to write them again.
    foreach($old_values as $old_key => $old_value) {
#      drupal_set_message("deleting key $old_key with value " . serialize($old_value) . " from values " . serialize($field_values));
      if(!isset($field_values[$old_key])) {
        
        // in case there is no main prop it is typically value
        if(isset($old_value['main_property']))
          $mainprop = $old_value['main_property'];
        else
          $mainprop = "value";        

        foreach($old_value as $key => $val) {
        
          // main prop?
          if(!is_array($val))
            continue;
          
          // empty value?
          if(empty($val[$mainprop]))
            continue;
          
          // if not its a value...
#          drupal_set_message("I delete from " . $entity_id . " field " . $old_key . " value " . $val[$mainprop] . " key " . $key);
          $this->deleteOldFieldValue($entity_id, $old_key, $val[$mainprop], $pathbuilder, $key, $mainprop);
        }
      }
    }
#\Drupal::logger('WissKI Import tmpc')->debug("df:".(microtime(TRUE)-$tmpt));

#    dpm($old_values, "old values");

    // combined go through the new fields    
    foreach($field_values as $field_id => $field_items) {
#      drupal_set_message("I try to add data to field $field_id with items: " . serialize($field_items));
      $path = $pathbuilder->getPbEntriesForFid($field_id);
#      drupal_set_message("found path: " . serialize($path). " " . microtime());
      
      $old_value = isset($old_values[$field_id]) ? $old_values[$field_id] : array();

      if(empty($path)) {
#        drupal_set_message("I leave here: $field_id " . microtime());
        continue;
      }
        
#      drupal_set_message("I am still here: $field_id");

      $mainprop = $field_items['main_property'];
      
      unset($field_items['main_property']);
      
      $write_values = $field_items;
      
#      drupal_set_message("write values still is: " . serialize($write_values));
      
      // TODO $val is not set: iterate over fieldvalue!
      // if there are old values
      if (!empty($old_value)) {
        // we might want to delete some
        $delete_values = $old_value;
        
#        drupal_set_message("del: " . serialize($delete_values));
        
        // if it is not an array there are no values, so we can savely stop
        if (!is_array($old_value)) {
          $delete_values = array($mainprop => $old_value);
          // $old_value contains the value directly
          foreach ($field_items as $key => $new_item) {
            if (empty($new_item)) { // empty field item due to cardinality, see else branch
              unset($write_values[$key]);
              continue;
            }
#            drupal_set_message("old value is: " . serialize($old_value) . " new is: " . $new_item[$mainprop]);
            // if the old value is somwhere in the new item
            if ($old_value == $new_item[$mainprop]) {
              // we unset the write value at this key because this doesn't have to be written
              unset($write_values[$key]);
              // we reset the things we need to delete
              $delete_values = array();
            }
          }
        } else {
          // $old_value is an array of arrays resembling field list items and
          // containing field property => value pairs
          
          foreach ($old_value as $old_key => $old_item) {
            
            if (!is_array($old_item) || empty($old_item)) {
              // this may be the case if 
              // - it contains key "main_property"... (not an array)
              // - it is an empty field that is there because of the 
              // field's cardinality (empty)
              unset($delete_values[$old_key]);
              continue;
            }
            
            $maincont = FALSE;
            
            foreach ($write_values as $key => $new_item) {
            
              if (empty($new_item)) {
                unset($write_values[$key]);
                continue; // empty field item due to cardinality
              }
              if ($old_item[$mainprop] == $new_item[$mainprop]) {
                // if we find the item in the old values we don't have to write it.
                unset($write_values[$key]);
                // and we don't have to delete it
                unset($delete_values[$old_key]);
                
                $maincont = TRUE;
                break;
              }
            }
            
            // if we found something we continue in the old values
            if($maincont)
              continue;
          }
        }

        #dpm($delete_values, "we have to delete");
        if (!empty($delete_values)) {
          foreach ($delete_values as $key => $val) {            
            #drupal_set_message("I1 delete from " . $entity_id . " field " . $old_key . " value " . $val[$mainprop] . " key " . $key);
            $this->deleteOldFieldValue($entity_id, $field_id, $val[$mainprop], $pathbuilder, $key, $mainprop);
          }
        }
      }
      
#      dpm($write_values, "we have to write");
      // now we write all the new values
      // TODO: it seems like there is a duplicate write in case of image files..
      // probably due to the fact that they are not found as old value because the URL is stored.
      // it does not hurt currently, but it is a performance sink.
      foreach ($write_values as $new_item) {
#        dpm($mainprop, "mainprop");
        
        $this->addNewFieldValue($entity_id, $field_id, $new_item[$mainprop], $pathbuilder, $mainprop); 
      }
  
    }
#\Drupal::logger('WissKI Import tmpc')->debug("da:".(microtime(TRUE)-$tmpt));


#    drupal_set_message("out: " . serialize($out));

    return $out;

  }
  
  // -------------------------------- Ontologie thingies ----------------------

  public function addOntologies($iri = NULL) { 
    
    if (empty($iri)) {
      //load all ontologies
      $query = "SELECT ?ont WHERE { GRAPH ?g { ?ont a owl:Ontology} }";
      $result = $this->directQuery($query);
      foreach ($result as $obj) {
        $this->addOntologies(strval($obj->ont));
      }
      return;
    }

    // check if the Ontology is already there
    $result = $this->directQuery("ASK { GRAPH ?g { <$iri> a owl:Ontology } }");

    // if we get here we may load the ontology
    $query = "LOAD <$iri> INTO GRAPH <$iri>";
#    dpm($query, "query");
    $result = $this->directUpdate($query);

    drupal_set_message("Successfully loaded $iri into the Triplestore.");
    \Drupal::logger('WissKI Ontology')->info(
      'Adapter {a}: Successfully loaded ontology <{iri}>.',
      array(
        'a' => $this->adapterId(),
        'iri' => $iri,
      )
    );
  
    // look for imported ontologies
    $query = "SELECT DISTINCT ?ont FROM <$iri> WHERE { ?s a owl:Ontology . ?s owl:imports ?ont . }";
    $results = $this->directQuery($query);
 
    foreach ($results as $to_load) {
      $this->addOntologies(strval($to_load->ont));
    }
                
    // add namespaces to table
    // TODO: curently all namespaces from all adapters are stored in a single
    // table and adapters may override existing ones from themselves or from
    // other adapters!
    // We add the namespaces AFTER we loaded the imported ontologies so that
    // the importing ontology's namespaces win over the ones in the imported 
    // ontologies
    list($default, $namespaces) = $this->getNamespacesFromDocument($iri);
    if (!empty($namespaces)) {
      foreach($namespaces as $key => $value) {
        $this->putNamespace($key, $value);
      }
      \Drupal::logger('WissKI Ontology')->info(
        'Adapter {a}: registered the following namespaces and prefixes: {n} Default is {b}',
        array(
          'a' => $this->adapterId(),
          'n' => array_reduce(array_keys($namespaces), function($carry, $k) use ($namespaces) { return "$carry$k: <$namespaces[$k]>. "; }, ''),
          'b' => empty($base) ? 'not set' : "<$base>",
        )
      );
      // TODO: default is currently not stored. Do we need to store it? 
      // it is no proper namespace prefix. 
      // @TODO: check if it is already in the ontology.
      // TODO: why declare these here? we never use them!
      #global $base_url;
      #$this->putNamespace("local", $base_url . '/');
      #$this->putNamespace("data", $base_url . '/inst/');
    } else {
      \Drupal::logger('WissKI Ontology')->info('Adapter {a}: no namespaces registered', array('a' => $this->adapterId()));
    }
    
    // return the result of the loading of this ontology
    return $result;   

  }

  
  /** Tries to parse RDF namespace declarations in a given document.
   *
   * @param iri the IRI of the document
   *
   * @return an array where the first element is the default prefix URI and the
   *         second is an array of prefix-namespace pairs. If the document 
   *         cannot be parsed, an array(FALSE, FALSE) is returned.
   */
  public function getNamespacesFromDocument($iri) {
    $file = file_get_contents($iri);
    $format = \EasyRdf_Format::guessFormat($file, $iri); 
    // unfortunately EasyRdf does not provide any API to get the declared
    // namespaces although in general its Parsers do handle them.
    // Therefore we have to provide our own namespace parsers. 
    // atm, we only supprt rdfxml
    if(stripos($format->getName(), 'xml') !== FALSE) {
      // this is a quick and dirty parse of an rdfxml file.
      // search for xmlns:xyz pattern inside the rdf:RDF tag.
      if (preg_match('/<(?:\w+:)?RDF[^>]*>/i', $file, $rdf_tag)) {
        preg_match_all('/xmlns[^=]*=(?:"[^"]*"|\'[^\']*\')/i', $rdf_tag[0], $nsarray);
        // go thru the matches and collect the default and prefix-namespace pairs
        $namespaces = array();
        $default = NULL;
        foreach($nsarray[0] as $ns_decl) {
          list($front, $back) = explode("=", substr($ns_decl, 5));  // remove the leading xmlns
          $front = trim($front);
          $value = substr($back, 1, -1);  // chop the "/'
          if (empty($front)) {
            // if front is empty it is the default namespace
            $default = $value;
          }
          elseif ($front[0] == ':') {
            // a named prefix must start with a :
            $prefix = substr($front, 1); // chop the leading :
            $namespaces[$prefix] = $value;
          } 
          // else:
          // it's not a namespace declaration but an attribute that starts 
          // with xmlns. we must ignore it.
        }
        return array($default, $namespaces);
      }
      // else: if we cannot match an RDF tag it's no / an unknown RDF document
    }
    // no parser available for this file format
    return array(FALSE, FALSE);
  }


  public function getOntologies($graph = NULL) {
    // get ontology and version uri
    if(!empty($graph)) {
      $query = "SELECT DISTINCT ?ont ?iri ?ver FROM $graph WHERE {\n"
             . "  ?ont a owl:Ontology .\n"
             . "  OPTIONAL { ?ont owl:ontologyIRI ?iri. } OPTIONAL { ?ont owl:versionIRI ?ver . }\n"
             . "}";
    }
    else {
      $query = "SELECT DISTINCT ?ont (COALESCE(?niri, 'none') as ?iri) (COALESCE(?nver, 'none') as ?ver) ?graph WHERE {\n"
             . "  GRAPH ?graph { ?ont a owl:Ontology } .\n"
             . "  OPTIONAL { ?ont owl:ontologyIRI ?niri. } OPTIONAL { ?ont owl:versionIRI ?nver . }\n"
             . "}";
    }
    $results = $this->directQuery($query);                      
    return $results;
  }
     
  public function deleteOntology($graph, $type = "graph") {
 
    // get ontology and version uri
    if($type == "graph") {
      $query = "WITH <$graph> DELETE { ?s ?p ?o } WHERE { ?s ?p ?o }";
    } else
      $query = "DELETE { ?s ?p ?o } WHERE { ?s ?p ?o . FILTER ( STRSTARTS(STR(?s), '$graph')) }";
                         
    $results = $this->directUpdate($query);
                             
   /* if (!$ok) {
    // some useful error message :P~
      drupal_set_message('some error encountered:' . serialize($results), 'error');
    }
   */                                              
    return $results;
  }
  
  private function putNamespace($short_name,$long_name) {
    $result = db_select('wisski_core_ontology_namespaces','ns')
              ->fields('ns')
              ->condition('short_name',$short_name,'=')
              ->execute()
              ->fetchAssoc();
    if (empty($result)) {
      db_insert('wisski_core_ontology_namespaces')
              ->fields(array('short_name' => $short_name,'long_name' => $long_name))
              ->execute();
    } else {
     //      drupal_set_message('Namespace '.$short_name.' already exists in DB');
    }
  }
                                                                                                           
  /*
  * This should be made global as it actually stores the namespaces globally
  */
  public function getNamespaces() {
    $ns = array();
    $db_spaces = db_select('wisski_core_ontology_namespaces','ns')
                  ->fields('ns')
                  ->execute()
                  ->fetchAllAssoc('short_name');
    foreach ($db_spaces as $space) {
      $ns[$space->short_name] = $space->long_name;
    }
    return $ns;
  }

  private $super_properties = array();
  private $clean_super_properties = array();

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

#    $cids = array(
#      'properties',
#      'sub_properties',
#      'super_properties',
#      'inverse_properties',
#      'sub_classes',
#      'super_classes',
#      'domains',
#      'reverse_domains',
#      'ranges',
#      'reverse_ranges',
#    );
#    $results = array();
#    foreach ($cids as $cid) {
#      if ($cache = \Drupal::cache()->get('wisski_reasoner_'.$cid)) {
#        $results[$cid] = $cache->data;
#      }
#    }
#    dpm($results,'Results');

    $in_cache = $this->isCacheSet();

    $form = parent::buildConfigurationForm($form, $form_state);

    $button_label = $this->t('Start Reasoning');
    $emphasized = $this->t('This will take several minutes.');

    $always_reason = \Drupal::state()->get('wisski_always_reason');

    if(isset($always_reason[$this->adapterId()]))
      $always_reason = $always_reason[$this->adapterId()];
    else
      $always_reason = TRUE;
    
    $form['allow_inverse_property_pattern'] = array(
      '#type' => 'checkbox',
      '#title' => 'Inverse property selection',
      '#default_value' => $this->allow_inverse_property_pattern,
      '#return_value' => TRUE,
      '#description' => 'Allows selecting properties in inverse direction in pathbuilder. These properties are marked with a leading "^". E.g. for "^ex:prop1", the triple x2 ex:prop x1 must hold instead of x1 ex:prop1 x2.',
    );
    
    $form['reasoner'] = array(
      '#type' => 'details',
      '#title' => $this->t('Compute Type and Property Hierarchy and Domains and Ranges'),
      '#prefix' => '<div id="wisski-reasoner-block">',
      '#suffix' => '</div>',
      'description' => array(
        '#type' => 'fieldset',
        '#title' => $this->t('Read carefully'),
        'description_start' => array('#markup' => $this->t("Clicking the %label button will initiate a set of complex SPARQL queries computing",array('%label'=>$button_label))),
        'description_list' => array(
          '#theme' => 'item_list',
          '#items' => array(
            $this->t("the class hierarchy"),
            $this->t("the property hierarchy"),
            $this->t("the domains of all properties"),
            $this->t("the ranges of all properties"),
          ),
        ),
        'description_end' => array(
          '#markup' => $this->t(
            "in the specified triple store. <strong>%placeholder</strong> The pathbuilders relying on this adapter will become much faster by doing this.",
            array('%placeholder'=>$emphasized)
          ),
        ),
      ),
      'start_button' => array(
        '#type' => 'button',
        '#value' => $button_label,
        '#ajax' => array(
          'wrapper' => 'wisski-reasoner-block',
          'callback' => array($this,'startReasoning'),
        ),
        '#prefix' => '<div id="wisski-reasoner-start-button">',
        '#suffix' => '</div>',
      ),
      'always_reason_this_store' => array(
        '#type' => 'checkbox',
        '#title' => $this->t('Always do reasoning on this adapter.'),
        '#default_value' => $always_reason,
      ),
    );
    if ($in_cache) {
      $form['reasoner']['start_button']['#disabled'] = !$form_state->getValue('flush_button');
      $form['reasoner']['flush_button'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Re-Compute results'),
        '#default_value' => FALSE,
        '#description' => $this->t('You already have reasoning results in your cache'),
        '#ajax' => array(
          'wrapper' => 'wisski-reasoner-start-button',
          'callback' => array($this,'checkboxAjax'),
        ),
      );
      $classes_n_properties = ((array) $this->getClasses()) + ((array) $this->getProperties());
      if ($this->getClasses() === FALSE || $this->getProperties() === FALSE) {
        drupal_set_message($this->t('Bad class and property cache.'));
      }
      $form['reasoner']['tester'] = array(
        '#type' => 'details',
        '#title' => $this->t('Check reasoning results'),
        'selected_prop' => array(
          '#type' => 'select',
          '#options' => $classes_n_properties,
          '#empty_value' => 'empty',
          '#empty_option' => $this->t('select a class or property'),
          '#ajax' => array(
            'wrapper' => 'wisski-reasoner-check',
            'callback' => array($this,'checkTheReasoner'),
          ),
        ),
        'check_results' => array(
          '#type' => 'textarea',
          '#prefix' => '<div id="wisski-reasoner-check">',
          '#suffix' => '</div>',      
        ),
      );
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    
    $val = $form_state->getValue('always_reason_this_store');
        
    $always_reason = \Drupal::state()->get('wisski_always_reason');
    $always_reason[$this->adapterId()] = $val;
    
    \Drupal::state()->set('wisski_always_reason', $always_reason);

    $this->allow_inverse_property_pattern = $form_state->getValue('allow_inverse_property_pattern');
  
  }  
  

  public function checkboxAjax(array $form, FormStateInterface $form_state) {
    return $form['reasoner']['start_button'];
  }
  
  public function checkTheReasoner(array $form, FormStateInterface $form_state) {
  
    $candidate = $form_state->getValue($form_state->getTriggeringElement()['#name']);
    if ($this->isAProperty($candidate)) {
      $stored = $this->getClassesFromStore($candidate);
      $cached = $this->getClassesFromCache($candidate);
    } else {
      $stored = $this->getPropertiesFromStore($candidate);
      $cached = $this->getPropertiesFromCache($candidate);
    }
    $more_stored = array_diff($stored,$cached);
    $more_cached = array_diff($cached,$stored);
    if (empty($more_stored) && empty($more_cached)) {
      $result = $this->t('Same results for cache and direct query');
      $full_results = $stored;
    } else {
      $stored_text = empty($more_stored) ? '' : $this->t('more in store:')."\n\t".implode("\n\t",$more_stored);
      $cached_text = empty($more_cached) ? '' : $this->t('more in cache:')."\n\t".implode("\n\t",$more_cached);
      $result = $this->t('Different results:')."\n".$stored_text."\n".$cached_text;
      $full_results = array_unique(array_merge($stored,$cached));
    }
    $form['reasoner']['tester']['check_results']['#value'] = $candidate."\n".$result."\n\n".$this->t('Full list of results')."\n\t".implode("\n\t",$full_results);
    return $form['reasoner']['tester']['check_results'];
  }

  public function startReasoning(array $form,FormStateInterface $form_state) {
    
    $this->doTheReasoning();
    $form_state->setRedirect('<current>');
    return $form['reasoner'];
  }
  
  public function doTheReasoning() {
  
    $properties = array();
    $super_properties = array();
    $sub_properties = array();
    
    //prepare database connection and reasoner tables
    //if there's something wrong stop working
    if ($this->prepareTables() === FALSE) return;
    
    //find properties
    $result = $this->directQuery("SELECT ?property WHERE { GRAPH ?g {{?property a owl:ObjectProperty.} UNION {?property a rdf:Property}} }");
    $insert = $this->prepareInsert('properties');
    foreach ($result as $row) {
      $prop = $row->property->getUri();
      $properties[$prop] = $prop;
      $insert->values(array('property' => $prop));
    }
    $insert->execute();
    //$cid = 'wisski_reasoner_properties';
    //\Drupal::cache()->set($cid,$properties);
    
    //find one step property hierarchy, i.e. properties that are direct children or direct parents to each other
    // no sub-generations are gathered
    $result = $this->directQuery(
      "SELECT ?property ?super WHERE { GRAPH ?g {"
        ."{{?property a owl:ObjectProperty.} UNION {?property a rdf:Property}}} . GRAPH ?g1 { "
        ."?property rdfs:subPropertyOf ?super.  "
        ."FILTER NOT EXISTS {?mid_property rdfs:subPropertyOf+ ?super. ?property rdfs:subPropertyOf ?mid_property.}"
      ."} } ");
    foreach ($result as $row) {
      $prop = $row->property->getUri();
      $super = $row->super->getUri();
      $super_properties[$prop][$super] = $super;
      $sub_properties[$super][$prop] = $prop;
      if (!isset($properties[$prop])) $properties[$prop] = $prop;
    }

    //$cid = 'wisski_reasoner_sub_properties';
    //\Drupal::cache()->set($cid,$sub_properties);
    //$cid = 'wisski_reasoner_super_properties';
    //\Drupal::cache()->set($cid,$super_properties);

    //now lets find inverses
    $insert = $this->prepareInsert('inverses');
    $inverses = array();
    $results = $this->directQuery("SELECT ?prop ?inverse WHERE { GRAPH ?g {{?prop owl:inverseOf ?inverse.} UNION {?inverse owl:inverseOf ?prop.}} }");
    foreach ($results as $row) {
      $prop = $row->prop->getUri();
      $inv = $row->inverse->getUri();
      $inverses[$prop] = $inv;
      $insert->values(array('property' => $prop,'inverse'=>$inv));
    }
    $insert->execute();
    //$cid = 'wisski_reasoner_inverse_properties';
    //\Drupal::cache()->set($cid,$inverses);
    
    //now the same things for classes
    //find all classes
    $insert = $this->prepareInsert('classes');
    $classes = array();
    $results = $this->directQuery("SELECT ?class WHERE { GRAPH ?g {{?class a owl:Class.} UNION {?class a rdfs:Class}} }");
    foreach ($results as $row) {
      $class = $row->class->getUri();
      $classes[$class] = $class;
      $insert->values(array('class'=>$class));
    }
    $insert->execute();
    //uksort($classes,'strnatcasecmp');
    //\Drupal::cache()->set('wisski_reasoner_classes',$classes);
    
    //find full class hierarchy
    $super_classes = array();
    $sub_classes = array();
    $results = $this->directQuery("SELECT ?class ?super WHERE { GRAPH ?g {"
      ."?class rdfs:subClassOf+ ?super. "
      ."FILTER (!isBlank(?class)) "
      ."FILTER (!isBlank(?super)) } . GRAPH ?g1 {"
      ."{{?super a owl:Class.} UNION {?super a rdfs:Class.}} "
    ."} }");
    foreach ($results as $row) {
      $sub = $row->class->getUri();
      $super = $row->super->getUri();
      $super_classes[$sub][$super] = $super;
      $sub_classes[$super][$sub] = $sub;
    }
    
    //\Drupal::cache()->set('wisski_reasoner_sub_classes',$sub_classes);
    //\Drupal::cache()->set('wisski_reasoner_super_classes',$super_classes);
    
    //explicit top level domains
    $domains = array();
    
    $results = $this->directQuery(
      "SELECT ?property ?domain WHERE { GRAPH ?g {"
        ." ?property rdfs:domain ?domain."
        // we only need top level domains, so no proper subClass of the domain shall be taken into account
        ." FILTER NOT EXISTS { ?domain rdfs:subClassOf+ ?super_domain. ?property rdfs:domain ?super_domain.}"
      ." } }");
    foreach ($results as $row) {
      $domains[$row->property->getUri()][$row->domain->getUri()] = $row->domain->getUri();
    }
    
    //clear up, avoid DatatypeProperties
    $domains = array_intersect_key($domains,$properties);
    
    //explicit top level ranges
    $ranges = array();
    
    $results = $this->directQuery(
      "SELECT ?property ?range WHERE { GRAPH ?g {"
        ." ?property rdfs:range ?range."
        // we only need top level ranges, so no proper subClass of the range shall be taken into account
        ." FILTER NOT EXISTS { ?range rdfs:subClassOf+ ?super_range. ?property rdfs:range ?super_range.}"
      ." } }");
    foreach ($results as $row) {
      $ranges[$row->property->getUri()][$row->range->getUri()] = $row->range->getUri();
    }
    
    //clear up, avoid DatatypeProperties
    $ranges = array_intersect_key($ranges,$properties);    

    //take all properties with no super property
    $top_properties = array_diff_key($properties,$super_properties);

    $invalid_definitions = array();
    //check if they all have domains and ranges set
    $dom_check = array_diff_key($top_properties,$domains);
    if (!empty($dom_check)) {
      drupal_set_message('No domains for top-level properties: '.implode(', ',$dom_check),'error');;
      $invalid_definitions = array_merge($invalid_definitions, $dom_check);
    #  $valid_definitions = FALSE;
      //foreach($dom_check as $dom) {
      //  $domains[$dom] = ['TOPCLASS'=>'TOPCLASS'];
      //}
    }
    $rng_check = array_diff_key($top_properties,$ranges);
    if (!empty($rng_check)) {
      drupal_set_message('No ranges for top-level properties: '.implode(', ',$rng_check),'error');
      $invalid_definitions = array_merge($invalid_definitions, $rng_check);
    #  $valid_definitions = FALSE;
      //foreach ($rng_check as $rng) {
      //  $ranges[$rng] = ['TOPCLASS'=>'TOPCLASS'];
      //}
    }
    
    //set of properties where the domains and ranges are not fully set
    $not_set = array_diff_key($properties,$top_properties);
    $not_set = array_diff_key($not_set, $invalid_definitions);
#    dpm($invalid_definitions, "invalid!");
#   dpm($not_set, "not set");
    
    //while there are unchecked properties cycle throgh them, gather domain/range defs from all super properties and inverses
    //and include them into own definition
    $runs = 0;
    while (!empty($not_set)) {
      
      $runs++;
      //take one of the properties
      $prop = array_shift($not_set);
 #     dpm($prop, "was not set");
      //check if all super_properties have their domains/ranges set
      $supers = $super_properties[$prop];
      $invalid_supers = array_intersect($supers,$invalid_definitions);
#      $invalid_supers = $invalid_definitions;
#      dpm($supers, "for prop $prop, still to check: " . serialize($not_set));

      if (empty($invalid_supers)) {
      
        $to_check = array_intersect($supers, $not_set);
        if(!empty($to_check)) {
          array_push($not_set,$prop);
          continue;
        }
      
        //take all the definitions of super properties and add them here
        $new_domains = isset($domains[$prop]) ? $domains[$prop] : array();
        $new_ranges = isset($ranges[$prop]) ? $ranges[$prop] : array();
#        dpm($domains);
        foreach ($supers as $super_prop) {
          if(isset($domains[$super_prop]))
            $new_domains += $domains[$super_prop];
          if(isset($ranges[$super_prop]))
            $new_ranges += $ranges[$super_prop];
        }
        $new_domains = array_unique($new_domains);
        $new_ranges = array_unique($new_ranges);
        
        $remove_domains = array();
        foreach ($new_domains as $domain_1) {
          foreach ($new_domains as $domain_2) {
            if ($domain_1 !== $domain_2) {
              if (isset($super_classes[$domain_1]) && in_array($domain_2,$super_classes[$domain_1])) {
                $remove_domains[] = $domain_2;
              }
            }
          }
        }
        $new_domains = array_diff($new_domains,$remove_domains);
        
        $domains[$prop] = array_combine($new_domains,$new_domains);
        
        $remove_ranges = array();
        foreach ($new_ranges as $range_1) {
          foreach ($new_ranges as $range_2) {
            if ($range_1 !== $range_2) {
              if (isset($super_classes[$range_1]) && in_array($range_2,$super_classes[$range_1])) {
                $remove_ranges[] = $range_2;
              }
            }
          }
        }
        $new_ranges = array_diff($new_ranges,$remove_ranges);
        
        $ranges[$prop] = array_combine($new_ranges,$new_ranges);
        
      } else {
        //append this property to the end of the list to be checked again later-on
#        array_push($not_set,$prop);
        drupal_set_message("I could not check $prop, because it has the invalid superproperties: " . implode(', ',$invalid_supers), "error");
        continue;
      }
    }
    drupal_set_message('Definition checkup runs: '.$runs);
    //remember sub classes of domains are domains, too.
    //if a property has exactly one domain set, we can add all subClasses of that domain
    //if there are multiple domains we can only add those being subClasses of ALL of the domains
    foreach ($properties as $property) {
      if (isset($domains[$property])) {
        $add_up = array();
        foreach ($domains[$property] as $domain) {
          if (isset($sub_classes[$domain]) && $sub_domains = $sub_classes[$domain]) {
            $add_up = empty($add_up) ? $sub_domains : array_intersect_key($add_up,$sub_domains);
          }
        }
        $domains[$property] = array_merge($domains[$property],$add_up);
      }
      if (isset($ranges[$property])) {
        $add_up = array();
        foreach ($ranges[$property] as $range) {
          if (isset($sub_classes[$range]) && $sub_ranges = $sub_classes[$range]) {
            $add_up = empty($add_up) ? $sub_ranges : array_intersect_key($add_up,$sub_ranges);
          }
        }
        $ranges[$property] = array_merge($ranges[$property],$add_up);
      }
    }
    
    $insert = $this->prepareInsert('domains');
    foreach ($domains as $prop => $classes) {
      foreach ($classes as $class) $insert->values(array('property'=>$prop,'class'=>$class));
    }
    $insert->execute();
    $insert = $this->prepareInsert('ranges');
    foreach ($ranges as $prop => $classes) {
      foreach ($classes as $class) $insert->values(array('property'=>$prop,'class'=>$class));
    }
    $insert->execute();
    
//    //for the pathbuilders to work correctly, we also need inverted search
//    $reverse_domains = array();
//    foreach ($domains as $prop => $classes) {
//      foreach ($classes as $class) $reverse_domains[$class][$prop] = $prop;
//    }
//    $reverse_ranges = array();
//    foreach ($ranges as $prop => $classes) {
//      foreach ($classes as $class) $reverse_ranges[$class][$prop] = $prop;
//    }
//    $cid = 'wisski_reasoner_domains';
//    \Drupal::cache()->set($cid,$domains);
//    $cid = 'wisski_reasoner_ranges';
//    \Drupal::cache()->set($cid,$ranges);
//    $cid = 'wisski_reasoner_reverse_domains';
//    \Drupal::cache()->set($cid,$reverse_domains);
//    $cid = 'wisski_reasoner_reverse_ranges';
//    \Drupal::cache()->set($cid,$reverse_ranges);
  }

  public function getInverseProperty($property_uri) {

  /* cache version
    $inverses = array();
    $cid = 'wisski_reasoner_inverse_properties';
    if ($cache = \Drupal::cache()->get($cid)) {
      $inverses = $cache->data;
      if (isset($properties[$property_uri])) return $inverses[$property_uri];
    }
    */
    
    //DB version
    $inverse = $this->retrieve('inverses','inverse','property',$property_uri);
    if (!empty($inverse)) return current($inverse);

    // up to now this was the current code. However this is evil in case there are several answers.
    // it will then return the upper one which is bad.
    // so in case there is an easy answer, give the easy answer.
    $results = $this->directQuery(
      "SELECT ?sub_inverse ?inverse WHERE {"
        ."{"
          ."{GRAPH ?g1 {?sub_inverse owl:inverseOf ?inverse.}}"
          ." UNION "
          ."{GRAPH ?g2 {?inverse owl:inverseOf ?sub_inverse.}}"
        ."}"
        ."{GRAPH ?g3 {<$property_uri> rdfs:subPropertyOf* ?sub_inverse}}"
      ."}"
    );

    $inverse = '';
    foreach ($results as $row) {
      $inverse = $row->inverse->getUri();
      // if we had the requested property, we do not need to search for a sub...
      if($row->sub_inverse->getUri() == $property_uri) {
        break;
      }
    }
    $inverses[$property_uri] = $inverse;
//    \Drupal::cache()->set($cid,$inverses);
    return $inverse;
  }
  
  protected function isPrepared() {
    try {
      $result = !empty(\Drupal::service('database')->select($this->adapterId().'_classes','c')->fields('c')->range(0,1)->execute());
      return $result;
    } catch (\Exception $e) {
      return FALSE;
    }
  }
  
  protected function prepareTables() {
    
    try {
      $database = \Drupal::service('database');
      $schema = $database->schema();
      $adapter_id = $this->adapterId();
      foreach (self::getReasonerTableSchema() as $type => $table_schema) {
        $table_name = $adapter_id.'_'.$type;
        if ($schema->tableExists($table_name)) {
          $database->truncate($table_name)->execute();
        } else {
          $schema->createTable($table_name,$table_schema);
        }
      }
      return TRUE;
    } catch (\Exception $ex) {}
    return FALSE;
  }
  
  private function prepareInsert($type) {
    
    $fieldS = array();
    foreach (self::getReasonerTableSchema()[$type]['fields'] as $field_name => $field) {
      if ($field['type'] !== 'serial') $fields[] = $field_name;
    }
    $table_name = $this->adapterId().'_'.$type;
    return \Drupal::service('database')->insert($table_name)->fields($fields);
  }
  
  public function retrieve($type,$return_field=NULL,$condition_field=NULL,$condition_value=NULL) {
    
    $table_name = $this->adapterId().'_'.$type;
    $query = \Drupal::service('database')
              ->select($table_name,'t')
              ->fields('t');
    if (!is_null($condition_field) && !is_null($condition_value)) {
      $query = $query->condition($condition_field,$condition_value);
    }
    try {
      $result = $query->execute();
      if (!is_null($return_field)) {
        $result = array_keys($result->fetchAllAssoc($return_field));
        usort($result,'strnatcasecmp');
        return array_combine($result,$result);
      }
      return $result->fetchAll();
    } catch (\Exception $e) {
      return FALSE;
    }
  }
  
  /**
   * implements hook_schema()
   */
  public static function getReasonerTableSchema() {

    $schema['classes'] = array(
      'description' => 'hold information about triple store classes',
      'fields' => array(
        'num' => array(
          'description' => 'the Serial Number for this class',
          'type' => 'serial',
          'size' => 'normal',
          'not null' => TRUE,
        ),
        'class' => array(
          'description' => 'the uri of the class',
          'type' => 'varchar',
          'length' => '2048',
          'not null' => TRUE,
        ),
      ),
      'primary key' => array('num'),
    );
    
    $schema['properties'] = array(
      'description' => 'hold information about triple store properties',
      'fields' => array(
        'num' => array(
          'description' => 'the Serial Number for this property',
          'type' => 'serial',
          'size' => 'normal',
          'not null' => TRUE,
        ),
        'property' => array(
          'description' => 'the uri of the property',
          'type' => 'varchar',
          'length' => '2048',
          'not null' => TRUE,
        ),
      ),
      'primary key' => array('num'),
    );    
    
    $schema['domains'] = array(
      'description' => 'hold information about domains of triple store properties',
      'fields' => array(
        'num' => array(
          'description' => 'the Serial Number for this pairing',
          'type' => 'serial',
          'size' => 'normal',
          'not null' => TRUE,
        ),
        'property' => array(
          'description' => 'the uri of the property',
          'type' => 'varchar',
          'length' => '2048',
          'not null' => TRUE,
        ),
        'class' => array(
          'description' => 'the uri of the domain class',
          'type' => 'varchar',
          'length' => '2048',
          'not null' => TRUE,
        ),
      ),
      'primary key' => array('num'),
    );
    
    $schema['ranges'] = array(
      'description' => 'hold information about ranges of triple store properties',
      'fields' => array(
        'num' => array(
          'description' => 'the Serial Number for this pairing',
          'type' => 'serial',
          'size' => 'normal',
          'not null' => TRUE,
        ),
        'property' => array(
          'description' => 'the uri of the property',
          'type' => 'varchar',
          'length' => '2048',
          'not null' => TRUE,
        ),
        'class' => array(
          'description' => 'the uri of the range class',
          'type' => 'varchar',
          'length' => '2048',
          'not null' => TRUE,
        ),
      ),
      'primary key' => array('num'),
    );
    
    $schema['inverses'] = array(
      'description' => 'hold information about ranges of triple store properties',
      'fields' => array(
        'num' => array(
          'description' => 'the Serial Number for this pairing',
          'type' => 'serial',
          'size' => 'normal',
          'not null' => TRUE,
        ),
        'property' => array(
          'description' => 'the uri of the property',
          'type' => 'varchar',
          'length' => '2048',
          'not null' => TRUE,
        ),
        'inverse' => array(
          'description' => 'the uri of the inverse property',
          'type' => 'varchar',
          'length' => '2048',
          'not null' => TRUE,
        ),
      ),
      'primary key' => array('num'),
    );

    return $schema;
  }
  
}
