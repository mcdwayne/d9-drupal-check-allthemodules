<?php

/**
 * @file
 * Contains \Drupal\wisski_salz\Plugin\wisski_salz\Engine\Sparql11EngineWithPB.
 */

namespace Drupal\wisski_adapter_skos\Plugin\wisski_salz\Engine;

use Drupal\Core\Form\FormStateInterface;
use Drupal\wisski_salz\Plugin\wisski_salz\Engine\Sparql11Engine;
use Drupal\wisski_pathbuilder\PathbuilderEngineInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\wisski_salz\AdapterHelper;

use Drupal\wisski_adapter_skos\Query\Query;
use \EasyRdf;

/**
 * Wiki implementation of an external entity storage client.
 *
 * @Engine(
 *   id = "wisski_skos",
 *   name = @Translation("SKOS via SPARQL 1.1"),
 *   description = @Translation("Provides access to a SKOS based SPARQL 1.1 endpoint and is configurable via a Pathbuilder")
 * )
 */
class WisskiSkosEngine extends Sparql11Engine implements PathbuilderEngineInterface  {

  public function directQuery($query) {
    //ensure graph rewrite
    return parent::directQuery($query,TRUE);
  }

  /******************* BASIC Pathbuilder Support ***********************/

  /**
   * this adapter/engine provides two functions for retrieving path alternatives
   * @TODO bring that to the interface
   */
  public function providesFastMode() {
    return FALSE;
  }
  
  /**
   * this adapter/engine provides a pre-computed step alternative cache
   * @TODO bring that to the interface
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

    \Drupal::logger('WissKI path alternatives: '.($fast_mode ? 'fast mode' : "normal mode"))->debug('History: '.serialize($history)."\n".'Future: '.serialize($future));
    
    $search_properties = NULL;
    
    $last = NULL;
    if (!empty($history)) {
      $candidate = array_pop($history);
      if ($candidate === $empty_uri) {
//        \Drupal::logger('WissKI path alternatives')->error('Not a valid URI: "'.$candidate.'"');
        //as a fallback we assume that the full history is given so that every second step is a property
        //we have already popped one element, so count($history) is even when we need a property
        $search_properties = (0 === count($history) % 2);
      }
      elseif ($this->isValidUri('<'.$candidate.'>')) {
        $last = $candidate;
        if ($this->isAProperty($last) === FALSE) $search_properties = TRUE; 
      } else {
        \Drupal::logger('WissKI path alternatives')->debug('invalid URI '.$candidate);
        return array();
      }
    }
    
    $next = NULL;
    if (!empty($future)) {
      $candidate = array_shift($future);
      if ($candidate !== $empty_uri) {
        if ($this->isValidUri('<'.$candidate.'>')) {
          $next = $candidate;
          if ($search_properties === NULL) {
            if ($this->isAProperty($next) === FALSE) $search_properties = TRUE;
          } elseif ($this->isAProperty($next) === $search_properties) {
            drupal_set_message('History and Future are inconsistent','error');
          }
        } else {
          \Drupal::logger('WissKI path alternatives')->debug('invalid URI '.$candidate);
          return array();
        }
      }
    }
    
    \Drupal::logger('WissKI next '.($search_properties ? 'properties' : 'classes'))->debug('Last: '.$last.', Next: '.$next);
    //$search_properties is TRUE if and only if last and next are valid URIs and no owl:Class-es
    if ($search_properties) {
      $return = $this->nextProperties($last,$next,$fast_mode);
    } else {
      $return = $this->nextClasses($last,$next,$fast_mode);
    }
//    dpm(func_get_args()+array('result'=>$return),__FUNCTION__);
    return $return;
  }
  
  /**
   * @{inheritdoc}
   */
  public function getPrimitiveMapping($step) {
    
    \Drupal::logger('wisski sparql '.__FUNCTION__)->debug($step);
    $info = [];

    // this might need to be adjusted for other standards than rdf/owl
    $query = 
      "SELECT DISTINCT ?property "
      ."WHERE { "
        ."<$step> a owl:Class. "
        ."?property rdfs:subPropertyOf rdfs:label. "
      ."}"
      ;

    $result = $this->directQuery($query);

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

    $query = "SELECT DISTINCT ?label WHERE { <$step> <http://www.w3.org/2000/01/rdf-schema#label> ?label . } LIMIT 1";
    $result = $this->directQuery($query);
    if (count($result) > 0) {
      $info['label'] = $result[0]->label->getValue();
    }

    $query = "SELECT DISTINCT ?comment WHERE { <$step> <http://www.w3.org/2000/01/rdf-schema#comment> ?comment . } LIMIT 1";
    $result = $this->directQuery($query);
    if (count($result) > 0) {
      $info['comment'] = $result[0]->comment->getValue();
    }


    return $info;
  }

  
  
  public function isaProperty($p) {
    
    return $this->directQuery("ASK { <$p> a owl:ObjectProperty . }")->isTrue();

  }


  public function getClasses() {
  
    $out = $this->retrieve('classes','class');
    if (!empty($out)) return $out;
    $query = "SELECT DISTINCT ?class WHERE { ?class a owl:Class . }";  
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
    $query = "SELECT DISTINCT ?property WHERE { ?property a owl:ObjectProperty . }";  
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
    \Drupal::logger(__METHOD__)->debug('class: '.$class.', class_after: '.$class_after);
    $output = $this->getPropertiesFromCache($class,$class_after);
    if ($output === FALSE) {
      //drupal_set_message('none in cache');
      $output = $this->getPropertiesFromStore($class,$class_after,$fast_mode);
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

  public function getPropertiesFromStore($class=NULL,$class_after = NULL,$fast_mode=FALSE) {

    $query = "SELECT DISTINCT ?property WHERE {"
      ."?property a owl:ObjectProperty. ";
    if ($fast_mode) {  
      if (isset($class)) $query .= "?property rdfs:domain <$class>. ";
      if (isset($class_after)) $query .= "?property rdfs:range <$class_after>.";
    } else {
      if (isset($class)) {
        $query .= 
          "{"
            ."{?d_def_prop rdfs:domain ?d_def_class.}"
            ." UNION "
            ."{"
              ."?d_def_prop owl:inverseOf ?inv. "
              ."?inv rdfs:range ?d_def_class. "
            ."}"
          ."} "
          ."<$class> rdfs:subClassOf* ?d_def_class. "
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
          ."}";
      }
      if (isset($class_after)) {
        $query .= "{"
            ."{ "
                ."{?r_def_prop rdfs:range ?r_def_class.} "
                ."UNION "
                ."{ "
                  ."?r_def_prop owl:inverseOf ?inv. "
                  ."?inv rdfs:domain ?inv. "
                ."} "
              ."} "
            ."<$class_after> rdfs:subClassOf* ?r_def_class. "
          ."}"
          ."{"
            ."{?r_def_prop rdfs:subPropertyOf* ?property.} "
          ."UNION "
            ."{ "
              ."?property rdfs:subPropertyOf+ ?r_def_prop. "
              ."FILTER NOT EXISTS { "
                ."{ "
                  ."?mid_prop rdfs:subPropertyOf+ ?r_def_prop. "
                  ."?property rdfs:subPropertyOf* ?mid_prop. "
                ."} "
                ."{?mid_prop rdfs:range ?any_range.}"
                  ." UNION "
                  ."{ "
                    ."?mid_prop owl:inverseOf ?mid_inv. "
                    ."?mid_inv rdfs:domain ?any_domain. "
                  ."}"
                ."}"
              ."} "
            ."}"
          ."} ";
      }  
    }
    $query .= "}";
    $result = $this->directQuery($query);
    $output = array();
    foreach ($result as $obj) {
      $prop = $obj->property->getUri();
      $output[$prop] = $prop;
    }
    return $output;
  }

  public function nextClasses($property=NULL,$property_after = NULL,$fast_mode=FALSE) {
    
    if (!isset($property) && !isset($property_after)) return $this->getClasses();
    \Drupal::logger(__METHOD__)->debug('property: '.$property.', property_after: '.$property_after);
    $output = $this->getClassesFromCache($property,$property_after);
    if ($output === FALSE) {
      //drupal_set_message('none in cache');
      $output = $this->getClassesFromStore($property,$property_after,$fast_mode);
    }
    uksort($output,'strnatcasecmp');
    return $output;
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
    
    //DB version
    $rng_classes = $this->retrieve('ranges','class','property',$property);
    if (isset($property_after)) $dom_classes = $this->retrieve('domains','class','property',$property_after);
    else return $rng_classes;
    return array_intersect_key($rng_classes,$dom_classes);
  }

  public function getClassesFromStore($property=NULL,$property_after = NULL,$fast_mode=FALSE) {
  
    $query = "SELECT DISTINCT ?class WHERE {"
      ."?class a owl:Class. ";
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
    $query .= "}";
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
    $pb = $this->getPbForThis();

#    drupal_set_message("yay!" . $entityid . " and " . $bundleid);
    
    $entityid = $this->getDrupalId($entityid);
    
    $ret = array();
    
    $groups = $pb->getGroupsForBundle($bundleid);
    
    foreach($groups as $group) {
      $paths = $pb->getImagePathIDsForGroup($group->id());
      
#      drupal_set_message("paths: " . serialize($paths));
            
      foreach($paths as $pathid) {
      
        $path = \Drupal\wisski_pathbuilder\Entity\WisskiPathEntity::load($pathid);
        
#        drupal_set_message(serialize($path));
        
#        drupal_set_message("thing: " . serialize($this->pathToReturnValue($path->getPathArray(), $path->getDatatypeProperty(), $entityid, 0, NULL, 0)));
        
        // position 0 is wrong here, but it will hold for now
        $ret = array_merge($ret, $this->pathToReturnValue($path, $pb, $entityid, 0, NULL));
      } 
    }
    
#    drupal_set_message("returning: " . serialize($ret));
    
    return $ret;
  }
  
  public function getDrupalId($uri) {
    #dpm($uri, "uri");
    
    if(is_numeric($uri) !== TRUE) {
      $id = AdapterHelper::getDrupalIdForUri($uri);
    } else {
      $id = $uri;
    }
    return $id;
  }
  
  public function getUriForDrupalId($id) {
    // danger zone: if id already is an uri e.g. due to entity reference
    // we load that. @TODO: I don't like that.
#    drupal_set_message("in: " . serialize($id));
#    drupal_set_message("vgl: " . serialize(is_int($id)));
    if(is_numeric($id) === TRUE) {
      $uri = AdapterHelper::getUrisForDrupalId($id);
      // just take the first one for now.
      $uri = current($uri);
    } else {
      $uri = $id;
    }
    
#    drupal_set_message("out: " . serialize($uri));
    return $uri;
  }

  /**
   *
   *
   *
   */
  public function getBundleIdsForEntityId($entityid) {
    
    $pb = $this->getPbForThis();
#    dpm($pb,$this->adapterId().' Pathbuilder');
#    dpm($entityid, "eid");

    $uri = $this->getUriForDrupalId($entityid);    
    
    #$uri = str_replace('\\', '/', $entityid);

#    drupal_set_message("parse url: " . serialize(parse_url($uri)));

    $url = parse_url($uri);

    if(!empty($url["scheme"]))
      $query = "SELECT ?class WHERE { <" . $uri . "> a ?class }";
    else
      $query = "SELECT ?class WHERE { " . $entityid . " a ?class }";
#    dpm($query);
    $result = $this->directQuery($query);
    
#    drupal_set_message("res: " . serialize($result));
    
   $out = array();
    foreach($result as $thing) {
#      dpm($thing);   
      // ask for a bundle from the pb that has this class thing in it
      $groups = $pb->getAllGroups();
      
#      drupal_set_message("groups: " . count($groups) . " " . serialize($groups));

      $i = 0;
      
      foreach($groups as $group) {
        // this does not work for subgroups
        #$path_array = $group->getPathArray();
#        dpm($group);                
#        $path_array = $this->getClearPathArray($group, $pb);
        $path_array = $this->getClearGroupArray($group, $pb);
        $i++;
#        dpm($path_array); 
#        drupal_set_message("p_a " . $i . " " . $group->getName() . " " . serialize($path_array));
        
        if(empty($group) || empty($path_array))
          continue;
        
#        dpm($path_array[ count($path_array)-1]);
#        dpm($thing->class->getUri("text"));

        // this checks if the last element is the same
        // however this is evil whenever there are several elements in the path array
        // typically subgroups ask for the first element part.        
        if($path_array[ count($path_array)-1] == $thing->class->getUri() || $path_array[0] == $thing->class->getUri()) {
#          dpm("found smthg!");
          $pbpaths = $pb->getPbPaths();
          
#          drupal_set_message(serialize($pbpaths[$group->id()]));
          
          if(!empty($pbpaths[$group->id()])) {
            $elem = $pbpaths[$group->id()];
            // priorize top groups to the front if the array
#            dpm($elem);
            if(empty($elem['parent'])) {
#              dpm("I resort...");
              $tmpout = $out;
              $out = array();
              $out[$pbpaths[$group->id()]['bundle']] = $pbpaths[$group->id()]['bundle'];
              $out = array_merge($out, $tmpout);
            }
            $out[$pbpaths[$group->id()]['bundle']] = $pbpaths[$group->id()]['bundle'];
          }
        }
      }
    }

#    drupal_set_message("serializing out: " . serialize($out));

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
   * Gets the common part of a group or path
   * that is clean from subgroup-fragments
   */
  public function getClearPathArray($path, $pb) {
    // We have to modify the path-array in case of subgroups.
    // Usually if we have a subgroup path x0 y0 x1 we have to skip x0 y0 in
    // the paths of the group.
if (!is_object($path)) {ddebug_backtrace(); return array();}
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
      $grouppath = $this->getClearPathArray($group, $pathbuilder);
                   
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
    
    if(is_null($limit) == FALSE && is_null($offset) == FALSE && empty($count)) {
      $query .= " LIMIT $limit OFFSET $offset ";
    }
     
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
#    dpm($outarr, "outarr");
#    return;
    if (empty($outarr) && $count) return 0;
    return $outarr;
  }

  public function loadEntity($id) {
#    drupal_set_message("b1: $id " . microtime());
        
    $out = array();
#    $uri = str_replace('\\', '/', $id);

    $uri = $this->getUriForDrupalId($id);
#    dpm(serialize($uri), 'uri!');

#    drupal_set_message("parse url: " . serialize(parse_url($uri)));

    $url = parse_url($uri);
#    drupal_set_message("b2: " . microtime());

    if(!empty($url["scheme"]))
      $query = "SELECT * WHERE { { <$uri> ?p ?o } UNION { ?s ?p <$uri> } } LIMIT 1"; 
    else
      $query = 'SELECT * WHERE { ?s ?p "' . $id . '" } LIMIT 1';  
#    drupal_set_message("b3: " . microtime());    
    $result = $this->directQuery($query);
#    drupal_set_message("b4: " . microtime());
    foreach($result as $thing) {
#      $uri = $thing->s->dumpValue("text");
#      $uri = str_replace('/','\\',$uri);
      $out = array('eid' => $id, 'bundle' => 'e21_person', 'name' => 'frizt');

#      $out[$uri] = array('eid' => $uri, 'bundle' => 'e21_person', 'name' => 'frizt');#$thing->s->dumpValue("text"), 'bundle' => 'e21_person', 'name' => 'frizt');
#      $i++;
    }
#    drupal_set_message("b5: " . microtime());
#    drupal_set_message("load single");

#    dpm($out);
    
    return $out;
  }
  
  public function loadMultipleEntities($ids = NULL) {
#    dpm($this->getConfiguration());
#    $this->entity_info = Yaml::parse($this->entity_string);
#    dpm($this->entity_info,__METHOD__);
#    if (is_null($ids)) return $this->entity_info;
    $query = "SELECT ?s WHERE { ?s a/a owl:Class}";
    
    $result = $this->directQuery($query);
    
#    drupal_set_message(serialize($result));
    
    $out = array();
    foreach($result as $thing) {
      
      $uri = $thing->s->dumpValue("text");
      #$uri = str_replace('/','\\',$uri);
      
      $uri = $this->getUriForDrupalId($uri);
    
#      drupal_set_message("my uri is: " . htmlentities($uri));
      
      $out[$uri] = array('eid' => $uri, 'bundle' => 'e21_person', 'name' => 'frizt');
    }
    
#    drupal_set_message("load Mult...");
    
    return $out;
    
    return array_intersect_key($this->entity_info,array_flip($ids));
  }
    
  /**
   * @inheritdoc
   */
  public function hasEntity($entity_id) {
 
#    dpm($entity_id, "eid");
    
    $ent = $this->loadEntity($entity_id);

#    dpm(!empty($ent), "ent");

    return !empty($ent);
  }
/* 
  public function groupToReturnValue($group, $primitive = NULL, $eid = NULL) {
    $sparql = "SELECT DISTINCT * WHERE { ";

    $sparql .= $this->generateTriplesForPath($pb, $group, '', NULL, NULL, 0, 0, TRUE);
*/
/*
    foreach($patharray as $key => $step) {
      if($key % 2 == 0) 
        $sparql .= "?x$key a <$step> . ";
      else
        $sparql .= '?x' . ($key-1) . " <$step> ?x" . ($key+1) . " . ";    
    }
    
    if(!empty($primitive)) {
      $sparql .= "?x$key <$primitive> ?out . ";
    }
*/
/*    
    if(!empty($eid)) {
      // rename to uri
      $eid = $this->getUriForDrupalId($eid);
    
#      $eid = str_replace("\\", "/", $eid);
      $url = parse_url($eid);
      
      if(!empty($url["scheme"]))
        $sparql .= " FILTER (?x0 = <$eid> ) . ";
      else
        $sparql .= " FILTER (?x0 = \"$eid\" ) . ";
    }
    
    $sparql .= " } ";

    
#    drupal_set_message("spq: " . serialize($sparql));
#    drupal_set_message(serialize($this));
    
    $result = $this->directQuery($sparql);
    
#    drupal_set_message(serialize($result));
    
    $out = array();
    foreach($result as $thing) {
 #     drupal_set_message("we got something!");
      $name = 'x' . (count($patharray)-1);
      if(!empty($primitive))
        $out[] = $thing->out->getValue();
      else
        $out[] = $thing->$name->dumpValue("text");
    }
    
    return $out;
  
  }
*/
  public function pathToReturnValue($path, $pb, $eid = NULL, $position = 0, $main_property = NULL) {
#    dpm("ptrv");
    if(!$path->isGroup())
      $primitive = $path->getDatatypeProperty();
    else
      $primitive = NULL;
      
    $disamb = $path->getDisamb();
    
#    drupal_set_message("pa: " . serialize($patharray) . " disamb: " . $disamb . " and eid " . $eid); 
#    dpm($path, "path");
    #dpm($pb, "pb");
#    dpm($eid, "eid");
    
    // also
    if($disamb > 0)
      $disamb = ($disamb-1)*2;
    else
      $disamb = NULL;
      
#    drupal_set_message(" after pa: " . serialize($patharray) . " disamb: $disamb and " . serialize(is_null($disamb))); 

#  dpm("yay!");  

    $sparql = "SELECT DISTINCT * WHERE { ";

#    $sparql .= $this->generateTriplesForPath($pb, $path, $primitive, , NULL, 0, 0, TRUE);

/*
    foreach($patharray as $key => $step) {
      if($key % 2 == 0) 
        $sparql .= "?x$key a <$step> . ";
      else
        $sparql .= '?x' . ($key-1) . " <$step> ?x" . ($key+1) . " . ";    
    }
    
    if(!empty($primitive)) {
      $sparql .= "?x$key <$primitive> ?out . ";
    }
*/


    if(!empty($eid)) {
      // rename to uri
      $eid = $this->getUriForDrupalId($eid);
    
#      $eid = str_replace("\\", "/", $eid);
#      $url = parse_url($eid);
      if($path->isGroup()) {
        $pbarray = $pb->getPbPaths();
        $parentpathid = $pbarray[$path->id()]["parent"];
        
        // if there is a parent path
        if(!empty($parentpathid)) {
          
          // load the parent path
          $parentpath = \Drupal\wisski_pathbuilder\Entity\WisskiPathEntity::load($parentpathid);
          
          $sparql .= $this->generateTriplesForPath($pb, $path, '', $eid, NULL, 0, floor(count($parentpath->getPathArray())/2), FALSE, NULL, 'entity_reference'); 
          
#          dpm($sparql, $path->getName());
          
#          if(count($parentpath->getPathArray()) > 2)
#           return;
        } else {
          // assume we start from zero
          $sparql .= $this->generateTriplesForPath($pb, $path, '', $eid, NULL, 0, 0, FALSE, NULL, 'entity_reference');
#        $sparql .= $this->generateTriplesForPath($pb, $path, '', $eid, NULL, 0, floor(count($path->getPathArray())/2), FALSE, NULL, 'entity_reference');
#        dpm($sparql, $path->getName());
#        if(count($path->getPathArray()) > 3)
#          return;
        }
      } else
        $sparql .= $this->generateTriplesForPath($pb, $path, '', $eid, NULL, 0, 0, FALSE, NULL, 'field');
#      dpm($sparql, $path->getName());
    } else {
      drupal_set_message("No EID for data. Error. ", 'error');
    }
/*      
      if(!empty($url["scheme"]))
        if(!empty($position))
          $sparql .= " FILTER (?x$position = <$eid> ) . ";
        else
          $sparql .= " FILTER (?x0 = <$eid> ) . ";
      else
        if(!empty($position))
          $sparql .= " FILTER (?x$position = \"$eid\" ) . ";
        else
          $sparql .= " FILTER (?x0 = \"$eid\" ) . ";
    }
*/    
    $sparql .= " } ";

    
#    drupal_set_message("spq: " . serialize($sparql));
#    drupal_set_message(serialize($this));
    
    $result = $this->directQuery($sparql);
    
    $out = array();
    foreach($result as $thing) {
#      drupal_set_message("thing is: " . serialize($thing));
#      $name = 'x' . (count($patharray)-1);
      $name = 'x' . (count($path->getPathArray())-1);
      if(!empty($primitive)) {
        if(empty($main_property)) {
          $out[] = $thing->out->getValue();
        } else {
          
          $outvalue = $thing->out->getValue();

          // special case: DateTime... render this as normal value for now.
          if(is_a($outvalue, "DateTime")) {
            $outvalue = (string)$outvalue->format('Y-m-d\TH:i:s.u');;
          }
          
#          if($main_property == "target_id")
#            $outvalue = $this->getDrupalId($outvalue);
          
          if(is_null($disamb) == TRUE)
            $out[] = array($main_property => $outvalue);
          else {
#            drupal_set_message("disamb: " . serialize($disamb));
#            dpm($thing);
#            drupal_set_message("pa: " . serialize($thing));
          #  drupal_set_message("res: " . serialize($result));
            $disambname = 'x'.$disamb;
            if(!isset($thing->{$disambname}))
              $out[] = array($main_property => $outvalue);
            else
              $out[] = array($main_property => $outvalue, 'wisskiDisamb' => $thing->{$disambname}->dumpValue("text"));
          }
        }
      } else {
        if(empty($main_property)) {
          $out[] = $thing->{$name}->dumpValue("text");
        } else { 
        
          $outvalue = $thing->{$name}->dumpValue("text");
          
#          if($main_property == "target_id")
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

#dpm($out, __METHOD__);
    return $out;
    
  }
  
  /**
   * Gets the PB object for a given adapter id
   * @return a pb object
   */
  public function getPbForThis() {
    $pbs = \Drupal\wisski_pathbuilder\Entity\WisskiPathbuilderEntity::loadMultiple();
    
    foreach($pbs as $pb) {
      // if there is no adapter set for this pb  
      if($adapter_id = $pb->getAdapterId()) {
        if ($this->adapterId() == $adapter_id) return $pb;
      }      
    }
    return NULL;
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

    // this approach will be not fast enough in the future...
    // the pbs have to have a better mapping of where and how to find fields
    $pbs = \Drupal\wisski_pathbuilder\Entity\WisskiPathbuilderEntity::loadMultiple();
    
    $out = array();
        
    // get the adapterid that was loaded
    // haha, this is the engine-id...
    //$adapterid = $this->getConfiguration()['id'];
        
    foreach($pbs as $pb) {
      
      // if we have no adapter for this pb it may go home.
      if(empty($pb->getAdapterId()))
        continue;
        
      $adapter = \Drupal\wisski_salz\Entity\Adapter::load($pb->getAdapterId());

      // if we have not adapter, we may go home, too
      if(empty($adapter))
        continue;
      
      // if he didn't ask for us...    
      if($this->getConfiguration()['id'] != $adapter->getEngine()->getConfiguration()['id'])
        continue;
        
      // if we find any data, we set this to true.
      $found_any_data = FALSE;
        
      foreach($field_ids as $fkey => $fieldid) {  
        #drupal_set_message("for field " . $fieldid . " with bundle " . $bundleid_in . " I've got " . serialize($this->loadPropertyValuesForField($fieldid, array(), $entity_ids, $bundleid_in, $language)));

        $got = $this->loadPropertyValuesForField($fieldid, array(), $entity_ids, $bundleid_in, $language);

#        drupal_set_message("I've got: " . serialize($got));
        
        if(empty($out))
          $out = $got;
        
        foreach($got as $eid => $value) {
          if(empty($out[$eid]))
            $out[$eid] = $got[$eid];
          else
            $out[$eid] = array_merge($out[$eid], $got[$eid]);
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
    $main_property = \Drupal\field\Entity\FieldStorageConfig::loadByName('wisski_individual', $field_id);#->getItemDefinition()->mainPropertyName();
    if(!empty($main_property))
      $main_property = $main_property->getMainPropertyName();
#     drupal_set_message("mp: " . serialize($main_property) . "for field " . serialize($field_id));
#    if (in_array($main_property,$property_ids)) {
#      return $this->loadFieldValues($entity_ids,array($field_id),$language);
#    }
#    return array();

    if(!empty($field_id) && empty($bundleid_in)) {
      drupal_set_message("Dorian ist doof, weil $field_id angefragt wurde und bundle aber leer ist.", "error");
      return;
    }
    
    // this approach will be not fast enough in the future...
    // the pbs have to have a better mapping of where and how to find fields
    $pbs = \Drupal\wisski_pathbuilder\Entity\WisskiPathbuilderEntity::loadMultiple();
    
    $out = array();
        
    // get the adapterid that was loaded
    // haha, this is the engine-id...
    //$adapterid = $this->getConfiguration()['id'];
        
    foreach($pbs as $pb) {
#      drupal_set_message("a2: " . microtime());
      // if we have no adapter for this pb it may go home.
      if(empty($pb->getAdapterId()))
        continue;
        
      $adapter = \Drupal\wisski_salz\Entity\Adapter::load($pb->getAdapterId());

      // if we have not adapter, we may go home, too
      if(empty($adapter))
        continue;
      
      // if he didn't ask for us...    
      if($this->getConfiguration()['id'] != $adapter->getEngine()->getConfiguration()['id'])
        continue;
      
      // if we find any data, we set this to true.
      $found_any_data = FALSE;
      
      foreach($entity_ids as $eid) {
#        drupal_set_message("a3: " . microtime());
        // here we should check if we really know the entity by asking the TS for it.
        // this would speed everything up largely, I think.
        // 
        // for now we assume we know the entity.
        // $entity = $this->loadEntity($eid);
#        drupal_set_message("a4: " . microtime());
        // if there is nothing, continue.
        // if(empty($entity))
        //  continue;

        if($field_id == "bundle" && !empty($bundleid_in))
          $out[$eid]["bundle"] = array($bundleid_in);

#        drupal_set_message("I am asked for fids: " . serialize($field_ids));
  
        if($field_id == "eid") {
          $out[$eid][$field_id] = array($eid);
          continue;
        }
        
        if($field_id == "name") {
          // tempo hack
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

        // every other field is an array, we guess
        // this might be wrong... cardinality?          
        if(!isset($out[$eid][$field_id]))
          $out[$eid][$field_id] = array();

        // set the bundle
        // @TODO: This is a hack and might break for multi-federalistic stores
        $pbarray = $pb->getPbEntriesForFid($field_id);
          
        // if there is no data about this path - how did we get here in the first place?
        // fields not in sync with pb?
        if(empty($pbarray["id"]))
          continue;

        $path = \Drupal\wisski_pathbuilder\Entity\WisskiPathEntity::load($pbarray["id"]);

        // if there is no path we can skip that
        if(empty($path))
          continue;

        $clearPathArray = $this->getClearPathArray($path, $pb);
 
 #         drupal_set_message("I have: " . serialize($pbarray), "error");
        if(!empty($path)) {
          // if this is question for a subgroup - handle it otherwise
          if($pbarray['parent'] > 0 && $path->isGroup()) {
#              drupal_set_message("I am asking for: " . serialize($this->getClearGroupArray($path, $pb)) . "with eid: " . serialize($eid));
            // this was the old query without evil numeric ids.
            // now we have to change all this.
            #$out[$eid][$field_id] = array_merge($out[$eid][$field_id], $this->pathToReturnValue($this->getClearGroupArray($path, $pb), NULL, $eid, 0, $main_property, $path->getDisamb()));
            // nowadays we do it otherwise
            
            #$tmp = $this->pathToReturnValue($this->getClearGroupArray($path, $pb), NULL, $eid, 0, $main_property, $path->getDisamb());
            // @TODO: ueberarbeiten
            drupal_set_message("danger zone!");
            $tmp = $this->pathToReturnValue($path, $pb, $eid, 0, $main_property);            

            foreach($tmp as $key => $item) {
              $tmp[$key]["target_id"] = $this->getDrupalId($item["target_id"]);
            }
            
            $out[$eid][$field_id] = array_merge($out[$eid][$field_id], $tmp);
            
            
#            $out[$eid][$field_id] = array_merge($out[$eid][$field_id], $this->getDrupalId($this->pathToReturnValue($this->getClearGroupArray($path, $pb), NULL, $eid, 0, $main_property, $path->getDisamb())));
#              drupal_set_message("I've got: " . serialize($out[$eid][$field_id]));
          } else {
              // it is a field?
#              $out[$eid][$field_id] = array_merge($out[$eid][$field_id], $this->pathToReturnValue($clearPathArray, $path->getDatatypeProperty(), $eid));
#              drupal_set_message("pa: " . serialize($path->getPathArray()) . " cpa: " . serialize($clearPathArray));

            // get the parentid
            $parid = $pbarray["parent"];
            
            // get the parent (the group the path belongs to) to get the common group path
            $par = \Drupal\wisski_pathbuilder\Entity\WisskiPathEntity::load($parid);

            // if there is no parent it is a ungrouped path... who asks for this?
            if(empty($par)) {
              drupal_set_message("Path " . $path->getName() . " with id " . $path->id() . " has no parent.", "error");
              continue;
            }
#              drupal_set_message("pa: " . serialize($path->getPathArray()) . " cpa: " . serialize($clearPathArray) . " cga: " . serialize($this->getClearGroupArray($par, $pb)));
            $out[$eid][$field_id] = array_merge($out[$eid][$field_id], $this->pathToReturnValue($path, $pb, $eid, count($path->getPathArray()) - count($clearPathArray), $main_property));#(count($this->getClearGroupArray($par, $pb))-1), $main_property, $path->getDisamb()));                        
#            $out[$eid][$field_id] = array_merge($out[$eid][$field_id], $this->pathToReturnValue($path->getPathArray(), $path->getDatatypeProperty(), $eid, count($path->getPathArray()) - count($clearPathArray), $main_property, $path->getDisamb()));#(count($this->getClearGroupArray($par, $pb))-1), $main_property, $path->getDisamb()));
#            drupal_set_message("smthg: " . serialize($out[$eid][$field_id]));
          }
#          drupal_set_message("bla: " . serialize($out[$eid][$field_id]));

#            drupal_set_message($path->getDisamb());
#              drupal_set_message("I loaded: " . serialize($out));
        }
        
        if(empty($out[$eid][$field_id]))
          unset($out[$eid]);
      }
    }

#    drupal_set_message("out: for " . serialize(func_get_args()) . " is: " . serialize($out));

    return $out;


  }
  
  public function getQueryObject(EntityTypeInterface $entity_type,$condition,array $namespaces) {
  
    return new Query($entity_type,$condition,$namespaces,$this);
  }
  
  public function deleteOldFieldValue($entity_id, $fieldid, $value, $pb) {
    // get the pb-entry for the field
    // this is a hack and will break if there are several for one field
    $pbarray = $pb->getPbEntriesForFid($fieldid);
    
    $path = \Drupal\wisski_pathbuilder\Entity\WisskiPathEntity::load($pbarray['id']);

    if(empty($path))
      return;
      
#   if(!drupal_validate_utf8($value)) {
#     $value = utf8_encode($value);
#   }

    $clearPathArray = $this->getClearPathArray($path, $pb);
#    dpm($clearPathArray);
#    $group = \Drupal\wisski_pathbuilder\Entity\WisskiPathEntity::load($pbarray['parent']);
    
#    $path_array = $path->getPathArray();
    
    $diff = count($path->getPathArray()) - count($clearPathArray);
    
#    $sparql = "SELECT DISTINCT * WHERE { GRAPH ?g {";
    $sparql = "SELECT DISTINCT * WHERE {";
    foreach($clearPathArray as $key => $step) {
#    for($i=(count($path->getPathArray())-1);$i>= (count($path->getPathArray())-count($clearPathArray)-1); $i--) {
      if($key % 2 == 0) 
        $sparql .= "?x" . ($key+$diff) . " a <$step> . ";
      else
        $sparql .= '?x' . ($key+$diff-1) . " <$step> ?x" . ($key+$diff+1) . " . ";    
    }
    
    $primitive = $path->getDatatypeProperty();
    
    // dorian special case -> "empty" @TODO - this is evil!
    if(!empty($primitive) && $primitive != "empty") {
      if(empty($value)) {
        $sparql .= "?x" . ($key+$diff) . " <$primitive> ?out . ";
      } else {
        $sparql .= "?x" . ($key+$diff) . " <$primitive> '" . $this->escapeSparqlLiteral($value) ."' . ";
      }
    }
    
    if(!empty($entity_id)) {
      // rename to uri
      $eid = $this->getUriForDrupalId($entity_id);    
#      $eid = str_replace("\\", "/", $entity_id);
      $url = parse_url($eid);
      
      if(!empty($url["scheme"]))
        $sparql .= " FILTER (?x$diff = <$eid> ) . ";
      else
        $sparql .= " FILTER (?x$diff = \"$eid\" ) . ";
    }
    
#    $sparql .= " } }";
    $sparql .= " }";
    $result = $this->directQuery($sparql);

#    drupal_set_message("I query: " . $sparql);

#    drupal_set_message(serialize($result));

    $outarray = array();

    foreach($result as $key => $thing) {
      $outarray[$key] = array();
      
#      drupal_set_message("thing is: " . serialize($thing));
      
#      for($i=(count($clearPathArray)-1);$i>= 0; $i--) {

      for($i=$diff; $i<count($clearPathArray)+$diff; $i++) {
        $name = "x" . $i;
        if($i % 2 == 0) {
#          $name = "x" . $i; 
#          drupal_set_message("name is: " . $name);
          $outarray[$key][$i] = $thing->{$name}->dumpValue("text");
        } else {
          $outarray[$key][$i] = $clearPathArray[($i-$diff)];
        }
      }

/*
      for($i=(count($path->getPathArray())-1);$i>= (count($path->getPathArray())-count($clearPathArray)-1); $i--) {
        $name = "x" . $i;
        if($i % 2 == 0) {
#          $name = "x" . $i; 
#          drupal_set_message("name is: " . $name);
          $outarray[$key][$i] = $thing->{$name}->dumpValue("text");
        } else {
          $outarray[$key][$i] = $clearPathArray[$i];
        }
      }
 */     
#      drupal_set_message("my outarr1 is: " . serialize($outarray));
      
      ksort($outarray[$key]);
   #     drupal_set_message("we got something!");
  #    $name = 'x' . (count($clearPathArray)-1);
      if(!empty($primitive))
        if(empty($value)) {
          $outarray[$key]["primitive"] = $primitive;
          $outarray[$key]["out"] = $thing->out->getValue();
        } else {
          $outarray[$key]["primitive"] = $primitive;
          $outarray[$key]["out"] = $value;
        }
     # else
     #   $out[] = $thing->$name->dumpValue("text");
#    }

#      drupal_set_message("my outarr is: " . serialize($outarray));
    
#    drupal_set_message("spq: " . serialize($sparql));
#    drupal_set_message(serialize($this));
    
        
    // add graph handling
      $sparqldelete = "DELETE DATA { " ;
 
      $arr = $outarray[$key];
#      dpm($path->getDisamb()); 
      $i=0;
      
      // is there a disamb?
      if($path->getDisamb() > 0 && isset($arr[($path->getDisamb()-2)*2])) {
        $i = ($path->getDisamb()-2)*2;
        
        $sparqldelete .= "<" . $arr[$i++] . "> ";
        $sparqldelete .= "<" . $arr[$i++] . "> ";
        $sparqldelete .= "<" . $arr[$i++] . "> ";
      } else { // no disamb - cut in the end!
        // -3 because out and primitive
        $maxi = count($arr)-3;
        
        $sparqldelete .= "<" . $arr[$maxi] . "> ";
        $sparqldelete .= "<" . $arr['primitive'] . "> ";
        $sparqldelete .= "'" . $this->escapeSparqlLiteral($arr['out']) . "' ";
      }
      
      $sparqldelete .= " } ";
      
      $result = $this->directUpdate($sparqldelete);    
    
#    drupal_set_message("delete query: " . htmlentities($sparqldelete));
    
    }
#    drupal_set_message("I delete field $field from entity $entity_id that currently has the value $value");
  }

  /**
   * Create a new entity
   * @param $entity an entity object
   * @return TRUE on success
   */
  public function createEntity($entity) {
    #$uri = $this->getUri($this->getDefaultDataGraphUri());
    
    $bundleid = $entity->bundle();

    $pbs = \Drupal\wisski_pathbuilder\Entity\WisskiPathbuilderEntity::loadMultiple();
    
    $out = array();
        
    // get the adapterid that was loaded
    // haha, this is the engine-id...
    //$adapterid = $this->getConfiguration()['id'];
        
    foreach($pbs as $pb) {
#      drupal_set_message("a2: " . microtime());
      // if we have no adapter for this pb it may go home.
      if(empty($pb->getAdapterId()))
        continue;
        
      $adapter = \Drupal\wisski_salz\Entity\Adapter::load($pb->getAdapterId());

      // if we have not adapter, we may go home, too
      if(empty($adapter))
        continue;
      
      // if he didn't ask for us...    
      if($this->getConfiguration()['id'] != $adapter->getEngine()->getConfiguration()['id'])
        continue;
     
      $groups = $pb->getGroupsForBundle($bundleid);

      // for now simply take the first one.    
      $groups = current($groups);

      $triples = $this->generateTriplesForPath($pb, $groups, '', NULL, NULL, 0, 0, TRUE);
      
      $sparql = "INSERT DATA { GRAPH <" . $this->getDefaultDataGraphUri() . "> { " . $triples . " } } ";
      #dpm($sparql, "spargel");      
      $result = $this->directUpdate($sparql);
    
      $uri = explode(" ", $triples, 2);
      
      $uri = substr($uri[0], 1, -1);
      
      $uri = $this->getDrupalId($uri);
      
    }
#    dpm($groups, "bundle");
        
#    $entity->set('id',$uri);
    $entity->set('eid',$uri);
    
#    "INSERT INTO { GRAPH <" . $this->getDefaultDataGraphUri() . "> { " 
    
  }

  public function getUri($prefix) {
    return uniqid($prefix);
  }
  
  public function getDefaultDataGraphUri() {
    // here we should return a default graph for this store.
    return "graf://dr.acula/";
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
   *              of concepts from the beginning.
   * @param $write Is this a write or a read-request?
   * @param $mode defaults to 'field' - but may be 'group' or 'entity_reference' in special cases
   */
  public function generateTriplesForPath($pb, $path, $primitiveValue = "", $subject_in = NULL, $object_in = NULL, $disambposition = 0, $startingposition = 0, $write = FALSE, $op = '=', $mode = 'field') {
#dpm(func_get_args(), __METHOD__);
    // the query construction parameter
    $query = "";

    // if we disamb on ourself, return.
    if($disambposition == 0 && !empty($object_in)) return "";

    // get the clearArray of this path, we skip anything that is in upper groups.
    if($mode == 'field')
      $clearPathArray = $this->getClearPathArray($path, $pb);
    if($mode == 'entity_reference')
      $clearPathArray = $path->getPathArray();  

#    dpm($path->id() . ' and ' . $path->isGroup() . ' yay!');    
#    dpm($clearPathArray, "cpa!");
#    dpm($mode, "mode!");
    
    // in case of disamb etc. we have to add the countdiff
    // first check if there is any real clearpath
    if(count($clearPathArray) > 2) {
      $countdiff = count($path->getPathArray()) - count($clearPathArray);
    } else {
      $countdiff = 0;
    }  
#    $countdiff = 0;
#    dpm($clearPathArray, "cpa");
    
    // old uri pointer
    $olduri = NULL;
    // old key pointer
    $oldkey = NULL;
    
    // if the old uri is empty we assume there is no uri and we have to
    // generate one in write mode. In ask mode we make variable-questions
    
    // get the default datagraphuri    
    $datagraphuri = $this->getDefaultDataGraphUri();

#    dpm($clearPathArray, "cpa");
#    dpm($key+$countdiff, "diff");
#    dpm($startingposition, "start");
    
    // iterate through the given path array
    foreach($clearPathArray as $key => $value) {
      
      $localkey = $key+$countdiff;
      
      // skip anything that is smaller than $startingposition.
      if($localkey < ($startingposition*2)) 
        continue;
      
      // basic initialisation
      $uri = NULL;
      
      // if we may write, we generate uris
      if($write) {
        $uri = $this->getUri($datagraphuri);
      }
      
      if($localkey % 2 == 0) {
        // if it is the first element and we have a subject_in
        // then we have to replace the first element with subject_in
        // and typically we don't do a type triple. So we skip the rest.
        if($key == ($startingposition*2) && !empty($subject_in)) {
          $olduri = $subject_in;
          continue;
        }
        
        // if the key is the disambpos
        // and we have an object
        if($localkey == ($disambposition*2) && !empty($object_in)) {
          $uri = $object_in;
        } else {
          // if it is not the disamb-case we add type-triples        
          if($write) 
            $query .= "<$uri> a <$value> . ";
          else
            $query .= "?x$localkey a <$value> . ";
        }
        
        // magic function
        if($localkey > 0 && !empty($prop)) { 
          if($write) {
            $query .= "<$olduri> <$prop> <$uri> . ";
          } else {
            $inverse = $this->getInverseProperty($prop);
            // if there is an inverse, don't do any unions
            if(empty($inverse)) {
              if(!empty($olduri))
                $query .= "<$olduri> ";
              else
                $query .= "?x$oldkey ";
          
              $query .= "<$prop> ";
                    
              if(!empty($uri))
                $query .= "<$uri> . ";
              else
                $query .= "?x$localkey . ";
            } else { // if there is an inverse, make a union
              $query .= "{ { ";
              // Forward query part
              if(!empty($olduri))
                $query .= "<$olduri> ";
              else
                $query .= "?x$oldkey ";
          
              $query .= "<$prop> ";
                    
              if(!empty($uri))
                $query .= "<$uri> . ";
              else
                $query .= "?x$localkey . ";
              
              $query .= " } UNION { ";

              // backward query part
          
              if(!empty($uri))
                $query .= "<$uri> ";
              else
                $query .= "?x$localkey "; 
          
              $query .= "<$inverse> ";

              if(!empty($olduri))
                $query .= "<$olduri> . ";
              else
                $query .= "?x$oldkey . ";
                            
              $query .= " } } . "; 
            }
          }
        }
         
         // if this is the disamb, we may break.
         if($localkey == ($disambposition*2) && !empty($object_in))
           break;
          
         $olduri = $uri;
         $oldkey = $localkey;
      } else {
        $prop = $value;
      }
    }

    // get the primitive for this path if any    
    $primitive = $path->getDatatypeProperty();
    
    if(!empty($primitive) && empty($object_in) && !$path->isGroup()) {
      if(!empty($olduri))
        $query .= "<$olduri> ";
      else
        $query .= "?x$oldkey ";
      
      $query .= "<$primitive> ";
      
      if(!empty($primitiveValue)) {
        
        // we have to escape it otherwise the sparql query may break
        $primitiveValue = $this->escapeSparqlLiteral($primitiveValue);

        if($op == '=') 
          $query .= "'" . $primitiveValue . "' . ";
        else {
          $regex = null;
          if($op == '<>')
            $op = '!=';
          if($op == 'STARTS_WITH') {
            $regex = true;
            $primitiveValue = '^' . $primitiveValue;
          }
          
          if($op == 'ENDS_WITH') {
            $regex = true;
            $primitiveValue = '' . $primitiveValue . '$';
          }
          
          if($op == 'CONTAINS') {
            $regex = true;
            $primitiveValue = '' . $primitiveValue . '", "i';
          }
          
        
          if($regex || $op == 'BETWEEN' || $op == 'IN' || $op == 'NOT IN')
            $query .= ' ?out . FILTER ( regex ( ?out, "' . $this->escapeSparqlRegex($primitiveValue) . '" ) ) . ';
          else
            // we have to use STR() otherwise we may get into trouble with
            // datatype and lang comparisons
            $query .= ' ?out . FILTER ( STR(?out) ' . $op . ' "' . $primitiveValue . '" ) . ';
        }
      } else
        $query .= " ?out . ";
    }

#    dpm($query);

    return $query;
  }
  
  public function addNewFieldValue($entity_id, $fieldid, $value, $pb) {
#    drupal_set_message("I get: " . $entity_id, " with fid " . $fieldid . " and value " . $value);
#    drupal_set_message(serialize($this->getUri("smthg")));
    $datagraphuri = $this->getDefaultDataGraphUri();

    $pbarray = $pb->getPbEntriesForFid($fieldid);
    
    $path = \Drupal\wisski_pathbuilder\Entity\WisskiPathEntity::load($pbarray['id']);
    #dpm($entity_id, "I add!");
#    drupal_set_message("smthg: " . serialize($this->generateTriplesForPath($pb, $path, NULL, "http://test.me/12", "http://argh.el/235", 2, TRUE)));

    if(empty($path))
      return;
      
#    $entity_id = $this->getUriForDrupalId($entity_id);
      
#    if(!drupal_validate_utf8($value)) {
#      $value = utf8_encode($value);
#    }

#    $clearPathArray = $this->getClearPathArray($path, $pb);
#    $path->setDisamb(1);
#    $path->save();

    if($path->getDisamb()) {
      $sparql = "SELECT * WHERE { GRAPH ?g { ";
#      $sparql .= $this->generateTriplesForPath($pb, $path, $value, NULL, NULL, NULL, 0, FALSE);
      $sparql .= $this->generateTriplesForPath($pb, $path, $value, NULL, NULL, NULL, $path->getDisamb()-1, FALSE);
      $sparql .= " } }";
      
#     drupal_set_message("query: " . serialize($sparql) . " disamb on: " . $path->getDisamb());
      
      $disambresult = $this->directQuery($sparql);
  
      if(!empty($disambresult))
        $disambresult = current($disambresult);      
#      drupal_set_message("rais: " . serialize($result));
    }
    
    // rename to uri
    $subject_uri = $this->getUriForDrupalId($entity_id);
        
#    $subject_uri = str_replace("\\", "/", $entity_id);

    $sparql = "INSERT DATA { GRAPH <" . $datagraphuri . "> { ";
#    drupal_set_message(serialize($path), "I would do: ");
#    drupal_set_message(serialize($eid
#    drupal_set_message("subj: " . serialize($subject_uri) . " obj: " . serialize($this->getUriForDrupalId($value)));

#    $position = 

    if($path->isGroup()) {
      $sparql .= $this->generateTriplesForPath($pb, $path, "", $subject_uri, $this->getUriForDrupalId($value), (count($path->getPathArray())-1)/2, NULL, TRUE, '', 'entity_reference');
    } else {
      if(empty($path->getDisamb()))
        $sparql .= $this->generateTriplesForPath($pb, $path, $value, $subject_uri, NULL, NULL, NULL, TRUE);
      else {
 #       drupal_set_message("disamb: " . serialize($disambresult) . " miau " . $path->getDisamb());
        if(empty($disambresult) || empty($disambresult->{"x" . $path->getDisamb()*2}) )
          $sparql .= $this->generateTriplesForPath($pb, $path, $value, $subject_uri, NULL, NULL, NULL, TRUE);
        else
          $sparql .= $this->generateTriplesForPath($pb, $path, $value, $subject_uri, $disambresult->{"x" . $path->getDisamb()*2}->dumpValue("text"), $path->getDisamb(), NULL, TRUE);
      }
    }
    $sparql .= " } } ";
  
       
 #   dpm($sparql, "I would do: ");
 
   
#    drupal_set_message("I would do: " . ($sparql));
/*

    $clearPathArray = $this->getClearPathArray($path, $pb);

    $sparql = "INSERT DATA { GRAPH <" . $datagraphuri . "> { ";
    $olduri = NULL;
    $prop = NULL;
    foreach($clearPathArray as $key => $step) {
      if($key == 0 && !empty($entity_id)) {
        $eid = str_replace("\\", "/", $entity_id);
        $url = parse_url($eid);

        $olduri = $eid;
        continue;
      }
        
      $uri = $this->getUri($datagraphuri);
      if($key % 2 == 0) {
        $sparql .= "<$uri> a <$step> . ";
        if($key > 0) 
          $sparql .= "<$olduri> <$prop> <$uri> . ";    
        $olduri = $uri;
      } else {
        $prop = $step;
      }
    }
    
    $primitive = $path->getDatatypeProperty();
    if(!empty($primitive)) {
      $sparql .= "<$olduri> <$primitive> '$value' . ";
    }
        
    $sparql .= " } }";

    drupal_set_message("I do: " . htmlentities($sparql));
*/
    $result = $this->directUpdate($sparql);
    
    
#    drupal_set_message("I add field $field from entity $entity_id that currently has the value $value");
  }
  
  public function writeFieldValues($entity_id, array $field_values, $bundle=NULL) {
#    drupal_set_message(serialize("Hallo welt!") . serialize($entity_id) . " " . serialize($field_values) . ' ' . serialize($bundle));
    
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
    

    // this approach will be not fast enough in the future...
    // the pbs have to have a better mapping of where and how to find fields
    $pbs = \Drupal\wisski_pathbuilder\Entity\WisskiPathbuilderEntity::loadMultiple();
    
    $out = array();
    
#    return $out;
        
    // get the adapterid that was loaded
    // haha, this is the engine-id...
    //$adapterid = $this->getConfiguration()['id'];
        
    foreach($pbs as $pb) {
      
      // if we have no adapter for this pb it may go home.
      if(empty($pb->getAdapterId()))
        continue;
        
      $adapter = \Drupal\wisski_salz\Entity\Adapter::load($pb->getAdapterId());

      // if we have not adapter, we may go home, too
      if(empty($adapter))
        continue;
      
      // if he didn't ask for us...    
      if($this->getConfiguration()['id'] != $adapter->getEngine()->getConfiguration()['id'])
        continue;
              
#      foreach($entity_ids as $eid) {
        
        // here we should check if we really know the entity by asking the TS for it.
        // this would speed everything up largely, I think.
        $entity = $this->loadEntity($entity_id);

        #dpm($entity, "entity!");
        
        // if there is nothing, continue.
        if(empty($entity))
          continue;
        
        // it would be better to gather this information from the form and not from the ts
        // there might have been somebody saving in between...
        // @TODO !!!
        $old_values = $this->loadFieldValues(array($entity_id), array_keys($field_values), $bundle);

#        drupal_set_message("the old values for $entity_id were: " . serialize($old_values));

        if(!empty($old_values))
          $old_values = $old_values[$entity_id];

#        drupal_set_message("the old values were: " . serialize($old_values));

        foreach($field_values as $key => $fieldvalue) {
          #drupal_set_message("key: " . serialize($key) . " fieldvalue is: " . serialize($fieldvalue)); 

          $path = $pb->getPbEntriesForFid($key);          

          if(empty($path)) 
            continue;
            
          #drupal_set_message("I am still here: $key");

          $mainprop = $fieldvalue['main_property'];
          
          unset($fieldvalue['main_property']);
          
          foreach($fieldvalue as $key2 => $val) {

 #           drupal_set_message(serialize($val[$mainprop]) . " new");
 #           drupal_set_message(serialize($old_values[$key]) . " old");

            // check if there are any old values. If not, delete nothing.
            if(!empty($old_values)) {
         
#dpm(array('old_values' => $old_values, 'val' => $val));
              // if they are the same - skip
              // I don't know why this should be working, but I leave it here...
              if($val[$mainprop] == $old_values[$key]) 
                continue;
              
              // the real value comparison is this here:
              if($val[$mainprop] == $old_values[$key][$key2][$mainprop])
                continue;
              
              // if oldvalues are an array and the value is in there - skip
              if(is_array($old_values[$key]) && in_array($val[$mainprop], $old_values[$key][$key2]))
                continue;
              
            // now write to the database
            
#            drupal_set_message($entity_id . "I really write!" . serialize($val[$mainprop])  . " and " . serialize($old_values[$key]) );
#            return;
            
              // first delete the old values
              if(is_array($old_values[$key]))
                $this->deleteOldFieldValue($entity_id, $key, $old_values[$key][$key2][$mainprop], $pb);
              else
                $this->deleteOldFieldValue($entity_id, $key, $old_values[$key], $pb);
            }
                  
            // add the new ones
            $this->addNewFieldValue($entity_id, $key, $val[$mainprop], $pb); 
            
#            drupal_set_message("I would write " . $val[$mainprop] . " to the db and delete " . serialize($old_values[$key]) . " for it.");
            
          }

          
/*          
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
          if($fieldid == "bundle") {
            // get all the bundles for the eid from us
            $bundles = $this->getBundleIdsForEntityId($eid);
                        
            if(!empty($bundles)) {
              // for now we simply take the first one
              // that might be not so smart
              // who knows @TODO:
              foreach($bundles as $bundle) {
                $out[$eid]['bundle'] = $bundle;
                break;
              }
              continue;
            }
          }

          // every other field is an array, we guess
          // this might be wrong... cardinality?          
          if(!isset($out[$eid][$fieldid]))
            $out[$eid][$fieldid] = array();

          // set the bundle
          // @TODO: This is a hack and might break for multi-federalistic stores
          $pbarray = $pb->getPbEntriesForFid($fieldid);
            
          // if there is no data about this path - how did we get here in the first place?
          // fields not in sync with pb?
          if(empty($pbarray["id"]))
            continue;

          $path = \Drupal\wisski_pathbuilder\Entity\WisskiPathEntity::load($pbarray["id"]);

          // if there is no path we can skip that
          if(empty($path))
            continue;

          // the easy assumption - there already is a bundle.
          $bundle = $out[$eid]['bundle'];

          // if there is no bundle we have to ask the system for the typical bundle
          if(empty($bundle)) {
            
            // we try to get it from cache
            $bundle = $pb->getBundleIdForEntityId($eid);
            
            // nothing was set up to now - so we use the field and ask the field for the typical bundle
            if(empty($bundle)) {
              $bundle = $pb->getBundle($pbarray["id"]);
              // and store it to the entity.
              $out[$eid]['bundle'] = $bundle;

              $pb->setBundleIdForEntityId($eid, $bundle);

            }
          }

          // we ask for the bundle
          $bundle = $pb->getBundle($pbarray["id"]);
          
          // and compare it to the bundle of the entity - if this is not the same, 
          // we don't have to ask for data.
          // @TODO: this is a hack - when the engine asks for the correct 
          // things right away we can remove that here
          if($bundle != $out[$eid]['bundle']) {
            continue;
          }
          
          $clearPathArray = $this->getClearPathArray($path, $pb);
          
          if(!empty($path)) {
            // if this is question for a subgroup - handle it otherwise
            if($pbarray['parent'] > 0 && $path->isGroup()) {
#              drupal_set_message("I am asking for: " . serialize($this->getClearGroupArray($path, $pb)));
              $out[$eid][$fieldid] = array_merge($out[$eid][$fieldid], $this->pathToReturnValue($this->getClearGroupArray($path, $pb), NULL, $eid));
               
            } else // it is a field?
              $out[$eid][$fieldid] = array_merge($out[$eid][$fieldid], $this->pathToReturnValue($clearPathArray, $path->getDatatypeProperty(), $eid));
          }
        */
        }
      #}
    }

#    drupal_set_message("out: " . serialize($out));

    return $out;

  }
  
  // -------------------------------- Ontologie thingies ----------------------

  public function addOntologies($iri = NULL) { 
    if (empty($iri)) {
      //load all ontologies
      $query = "SELECT ?ont WHERE {?ont a owl:Ontology}";
      $result = $this->directQuery($query);
     # if ($ok) {
        foreach ($result as $obj) {
          $this->addOntologies(strval($obj->ont));
        }
     /* } else {
        foreach ($result as $err) {
          drupal_set_message(t('Error getting imports of ontology %iri: @e', array('%ont' => $o, '@e' => $err)), 'error');
        }
      }
      */
      return;
    }

    // check if the Ontology is already there
    $result = $this->directQuery("ASK {<$iri> a owl:Ontology}");
    
   /* if (!$ok) { // we've got something weired.
      drupal_set_message("Store is not requestable.", 'error');
      return;
   */
    
  /*
     // this case will not work, result will never be empty because it always contains the 
     if(!empty($result)){ // if it is not false it is already there   
      drupal_set_message("$iri is already loaded.", 'error');
      return;
    }
*/

    // if we get here we may load the ontology
    $query = "LOAD <$iri> INTO GRAPH <$iri>";
    $result = $this->directUpdate($query);

    // everything worked?  
/*    if (!$ok) {
      foreach ($result as $err) {
        drupal_set_message(t('An error occured while loading the Ontology: ' . serialize($err)),'error');
      }
    } else { // or it worked
 */     
      drupal_set_message("Successfully loaded $iri into the Triplestore.");
   # }
  
    // look for imported ontologies
    $query = "SELECT DISTINCT ?ont FROM <$iri> WHERE { ?s a owl:Ontology . ?s owl:imports ?ont . }";
  #  list($ok, $results) = $this->directQuery($query);
    $results = $this->directQuery($query);
 
    // if there was nothing something is weired again.
  /*  if (!$ok) {
      foreach ($results as $err) {
        drupal_set_message(t('Error getting imports of ontology %iri: @e', array('%ont' => $o, '@e' => $err)), 'error');
      }
    } else { // if there are some we have to load them
      foreach ($results as $to_load) {
        $this->addOntologies(strval($to_load->ont));
      }
    }*/
    foreach ($results as $to_load) {
      $this->addOntologies(strval($to_load->ont));
    }
                
    // load the ontology info in internal parameters    
    // $this->loadOntologyInfo();
    
    // add namespaces to table
  
    $file = file_get_contents($iri);
    $format = \EasyRdf_Format::guessFormat($file, $iri); 
    if(empty($format)) {
      drupal_set_message("Could not initialize namespaces.", 'error');
    } else {
      if(stripos($format->getName(), 'xml') !== FALSE) {
        preg_match('/RDF[^>]*>/i', $file, $nse);
        
        preg_match_all('/xmlns:[^=]*="[^"]*"/i', $nse[0], $nsarray);
        
        $ns = array();
        $toStore = array();
        foreach($nsarray[0] as $newns) {
          preg_match('/xmlns:[^=]*=/', $newns, $front);
          $front = substr($front[0], 6, strlen($front[0])-7);
          preg_match('/"[^"]*"/', $newns, $end);
          $end = substr($end[0], 1, strlen($end[0])-2);
          $ns[$front] = $end;
        }
                
	preg_match_all('/xmlns="[^"]*"/i', $nse[0], $toStore);
	
	foreach($toStore[0] as $itemGot) {
          $i=0;
	  $key = 'base';
	
	  preg_match('/"[^"]*"/', $itemGot, $item);
	  $item	= substr($item[0], 1, strlen($item[0])-2);
	  
	  if(!array_key_exists($key, $ns)) {
	    if(substr($item, strlen($item)-1, 1) != '#')
	      $ns[$key] = $item . '#';
	    else
	      $ns[$key] = $item;
          } else {
	      $newkey = $key . $i;
	      while(array_key_exists($newkey, $ns)) {
		$i++;
		$newkey = $key . $i;
	      }
	      if(substr($item, strlen($item)-1, 1) != '#')
	 	$ns[$newkey] = $item . '#';
	      else
		$ns[$newkey] = $item;
          }
	}
	
	foreach($ns as $key => $value) {
  	  $this->putNamespace($key, $value);
  	} 
  	
  	global $base_url;
  	// @TODO: check if it is already in the ontology.
  	$this->putNamespace("local", $base_url . '/');
  	$this->putNamespace("data", $base_url . '/inst/');
      }
      
      
    }    
    
    // return the result
    return $result;   

 }  

  public function getOntologies($graph = NULL) {
    // get ontology and version uri
    if(!empty($graph)) {
      $query = "SELECT DISTINCT ?ont ?iri ?ver FROM $graph WHERE { ?ont a owl:Ontology . OPTIONAL { ?ont owl:ontologyIRI ?iri. ?ont owl:versionIRI ?ver . } }";
    } else
      $query = "SELECT DISTINCT ?ont (COALESCE(?niri, 'none') as ?iri) (COALESCE(?nver, 'none') as ?ver) (COALESCE(?ngraph, 'default') as ?graph) WHERE { ?ont a owl:Ontology . OPTIONAL { GRAPH ?ngraph { ?ont a owl:Ontology } } . OPTIONAL { ?ont owl:ontologyIRI ?niri. ?ont owl:versionIRI ?nver . } }";
     
    $results = $this->directQuery($query); 
  /*
  if (!$ok) {
    foreach ($results as $err) {
      drupal_set_message(t('Error getting imports of ontology %iri: @e', array('%ont' => $o, '@e' => $err)), 'error');
    }
  }
 */                              
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
    $result = db_select('wisski_salz_sparql11_ontology_namespaces','ns')
              ->fields('ns')
              ->condition('short_name',$short_name,'=')
              ->execute()
              ->fetchAssoc();
    if (empty($result)) {
      db_insert('wisski_salz_sparql11_ontology_namespaces')
              ->fields(array('short_name' => $short_name,'long_name' => $long_name))
              ->execute();
    } else {
     //      drupal_set_message('Namespace '.$short_name.' already exists in DB');
    }
  }
                                                                                                           
  public function getNamespaces() {
    $ns = array();
    $db_spaces = db_select('wisski_salz_sparql11_ontology_namespaces','ns')
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
      $form['reasoner']['tester'] = array(
        '#type' => 'details',
        '#title' => $this->t('Check reasoning results'),
        'selected_prop' => array(
          '#type' => 'select',
          '#options' => $this->getClasses() + $this->getProperties(),
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
    $result = $this->directQuery("SELECT ?property WHERE {?property a owl:ObjectProperty.}");
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
      "SELECT ?property ?super WHERE {"
        ."?property a owl:ObjectProperty. "
        ."?property rdfs:subPropertyOf ?super. "
        ."FILTER NOT EXISTS {?mid_property rdfs:subPropertyOf+ ?super. ?property rdfs:subPropertyOf ?mid_property.}"
      ."}");
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
    $results = $this->directQuery("SELECT ?prop ?inverse WHERE {{?prop owl:inverseOf ?inverse.} UNION {?inverse owl:inverseOf ?prop.}}");
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
    $results = $this->directQuery("SELECT ?class WHERE {?class a owl:Class.}");
    foreach ($results as $row) {
      $class = $row->class->getUri();
      $classes[$class] = $rclass;
      $insert->values(array('class'=>$class));
    }
    $insert->execute();
    //uksort($classes,'strnatcasecmp');
    //\Drupal::cache()->set('wisski_reasoner_classes',$classes);
    
    //find full class hierarchy
    $super_classes = array();
    $sub_classes = array();
    $results = $this->directQuery("SELECT ?class ?super WHERE {"
      ."?class rdfs:subClassOf+ ?super. "
      ."FILTER (!isBlank(?class)) "
      ."FILTER (!isBlank(?super)) "
      ."?super a owl:Class. "
    ."}");
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
      "SELECT ?property ?domain WHERE {"
        ." ?property rdfs:domain ?domain."
        // we only need top level domains, so no proper subClass of the domain shall be taken into account
        ." FILTER NOT EXISTS { ?domain rdfs:subClassOf+ ?super_domain. ?property rdfs:domain ?super_domain.}"
      ." }");
    foreach ($results as $row) {
      $domains[$row->property->getUri()][$row->domain->getUri()] = $row->domain->getUri();
    }
    
    //clear up, avoid DatatypeProperties
    $domains = array_intersect_key($domains,$properties);
    
    //explicit top level ranges
    $ranges = array();
    
    $results = $this->directQuery(
      "SELECT ?property ?range WHERE {"
        ." ?property rdfs:range ?range."
        // we only need top level ranges, so no proper subClass of the range shall be taken into account
        ." FILTER NOT EXISTS { ?range rdfs:subClassOf+ ?super_range. ?property rdfs:range ?super_range.}"
      ." }");
    foreach ($results as $row) {
      $ranges[$row->property->getUri()][$row->range->getUri()] = $row->range->getUri();
    }
    
    //clear up, avoid DatatypeProperties
    $ranges = array_intersect_key($ranges,$properties);    
    
    //take all properties with no super property
    $top_properties = array_diff_key($properties,$super_properties);

    $valid_definitions = TRUE;
    //check if they all have domains and ranges set
    $dom_check = array_diff_key($top_properties,$domains);
    if (!empty($dom_check)) {
      drupal_set_message('No domains for top-level properties: '.implode(', ',$dom_check),'error');
      $valid_definitions = FALSE;
    }
    $rng_check = array_diff_key($top_properties,$ranges);
    if (!empty($rng_check)) {
      drupal_set_message('No ranges for top-level properties: '.implode(', ',$rng_check),'error');
      $valid_definitions = FALSE;
    }
    
    //set of properties where the domains and ranges are not fully set
    $not_set = array_diff_key($properties,$top_properties);
    
    //while there are unchecked properties cycle throgh them, gather domain/range defs from all super properties and inverses
    //and include them into own definition
    $runs = 0;
    while ($valid_definitions && !empty($not_set)) {
      
      $runs++;
      //take one of the properties
      $prop = array_shift($not_set);
      //check if all super_properties have their domains/ranges set
      $supers = $super_properties[$prop];
      $invalid_supers = array_intersect($supers,$not_set);
      if (empty($invalid_supers)) {
        //take all the definitions of super properties and add them here
        $new_domains = isset($domains[$prop]) ? $domains[$prop] : array();
        $new_ranges = isset($ranges[$prop]) ? $ranges[$prop] : array();
        foreach ($supers as $super_prop) {
          $new_domains += $domains[$super_prop];
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
        array_push($not_set,$prop);
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
    $results = $this->directQuery("SELECT ?inverse WHERE {{<$property_uri> owl:inverseOf ?inverse.} UNION {?inverse owl:inverseOf <$property_uri>.}}");
    $inverse = '';
    foreach ($results as $row) {
      $inverse = $row->inverse->getUri();
    }
    $inverses[$property_uri] = $inverse;
//    \Drupal::cache()->set($cid,$inverses);
    return $inverse;
  }
  
  protected function isPrepared() {
    try {
      return !empty(\Drupal::service('database')->select($this->adapterId().'_classes','c')->fields('c')->range(0,1)->execute());
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
          $database->truncate($table_name);
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
