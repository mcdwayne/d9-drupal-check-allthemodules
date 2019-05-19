<?php

namespace Drupal\wisski_adapter_skos\Query;

use Drupal\wisski_salz\Query\WisskiQueryBase;
use Drupal\wisski_salz\Query\ConditionAggregate;
use Drupal\wisski_adapter_skos\Plugin\wisski_salz\Engine\Sparql11EngineWithPB;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\wisski_salz\AdapterHelper;

class Query extends WisskiQueryBase {

  protected $then = 0;
    
  protected function tick($name='') {
    $now = microtime(TRUE)*1000;
    dpm(ceil($now-$this->then).' ms',$name);
    $this->then = $now;
  }

  #private $parent_engine;

  #public function __construct(EntityTypeInterface $entity_type,$condition,array $namespaces,Sparql11EngineWithPB $parent_engine) {
    #parent::__construct($entity_type,$condition,$namespaces);
    #$this->parent_engine = $parent_engine;
    #drupal_set_message("yeah!");
  #}

  /**
   * {@inheritdoc}
   */
  public function execute() {

#    wisski_tick('init query');
    
    // get the adapter
    $engine = $this->getEngine();
    
    if(empty($engine))
      return array();
    
    // get the adapter id
    $adapterid = $engine->adapterId();
    //dpm($this,__FUNCTION__.' '.$adapterid);
    
    // if we have not adapter, we may go home, too
    if(empty($adapterid))
      return;
    
    // get all pbs
    $pbs = \Drupal\wisski_pathbuilder\Entity\WisskiPathbuilderEntity::loadMultiple();
    
    $ents = array();
    // iterate through all pbs
    foreach($pbs as $pb) {

      // if we have no adapter for this pb it may go home.
      if(empty($pb->getAdapterId()))
        continue;
      
      // load the adapter of the pb
      $pbadapter = \Drupal\wisski_salz\Entity\Adapter::load($pb->getAdapterId());
      
      // check if the queries adapter is the adapter of the pb we currently use.
      if($pbadapter->id() != $adapterid)
        continue;
      
      // init pager-things
      if(isset($this->pager) && !empty($this->range)) {
        $limit = $this->range['length'];
        $offset = $this->range['start'];
        #dpm(array('limit'=>$limit,'offset'=>$offset),'Pager');
      }

      // care about everything...
      if($this->isFieldQuery()) {
        
    #    wisski_tick("field query");
        
        $eidquery = NULL;
        $bundlequery = NULL;
        
        foreach($this->condition->conditions() as $condition) {
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
        if($eidquery !== NULL) {
          
          $eidquery = current($eidquery);
          
          $bundlequery = current($bundlequery);
          
          // load the id, this hopefully helps.
          $thing = $pbadapter->getEngine()->load($eidquery);
        
#          dpm($eidquery, "thing");
        
          if($bundlequery === NULL)
            $giveback = array($thing['eid']);
            
          else {
        
            // load the bundles for this id
            $bundleids = $pbadapter->getEngine()->getBundleIdsForEntityId($thing['eid']);        

            if(in_array($bundlequery, $bundleids))
              $giveback =  array($thing['eid']);
#            drupal_set_message(serialize($giveback) . "I give back for ask $eidquery");
            return $giveback;
          }
        }
        
     #   wisski_tick("field query half");
        
        foreach($this->condition->conditions() as $condition) {
          $field = $condition['field'];
          $value = $condition['value'];
#        drupal_set_message("you are evil!" . microtime() . serialize($this->count));

#        drupal_set_message("my cond is: " . serialize($condition));

          // just return something if it is a bundle-condition
          if($field == 'bundle') {
#  	        drupal_set_message("I go and look for : " . serialize($value) . " and " . serialize($limit) . " and " . serialize($offset) . " and " . $this->count);
            if($this->count) {
#   	         drupal_set_message("I give back to you: " . serialize($pbadapter->getEngine()->loadIndividualsForBundle($value, $pb, NULL, NULL, TRUE)));
              return $pbadapter->getEngine()->loadIndividualsForBundle($value, $pb, NULL, NULL, TRUE, $this->condition->conditions());
            }
            
#            dpm($pbadapter->getEngine()->loadIndividualsForBundle($value, $pb, $limit, $offset, FALSE, $this->condition->conditions()), 'out!');
#            dpm(array_keys($pbadapter->getEngine()->loadIndividualsForBundle($value, $pb, $limit, $offset, FALSE, $this->condition->conditions())), "muhaha!");
#            return;           
            return array_keys($pbadapter->getEngine()->loadIndividualsForBundle($value, $pb, $limit, $offset, FALSE, $this->condition->conditions()));
          }
        }
      }

      #wisski_tick("afterprocessing");
      
      // if this is a path query act upon it accordingly
      if($this->isPathQuery()) {
        
      #  wisski_tick("path query");
        
        // construct the query
        $query = "";
        // what bundle is it - for the bundle cache
        $bundle_id = "";
                
        foreach($this->condition->conditions() as $condition) {
          $each_condition_group = $condition['field'];
          
          foreach($each_condition_group->conditions() as $cond) {

            // save the bundle for the bundle cache    
            if($cond['field'] == 'bundle') {
              $bundle_id = $cond['value'];
              continue;
            }
            
            $pb_and_path = explode(".", $cond['field']);
                        
            $pbid = $pb_and_path[0];
            
            // if this is not the correct pathbuilder
            if($pbid != $pb->id())
              continue;
            
            // get the path
            $path_id = $pb_and_path[1];
            
            $value = $cond['value'];
            
            $op = $cond['operator'];
                        
            $path = \Drupal\wisski_pathbuilder\Entity\WisskiPathEntity::load($path_id);
            
            // if it is no valid path - skip    
            if(empty($path))
              continue;

            $query .= $pbadapter->getEngine()->generateTriplesForPath($pb, $path, $value, NULL, NULL, 0, 0, FALSE, $op);
          }
        }
          
        // if no query was constructed - there is nothing to search.     
        if(empty($query))
          return array();
      
        $query = "SELECT * WHERE { " . $query . " }";
#        dpm($query, 'query');        
        $result = $pbadapter->getEngine()->directQuery($query);
        
        $out = array();
      
        foreach($result as $hit) {
#          $entity_id = str_replace('/', '\\', $hit->x0->getUri());
          $entity_id = AdapterHelper::getDrupalIdForUri($hit->x0->getUri());
          $out[] = $entity_id;
          \Drupal::entityManager()->getStorage('wisski_individual')->writeToCache($entity_id,$bundle_id);
        }
                  
        return $out;        
      }
    
        
      // do something with conditions ... but for now we just load everything.   
#      $ents = array_merge($ents, $this->parent_engine->loadMultiple());
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
