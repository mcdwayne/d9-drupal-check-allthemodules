<?php

namespace Drupal\wisski_salz\Query;

use Drupal\Core\Entity\EntityTypeInterface;

class WisskiQueryDelegator extends WisskiQueryBase {

  /**
   * an array of Query Objects keyed by the name of their parent adapter. We need this to make sure, every
   * dependent query gets the same conditions etc.
   */
  private $dependent_queries = array();
  
  /**
   * we cache a list of entity IDs whose corresponding entites have an empty title in the cache table
   * those MUST be deleted from the view
   */
  protected static $empties;

  public function __construct(EntityTypeInterface $entity_type,$condition,array $namespaces) {
    parent::__construct($entity_type,$condition,$namespaces);
    $adapters = entity_load_multiple('wisski_salz_adapter');
    $preferred_queries = array();
    $other_queries = array();
    foreach ($adapters as $adapter) {
      $query = $adapter->getQueryObject($this->entityType,$this->condition,$this->namespaces);
      if ($adapter->getEngine()->isPreferredLocalStore()) $preferred_queries[$adapter->id()] = $query;
      else $other_queries[$adapter->id()] = $query;
    }
    $this->dependent_queries = array_merge($preferred_queries,$other_queries);
#    dpm($this->dependent_queries, "dep!");
  }
  
  /**
   * Add all parameters for a federated query to one of the query objects 
   * and return this.
   */
  protected function getFederatedQuery($is_count = FALSE) {
    // if everything is sparql we do a federated query
    // see https://www.w3.org/TR/sparql11-federated-query/
    $first_query = NULL;
      
    $max_query_parts = "";

    $total_order_string = "";

    $count = count($this->dependent_queries);

    foreach ($this->dependent_queries as $adapter_id => $query) {

#      dpm("dependent on $adapter_id");

      if($query instanceOf \Drupal\wisski_adapter_gnd\Query\Query ||
        $query instanceOf \Drupal\wisski_adapter_geonames\Query\Query) {
        // this is null anyway... so skip it
        
        // reduce count
        $count--;
        
        continue;
      }
            
      if($is_count)
        $query->countQuery();
      else
        $query->normalQuery();
        
      // get the query parts
      $parts = $query->getQueryParts();
      $where = $parts['where'];
      $eids = $parts['eids'];
      $order = $parts['order'];

      if(!empty($order))     
        $total_order_string .= $order . " ";

#      dpm($where, "where");
#      dpm($eids, "eids");
#      dpm($order, "got order!");
      $filtered_uris = NULL;

      $eids_part = "";
      
      // we got eids?
      if(!empty($eids))
        $filtered_uris = array_filter($eids);
      if (!empty($filtered_uris)) {
        $eids_part .= 'VALUES ?x0 { <' . join('> <', $filtered_uris) . '> } ';
      }      

      // build up a whole string from that      
      $string_part = $where . "" . $eids_part;

      // only take the maximum, because up to now we mainly do path mode, which is bad anyway
      // @todo: a clean implementation here would be better!
      if(strlen($string_part) > strlen($max_query_parts))
        $max_query_parts = $string_part;
          
        // preserve the first query object for later use
      if(empty($first_query)) {
        $first_query = $query;
        continue;
      }
    }
    
    if(!empty($max_query_parts)) {   
      $total_service_array = array();

      foreach ($this->dependent_queries as $adapter_id => $query) {
        if($query instanceOf \Drupal\wisski_adapter_gnd\Query\Query ||
           $query instanceOf \Drupal\wisski_adapter_geonames\Query\Query) {
          // this is null anyway... so skip it
          continue;
        }
          
        $conf = $query->getEngine()->getConfiguration();
          
        $read_url = $conf['read_url'];
          
        // construct the service-string
        if($count > 1) 
          $service_string = " { SERVICE <" . $read_url . "> { " . $max_query_parts . " } }";
        else
          $service_string = $max_query_parts;

        // add it to the first query                     
        $total_service_array[] = $service_string;
      }
#      dpm($total_service_array, "tos");
      $first_query->setOrderBy($total_order_string);
      $first_query->setDependentParts($total_service_array);
    }
    
    return $first_query;
  }
  
  public function execute() {
#    dpm(microtime(), "yay!");  
    if (!isset($this->empties)) {
      $bundle_id = NULL;
      foreach($this->condition->conditions() as $cond) {
        if ($cond['field'] === 'bundle') {
          $bundle_id = $cond['value'];
          break;
        }
      }
      //it is allowed to have an empty $bundle_id here
      self::$empties = \Drupal\wisski_core\WisskiCacheHelper::getEntitiesWithEmptyTitle($bundle_id);
      //dpm(self::$empties,'Empty titled Entities');
    }  
    
    if ($this->count) {
#      dpm("we have a count query!");
      $result = array();
      
      // only do this if more than one adapter!!!
      if(count($this->dependent_queries) > 1) {
        $is_sparql = TRUE;

        // check if all queries are sparql queries...
        foreach($this->dependent_queries as $adapter_id => $query) {
          if($query instanceOf \Drupal\wisski_adapter_sparql11_pb\Query\Query || 
             $query instanceOf \Drupal\wisski_adapter_gnd\Query\Query ||
             $query instanceOf \Drupal\wisski_adapter_geonames\Query\Query ) {
            // if it is a sparql11-query we are save!
          } else {
            $is_sparql = FALSE;        
          }
        }
        
#        dpm(serialize($is_sparql), "is it?");
        
        if(!$is_sparql) {
          // this is complicated!     
          foreach ($this->dependent_queries as $adapter_id => $query) {

//        $query = $query->count();
            $sub_result = $query->execute() ? : 0;

          // this is rather complicated. I don't know why php does this like that...
            if(!is_array($sub_result))
              $sub_result = array();
            $result = array_unique(array_merge($result, $sub_result), SORT_REGULAR); 
          }
        } else {
          $first_query = $this->getFederatedQuery(TRUE);
#          dpm(serialize($first_query), "first query");
          $result = $first_query->countQuery()->execute() ? : 0;
#          dpm(serialize($result), "res");
        }
//        $result = count($result);
      } else {
        $query = current($this->dependent_queries);
        $result = $query->countQuery()->execute() ? : 0;
      }
      
#     dpm('we counted '.$result);
#      dpm(serialize(self::$empties), "empties!");
      if (!empty(self::$empties)) $result -= count(self::$empties);
      return $result;
    } else {
#      dpm("we have no count query!");
      $pager = FALSE;
      if ($this->pager) {
        $pager = TRUE;
        //initializePager() generates a clone of $this with $count = TRUE
        //this is then passed to the dependent_queries which are NOT cloned
        //thus we must reset $count for the dependent_queries
        $this->initializePager();
      }
      $result = array();
  
      if(count($this->dependent_queries) > 1) {
#        dpm("we have a dependent query");
        $is_sparql = TRUE;
                
        // check if all queries are sparql queries...
        foreach($this->dependent_queries as $adapter_id => $query) {
          if($query instanceOf \Drupal\wisski_adapter_sparql11_pb\Query\Query || 
             $query instanceOf \Drupal\wisski_adapter_gnd\Query\Query ||
             $query instanceOf \Drupal\wisski_adapter_geonames\Query\Query ) {
            // if it is a sparql11-query we are save!
          } else {
            $is_sparql = FALSE;        
          }
        }
        
        if(!$is_sparql) {
          // this is complicated
          
          if ($pager || !empty($this->range)) {
            // use the old behaviour if we have a pager
            return $this->pagerQuery($this->range['length'],$this->range['start']);
          } else {
            // if we dont have a pager, iterate it and sum it up 
            // @todo: This here is definitely evil. We should give some warning!
            foreach ($this->dependent_queries as $query) {
              $query = $query->normalQuery();
              $sub_result = $query->execute();
              $result = array_unique(array_merge($result,$sub_result));
              
              if (!empty(self::$empties)) $result = array_diff($result,self::$empties);
              return $result;
            }
          }
        } else {
#          dpm("it is sparql");
          // if it is sparql, do a federated query!
          $first_query = $this->getFederatedQuery(FALSE);
          if ($pager || !empty($this->range)) {
            $first_query = $first_query->normalQuery();
            $first_query->range($this->range['start'],$this->range['length']);
          } else {
            $first_query = $first_query->normalQuery();
          }
          $ret = $first_query->execute();
#          dpm($ret, "ret");
          return $ret;
        }
      } else {
        // if we dont have a dependent query, do it the easy way!
        if ($pager || !empty($this->range)) {
          return $this->pagerQuery($this->range['length'],$this->range['start']);
        } else {
          $query = current($this->dependent_queries);
          $query = $query->normalQuery();
          return $query->execute();
        }
      }
      
      // this is probably unreachable...
      if (!empty(self::$empties)) $result = array_diff($result,self::$empties);
      return $result;
    }
  }
  
  protected function pagerQuery($limit,$offset) {
    //old versions below  
    $queries = $this->dependent_queries;
    $query = array_shift($queries);
    $act_offset = $offset;
    $act_limit = $limit;
    $all_results = array();
    $results = array();
    while (!empty($query)) {
      $query = $query->normalQuery();
      $query->range($act_offset,$act_limit);
      $new_results = $query->execute();
#      dpm("got: " . serialize($new_results));
      $res_count = count($new_results);
      if (!empty(self::$empties)) $new_results = array_diff($new_results,self::$empties);
      //$post_res_count = count($new_results);      
      //dpm($post_res_count,$act_offset.' '.$act_limit);
      $old_sum = count($results);
      $results = array_unique(array_merge($results,$new_results));
      $curr_sum = count($results);
      
      $res_count = $curr_sum - $old_sum;
      $post_res_count = $curr_sum - $old_sum;

#      dpm(serialize($res_count), "res");
      
      if ($res_count === 0) {
        //$query->count();
        unset($query->range);
        $res_count = $query->execute();
        #dpm($res_count, "res!");
        if(!is_array($res_count))
          $res_count = array();

        $before = count($all_results);
        $all_results = array_unique(array_merge($all_results,$res_count));
        $after = count($all_results);
        
//        if (!is_numeric($res_count)) $res_count = count($res_count);
        
        //dpm($res_count,$key.' full count');
        $act_offset = $act_offset - ($after - $before);
        if ($act_offset < 0) $act_offset = 0;
        $query = array_shift($queries);
      } elseif ($post_res_count < $res_count) {
        $act_limit = $act_limit - $post_res_count;
        if ($act_limit < 1) break;
        $act_offset = $act_offset + $res_count;
        //don't load a new query, this one may have more
      } elseif ($res_count < $act_limit) {
        $act_limit = $act_limit - $res_count;
        $act_offset = 0;
        $query = array_shift($queries);
      } else break;
    }
#    dpm($results, "res!");
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function condition($field, $value = NULL, $operator = NULL, $langcode = NULL) {
    parent::condition($field,$value,$operator,$langcode);
    foreach ($this->dependent_queries as $query) {
#      dpm("doing condition " . serialize($field) . " to value " . serialize($value));
      $query->condition($field,$value,$operator.$langcode);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function exists($field, $langcode = NULL) {
    parent::exists($field,$langcode);
    foreach ($this->dependent_queries as $query) $query->exists($field,$langcode);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function notExists($field, $langcode = NULL) {
    parent::notExists($field,$langcode);
    foreach ($this->dependent_queries as $query) $query->notExists($field,$langcode);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function pager($limit = 10, $element = NULL) {
    parent::pager($limit,$element);
    //foreach ($this->dependent_queries as $query) $query->pager($limit,$element);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function range($start = NULL, $length = NULL) {
    parent::range($start,$length);
    //foreach ($this->dependent_queries as $query) $query->range($start,$length);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function sort($field, $direction = 'ASC', $langcode = NULL) {
    parent::sort($field,$direction,$langcode);
    foreach ($this->dependent_queries as $query) $query->sort($field,$direction,$langcode);
    return $this;
  }
  
  public function setPathQuery() {
    foreach ($this->dependent_queries as $query) $query->setPathQuery();
  }
  
  public function setFieldQuery() {
    foreach ($this->dependent_queries as $query) $query->setFieldQuery(); 
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
