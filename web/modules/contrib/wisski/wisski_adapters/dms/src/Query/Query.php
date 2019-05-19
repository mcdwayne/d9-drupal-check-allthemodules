<?php

namespace Drupal\wisski_adapter_dms\Query;

use Drupal\wisski_salz\Query\WisskiQueryBase;
use Drupal\wisski_salz\Query\ConditionAggregate;
use Drupal\wisski_adapter_dms\Plugin\wisski_salz\Engine\DmsEngine;
use Drupal\wisski_pathbuilder\Entity\WisskiPathbuilderEntity;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\wisski_salz\AdapterHelper;
use Drupal\wisski_salz\Query\Condition;

class Query extends WisskiQueryBase {


  public function execute() {
#    dpm("exe!");

    $result = array();

   // get the adapter
    $engine = $this->getEngine();
#    dpm($engine, "engine");
    if (empty($engine))
      return array();
    
    // get the adapter id
    $adapterid = $engine->adapterId();
#    dpm($adapterid, "adapterid");

    // if we have not adapter, we may go home, too
    if (empty($adapterid))
      return array();
#    return;
    // get all pbs
    $pbs = array();
    $ents = array();
    // collect all pbs that this engine is responsible for
    foreach (WisskiPathbuilderEntity::loadMultiple() as $pb) {
      if (!empty($pb->getAdapterId()) && $pb->getAdapterId() == $adapterid) {
        $pbs[$pb->id()] = $pb;
      }
    }
      
    // init pager-things
    if (!empty($this->pager) || !empty($this->range)) {
      #dpm(array($this->pager, $this->range),'limits '.__CLASS__);
      $limit = $this->range['length'];
      $offset = $this->range['start'];
    } //else dpm($this,'no limits');

//wisski_tick('prepared '.$pb->id());
#    return;
    // care about everything...
    if (TRUE ) { //$this->isFieldQuery()) {
#      dpm("fq!");
      // bad hack, but this is how it was...
      // TODO: handle correctly multiple pbs
      $pb = current($pbs);
      //wisski_tick("field query");
      
      $eidquery = NULL;
      $bundlequery = NULL;
            
      foreach ($this->condition->conditions() as $condition) {
        $field = $condition['field'];
        $value = $condition['value'];
        
        if($field == "bundle")
          $bundlequery = $value;
        if($field == "eid")
          $eidquery = $value;
      }
      
#        dpm($eidquery,"eidquery");
#        dpm($bundlequery, "bundlequery");
              
      $giveback = array();
              
      // eids are a special case
      if ($eidquery !== NULL) {
        
        if(is_array($eidquery))
          $eidquery = current($eidquery);
        
        if(is_array($bundlequery)) 
          $bundlequery = current($bundlequery);
        
        // load the id, this hopefully helps.
        $thing['eid'] = $eidquery;
      
#          dpm($eidquery, "thing");
      
        if($bundlequery === NULL)
          $giveback = array($thing['eid']);
          
        else {
      
          // load the bundles for this id
          $bundleids = $engine->getBundleIdsForEntityId($thing['eid']);        

          if(in_array($bundlequery, $bundleids))
            $giveback =  array($thing['eid']);
#            drupal_set_message(serialize($giveback) . "I give back for ask $eidquery");
          //wisski_tick('Field query out 1');
          return $giveback;
        }
      }
#      dpm("half");    
      //wisski_tick("field query half");
      
      foreach($this->condition->conditions() as $condition) {
        $field = $condition['field'];
        $value = $condition['value'];
#        drupal_set_message("you are evil!" . microtime() . serialize($this));
#        return;

#        drupal_set_message("my cond is: " . serialize($condition));
        
        // just return something if it is a bundle-condition
        if($field == 'bundle') {
#  	        drupal_set_message("I go and look for : " . serialize($value) . " and " . serialize($limit) . " and " . serialize($offset) . " and " . $this->count);
          if($this->count) {
#   	         drupal_set_message("I give back to you: " . serialize($pbadapter->getEngine()->loadIndividualsForBundle($value, $pb, NULL, NULL, TRUE)));
            //wisski_tick('Field query out 2');
            return $engine->loadIndividualsForBundle($value, $pb, NULL, NULL, TRUE, $this->condition->conditions());
          }
          
#            dpm($pbadapter->getEngine()->loadIndividualsForBundle($value, $pb, $limit, $offset, FALSE, $this->condition->conditions()), 'out!');
#            dpm(array_keys($pbadapter->getEngine()->loadIndividualsForBundle($value, $pb, $limit, $offset, FALSE, $this->condition->conditions())), "muhaha!");
#            return;           
          //wisski_tick('Field query out 3');
          return array_keys($engine->loadIndividualsForBundle($value, $pb, $limit, $offset, FALSE, $this->condition->conditions()));
        }
        
#        dpm($field, "fi");
        if($field instanceof Condition) {
#          dpm($field, "field");
#          dpm($field->conditions(), "cond");
          
          foreach($field->conditions() as $subcondition) {
#            dpm($subcondition, "sub");
#            dpm($subcondition['field'], "val");
            
            $pb_and_path = explode(".", $subcondition['field']);
            
            $pathid = $pb_and_path[1];
            
            $pbp = $pb->getPbPath($pathid);
            
            $value = $subcondition['value'];
            
            $bundle = $pbp['bundle'];
            if($this->count) {
              $ret = $engine->loadIndividualsForBundle($bundle, $pb, NULL, NULL, TRUE, $field->conditions());
              return $ret;
            } else {
              $ret = $engine->loadIndividualsForBundle($bundle, $pb, NULL, NULL, FALSE, $field->conditions());
#              dpm($ret, "got");
              return array_keys($ret);
            }
            
          }
        }
      }

    //wisski_tick("afterprocessing");
    
    } elseif ($this->isPathQuery()) {
    }

    return array_keys($ents);
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

