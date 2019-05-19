<?php

/**
 * @file
 * Contains \Drupal\wisski_salz\Plugin\wisski_salz\Engine\Sparql11RdfEngineWithPB.
 */

namespace Drupal\wisski_adapter_rdf\Plugin\wisski_salz\Engine;

use Drupal\Core\Form\FormStateInterface;
use Drupal\wisski_salz\Plugin\wisski_salz\Engine\Sparql11Engine;
use Drupal\wisski_pathbuilder\PathbuilderEngineInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\wisski_salz\AdapterHelper;
use Drupal\wisski_adapter_sparql11_pb\Plugin\wisski_salz\Engine\Sparql11EngineWithPB;

use Drupal\wisski_core\Entity\WisskiEntity;

use Drupal\wisski_adapter_sparql11_pb\Query\Query;
use \EasyRdf;

/**
 * Wiki implementation of an external entity storage client.
 *
 * @Engine(
 *   id = "sparql11_rdf_with_pb",
 *   name = @Translation("Sparql 1.1 RDF With Pathbuilder"),
 *   description = @Translation("Provides access to a SPARQL 1.1 endpoint with RDF and is configurable via a Pathbuilder")
 * )
 */
class Sparql11RdfEngineWithPB extends Sparql11EngineWithPB implements PathbuilderEngineInterface  {


  /******************* BASIC Pathbuilder Support ***********************/
  
  /**
   * @{inheritdoc}
   */
  public function getPrimitiveMapping($step) {
    
    $info = [];

    // this might need to be adjusted for other standards than rdf/owl
    $query = 
      "SELECT DISTINCT ?property "
      ."WHERE {  {"
        ." { ?property rdfs:range rdfs:Literal . } UNION { ?property a owl:DatatypeProperty . } UNION { ?property a rdf:Property . } UNION { ?property a owl:AnnotationProperty . } UNION { ?property a rdfs:label . } }}"
#        ."?property a owl:DatatypeProperty. "
#        ."?property rdfs:domain ?d_superclass. "
#        ."<$step> rdfs:subClassOf* ?d_superclass. }"
      ;
      
      // By Mark: TODO: Please check this. I have absolutely
      // no idea what this does, I just copied it from below
      // and I really really hope that Dorian did know what it
      // does and it will work forever.      
/*
      $query .= 
        "{"
          ."{?d_def_prop rdfs:domain ?d_def_class.}"
          ." UNION "
          ."{"
            ."?d_def_prop owl:inverseOf ?inv. "
            ."?inv rdfs:range ?d_def_class. "
          ."}"
        ."} "
        ."{ <$step> rdfs:subClassOf* ?d_def_class. } UNION { <$step> a* ?d_def_class. } UNION { <$step> a* ?inter . ?inter rdfs:subClassOf* ?d_def_class. }"
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

    $min = max(strrpos($property_uri, '/'), strrpos($property_uri, '#'));
    
    if(empty($min))
      return NULL;

    $min = $min +1;
    
    $front = substr($property_uri, 0, $min);
    $end = substr($property_uri, $min);
    
    preg_match('/^([a-zA-Z0-9]*)([^a-zA-Z0-9])/', $end, $matches);
    #dpm($property_uri, "searched");
    #dpm(serialize($min), "min");
    #dpm($matches, "found");
    #dpm($front, "front");
    #dpm($end, "end");        
    if(empty($matches))
      return NULL;
      
    $inversestart = $matches[1];
        
    if($inversestart[strlen($inversestart)-1] == "i")
      $inversestart = substr($inversestart, 0, -1);
    else
      $inversestart = $inversestart . 'i';

#      dpm($inversestart, "inv");

    // up to now this was the current code. However this is evil in case there are several answers.
    // it will then return the upper one which is bad.
    // so in case there is an easy answer, give the easy answer.
    $results = $this->directQuery(
      "SELECT ?prop WHERE {"
        ." GRAPH ?g1 {?prop a rdf:Property } . FILTER(contains(xsd:string(?prop), '" . $inversestart . $matches[2] . "')) . "
      ."}"
    );
    
    $inverse = '';
    foreach ($results as $row) {
      $inverse = $row->prop->getUri();
    }
    
#    dpm($inverse, "inv");
    $inverses[$property_uri] = $inverse;
//    \Drupal::cache()->set($cid,$inverses);
    return $inverse;
  }
  
}
