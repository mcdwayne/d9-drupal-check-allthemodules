<?php

/**
 * @file
 * Contains \Drupal\wisski_salz\Plugin\Action\MultiSparqlQuery.
 */

namespace Drupal\wisski_salz\Plugin\Action;

use Drupal\Core\Action\ConfigurableActionBase;
#use Drupal\Core\Annotation\Action;
#use Drupal\Core\Annotation\Translation;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;

use Drupal\wisski_salz\Plugin\wisski_salz\Engine\Sparql11Engine;

/**
* Redirects to a different URL.
*
* @Action(
*   id = "wisski_multisparql_query",
*   label = @Translation("Execute SparQL on multiple Adapters"),
*   type = "wisski_individual"
* )
*/
class MultiSparqlQuery extends ConfigurableActionBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    
    $config = array();
    
    // number of fields
    $num = 2;
    
    for($i = 0; $i < $num; $i++) {
      $config['query_part_' . $i] =  array(
        'adapter_id_' . $i => '',
        'sparql_' . $i => '',
        'query_method_' . $i => 'Update',
        'add_namespaces_' . $i => FALSE,
      );
    }
    
    // number of fields
    $config['num'] = $num;
    
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $adapters = entity_load_multiple('wisski_salz_adapter');
    $bundle_ids = array();
    // ask all adapters
    foreach($adapters as $aid => $adapter) {	
      if ($adapter->getEngine() instanceof Sparql11Engine) {
        $adapters[$aid] = $adapter->label();	
      }
      else {
        unset($adapters[$aid]);
      }
    }

    $num = $this->configuration['num'];

//    dpm($this->configuration, "conf!");

    $form['#tree'] = TRUE;

    for($i = 0; $i < $num; $i++) {
      $form['query_part_' . $i]['adapter_id_' . $i] = array(
        '#type' => 'select',
        '#title' => t('Adapter'),
        '#default_value' => $this->configuration['query_part_' . $i]['adapter_id_' . $i],
        '#options' => $adapters,
        '#required' => TRUE,
      );
      
      $form['query_part_' . $i]['query_method_' . $i] = array(
        '#type' => 'radios',
        '#title' => t('Query type'),
        '#options' => [
          'Query' => 'Query',
          'Update' => 'Update',
        ],
        '#default_value' => $this->configuration['query_part_' . $i]['query_method_' . $i],
        '#required' => TRUE,
      );
      $form['query_part_' . $i]['add_namespaces_' . $i] = array(
        '#type' => 'checkbox',
        '#title' => t("Use default namespaces"),
        '#default_value' => $this->configuration['query_part_' . $i]['add_namespaces_' . $i],
      );
      $form['query_part_' . $i]['sparql_' . $i] = array(
        '#type' => 'textarea',
        '#title' => t('SparQL query or update'),
        '#default_value' => $this->configuration['query_part_' . $i]['sparql_' . $i],
        '#required' => TRUE,
      );
    }
    
#    dpm($form, "form");
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration = $form_state->getValues();
  }
  
  
  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    return \Drupal\Core\Access\AccessResult::allowed();
  }
  
  public function yay() {
    drupal_set_message("yay!");
  }

  
  /**
   * {@inheritdoc}
   */
  public function execute() {
    
    $num = $this->configuration['num'];
        
    // first query everything
    for($i=0; $i<$num; $i++) {
#      dpm($this->configuration['query_part_' . $i]['query_method_' . $i], "conf");
      if($this->configuration['query_part_' . $i]['query_method_' . $i] == 'Query') {
        $adapter = entity_load('wisski_salz_adapter', $this->configuration['query_part_' . $i]['adapter_id_' . $i]);
        if (!$adapter) {
          \Drupal::logger('Wisski Salz')->error('Action %action: adapter with ID %aid does not exist', [
              '%action' => $this->pluginDefinition['label'],
              '%aid' => $this->configuration['query_part_' . $i]['adapter_id_' . $i],
          ]);
        }
        
#        dpm("yay1");
        
        
        $engine = $adapter->getEngine();
#        dpm("yay!");
        $queryMethod = 'direct' . $this->configuration['query_part_' . $i]['query_method_' . $i];
        $namespaces = '';
        if ($this->configuration['query_part_' . $i]['add_namespaces_' . $i]) {
          foreach ($engine->getNamespaces() as $prefix => $uri) {
            $namespaces .= "PREFIX $prefix: <$uri>\n";
          }
        }

        $all_results[] = array( 'source' => $this->configuration['query_part_' . $i]['adapter_id_' . $i], 'engine' => $engine,  'results' => $engine->$queryMethod($namespaces . $this->configuration['query_part_' . $i]['sparql_' . $i]));
#        dpm($namespaces . $this->configuration['query_part_' . $i]['sparql_' . $i], "yay!");
      }
    }

    $keys = array();
    $values = array();
    /*
    foreach($all_results as $key1 => $results) {
      #dpm(serialize($results), "res");
      
      $values[$key1] = array();
      
      foreach($results['results'] as $key2 => $result) {
        
        $values[$key1][$key2] = "(";
        // key3 is x or y or whatever was selected
        foreach($result as $key3 => $value) {
          $keys[$key1][$key3] = $key3;
          
          if($value->getUri()) {          
            $values[$key1][$key2] .= "<" . $value->getUri() . "> ";
          } else
            $values[$key1][$key2] .= '"' . $value->getValue() . '" ';
#          dpm(serialize($value), "res");  
        }
        
        $values[$key1][$key2] .= ") ";
#        dpm($value, "val");
      }
    }
    */
//    dpm($values, "values");
//    dpm($keys, "keys");

    // now update with what we have found if we found something
    for($i=0; $i<$num; $i++) {
      if($this->configuration['query_part_' . $i]['query_method_' . $i] == 'Update') {
        $adapter = entity_load('wisski_salz_adapter', $this->configuration['query_part_' . $i]['adapter_id_' . $i]);
        if (!$adapter) {
          \Drupal::logger('Wisski Salz')->error('Action %action: adapter with ID %aid does not exist', [
            '%action' => $this->pluginDefinition['label'],
            '%aid' => $this->configuration['query_part_' . $i]['adapter_id_' . $i],
          ]);
        }

        $engine = $adapter->getEngine();
        $queryMethod = 'direct' . $this->configuration['query_part_' . $i]['query_method_' . $i];
        $namespaces = '';
        
        if ($this->configuration['query_part_' . $i]['add_namespaces_' . $i]) {
          foreach ($engine->getNamespaces() as $prefix => $uri) {
            $namespaces .= "PREFIX $prefix: <$uri>\n";
          }
        }
     
        foreach($all_results as $key1 => $results) {
        #dpm(serialize($results), "res");
      
        $values[$key1] = array();
      
        foreach($results['results'] as $key2 => $result) {
        
          $values[$key1][$key2] = "(";
          // key3 is x or y or whatever was selected
          foreach($result as $key3 => $value) {
            $keys[$key1][$key3] = $key3;
          
            if($value->getUri()) {
              $newuri = $engine->getSameUri($value->getUri(), $this->configuration['query_part_' . $i]['adapter_id_' . $i]); //, $results['source']);
#              dpm(serialize($newuri), "newuri");
              if($newuri)
                $values[$key1][$key2] .= "<" . $newuri . "> ";
              else
                $values[$key1][$key2] .= "<" . $value->getUri() . "> ";
            } else
              $values[$key1][$key2] .= '"' . $value->getValue() . '" ';
#          dpm(serialize($value), "res");  
            }
        
            $values[$key1][$key2] .= ") ";
#        dpm($value, "val");
          }
        }
        
        
        $value_string_start = "VALUES (";
                
        // add it per source which should be key1.
        foreach($values as $key1 => $rows) {
          
          // first add the keys
          foreach($keys[$key1] as $key) {
            $value_string_start .= ("?" . $key . " ");
          }
          
          $value_string_start .= ") { ";


          $value_string = $value_string_start;
                
          $count_rows = 0;
                    
          foreach($rows as $row) {
            $count_rows++;
            $value_string .= $row;
            
            // if we have more than 100 we commit, so we don't overdo it for the
            // small triple stores...
            if($count_rows > 99) {
              $count_rows = 0;
              $value_string .= "} ";
              $result = $engine->$queryMethod($namespaces . $this->configuration['query_part_' . $i]['sparql_' . $i] . ' WHERE { ' . $value_string . ' } ');
              $value_string = $value_string_start;
            }
            
          }
          
          $value_string .= "} ";
   
          
          
     #     dpm(serialize($value_string), "valuestring");
          // only do this if we still have something to commit...
          if($count_rows > 0)
            $result = $engine->$queryMethod($namespaces . $this->configuration['query_part_' . $i]['sparql_' . $i] . ' WHERE { ' . $value_string . ' } ');
        // what to do with the result?
   
        }
   
      }
    }
  }

}

