<?php

namespace Drupal\wisski_adapter_geonames\Query;

use Drupal\wisski_salz\Query\WisskiQueryBase;
use Drupal\wisski_salz\Query\ConditionAggregate;
use Drupal\wisski_adapter_sparql11_pb\Plugin\wisski_salz\Engine\Sparql11EngineWithPB;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\wisski_salz\AdapterHelper;

class Query extends WisskiQueryBase {


  public function execute() {

    $result = array();
    
    if ($this->isFieldQuery()) {
      

    } elseif ($this->isPathQuery()) {

    }
    

    if ($this->count) {
      $result = count($result);
    }

    return $result;
#
#    // get the adapter
#    $engine = $this->getEngine();
#    
#    if (empty($engine))
#      return array();
#    
#    // get the adapter id
#    $adapterid = $engine->adapterId();
#
#    // if we have not adapter, we may go home, too
#    if (empty($adapterid))
#      continue;
#    
#    // get all pbs
#    $pbs = array();
#    $ents = array();
#    // collect all pbs that this engine is responsible for
#    foreach (WisskiPathbuilderEntity::loadMultiple() as $pb) {
#      if (!empty($pb->getAdapterId()) && $pb->getAdapterId() == $adapterid) {
#        $pbs[$pb->id()] = $pb;
#      }
#    }
#      
#    // init pager-things
#    if (!empty($this->pager) || !empty($this->range)) {
#      #dpm(array($this->pager, $this->range),'limits '.__CLASS__);
#      $limit = $this->range['length'];
#      $offset = $this->range['start'];
#    } //else dpm($this,'no limits');
#
#//wisski_tick('prepared '.$pb->id());
#    // care about everything...
#    if ($this->isFieldQuery()) {
#      
#      // bad hack, but this is how it was...
#      // TODO: handle correctly multiple pbs
#      $pb = current($pbs);
#      //wisski_tick("field query");
#      
#      $eidquery = NULL;
#      $bundlequery = NULL;
#      
#      foreach ($this->condition->conditions() as $condition) {
#        $field = $condition['field'];
#        $value = $condition['value'];
#        
#        if($field == "bundle")
#          $bundlequery = $value;
#        if($field == "eid")
#          $eidquery = $value;
#      }
#      
##        dpm($eidquery,"eidquery");
##        dpm($bundlequery, "bundlequery");
#              
#      $giveback = array();
#              
#      // eids are a special case
#      if ($eidquery !== NULL) {
#        
#        $eidquery = current($eidquery);
#        
#        $bundlequery = current($bundlequery);
#        
#        // load the id, this hopefully helps.
#        $thing = $engine->load($eidquery);
#      
##          dpm($eidquery, "thing");
#      
#        if($bundlequery === NULL)
#          $giveback = array($thing['eid']);
#          
#        else {
#      
#          // load the bundles for this id
#          $bundleids = $engine->getBundleIdsForEntityId($thing['eid']);        
#
#          if(in_array($bundlequery, $bundleids))
#            $giveback =  array($thing['eid']);
##            drupal_set_message(serialize($giveback) . "I give back for ask $eidquery");
#          //wisski_tick('Field query out 1');
#          return $giveback;
#        }
#      }
#      
#      //wisski_tick("field query half");
#      
#      foreach($this->condition->conditions() as $condition) {
#        $field = $condition['field'];
#        $value = $condition['value'];
##        drupal_set_message("you are evil!" . microtime() . serialize($this->count));
#
##        drupal_set_message("my cond is: " . serialize($condition));
#
#        // just return something if it is a bundle-condition
#        if($field == 'bundle') {
##  	        drupal_set_message("I go and look for : " . serialize($value) . " and " . serialize($limit) . " and " . serialize($offset) . " and " . $this->count);
#          if($this->count) {
##   	         drupal_set_message("I give back to you: " . serialize($pbadapter->getEngine()->loadIndividualsForBundle($value, $pb, NULL, NULL, TRUE)));
#            //wisski_tick('Field query out 2');
#            return $engine->loadIndividualsForBundle($value, $pb, NULL, NULL, TRUE, $this->condition->conditions());
#          }
#          
##            dpm($pbadapter->getEngine()->loadIndividualsForBundle($value, $pb, $limit, $offset, FALSE, $this->condition->conditions()), 'out!');
##            dpm(array_keys($pbadapter->getEngine()->loadIndividualsForBundle($value, $pb, $limit, $offset, FALSE, $this->condition->conditions())), "muhaha!");
##            return;           
#          //wisski_tick('Field query out 3');
#          return array_keys($engine->loadIndividualsForBundle($value, $pb, $limit, $offset, FALSE, $this->condition->conditions()));
#        }
#      }
#
#    //wisski_tick("afterprocessing");
#    
#    } elseif ($this->isPathQuery()) {
#      // if this is a path query act upon it accordingly
#      
#      //wisski_tick("path query");
#
#      // construct the query
#      $query = "";
#      // what bundle is it - for the bundle cache
#      $bundle_id = "";
#      
#      // we count 
#      $i = 0;
#      
#      // TODO: this does not handle nested conditions, ie.
#      // it does only handle OR/AND(cond1, cond2, ...) where
#      // condn must be a path
#      // this is sufficient for Drupal Search but might not suffice for
#      // more elaborate searches
#      foreach($this->condition->conditions() as $condition) {
#        $each_condition_group = $condition['field'];
#        $conjunction = strtoupper($each_condition_group->getConjunction());
#        
#        foreach($each_condition_group->conditions() as $cond) {
#
#          // condition groups may be and'ed or or'ed
#
#          $value = $cond['value'];
#          $op = $cond['operator'];
#
#          // save the bundle for the bundle cache    
#          if($cond['field'] == 'bundle') {
#            $bundle_id = $value;
#            continue;
#          }
#          
#          $pb_and_path = explode(".", $cond['field']);
#          $pbid = $pb_and_path[0];
#          if (!isset($pbs[$pbid])) {
#            // we cannot handle this path as its pb belongs to another engine
#            continue;
#          }
#          $pb = $pbs[$pbid];
#          // get the path
#          $path_id = $pb_and_path[1];
#          $path = \Drupal\wisski_pathbuilder\Entity\WisskiPathEntity::load($path_id);
#          // if it is no valid path - skip    
#          if(empty($path)) {
#            continue;
#          }
#          
#          // build up an array for separating the variables of the sparql 
#          // subqueries.
#          // only the first var x0 get to be the same so that everything maps
#          // to the same entity
#          $vars[0] = "";
#          for ($j = count($path->getPathArray()); $j > 0; $j--) {
#            $vars[$j] = "c${i}_";
#          }
#          $vars['out'] = "c${i}_";
#          
#          // 
#          $querypart = $engine->generateTriplesForPath($pb, $path, $value, NULL, NULL, 0, 0, FALSE, $op, 'field', TRUE, $vars);
#
#          if ($conjunction == 'OR' && $i != 0) {
#            $query .= ' } UNION {';
#          }
#          $query .= $querypart;
#
#          $i++;
#
#        }
#
#        if ($conjunction == 'OR' && !empty($query)) {
#          $query = "{{ $query }}";
#        }
#
#      }
#        
#      // if no query was constructed - there is nothing to search.    
#      // this may be the case when all paths belong to other engines.
#      if(empty($query))
#        return array();
#    
#      $query = "SELECT DISTINCT ?x0 WHERE { $query }";
#      $result = $engine->directQuery($query);
#    
#      foreach($result as $hit) {
#        if (!isset($hit->x0)) continue;
#        $entity_id = AdapterHelper::getDrupalIdForUri($hit->x0->getUri());
#        $ents[$entity_id] = $entity_id;
#      }
#      //wisski_tick('path query out');                  
#    }
#
#    return array_keys($ents);
#  }


  }
  
  /**
   * {@inheritdoc}
   */
  public function existsAggregate($field, $function, $langcode = NULL) {
    return $this->conditionAggregate->exists($field, $function, $langcode);
  }

  /**
   * {@inheritdoc}
   */
  public function notExistsAggregate($field, $function, $langcode = NULL) {
    return $this->conditionAggregate->notExists($field, $function, $langcode);
  }

  /**
   * {@inheritdoc}
   */
  public function conditionAggregateGroupFactory($conjunction = 'AND') {
    return new ConditionAggregate($conjunction, $this);
  }

}

