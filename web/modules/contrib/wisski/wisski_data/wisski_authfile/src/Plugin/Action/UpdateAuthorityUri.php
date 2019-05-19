<?php

/**
 * @file
 * Contains \Drupal\wisski_authority_file\Plugin\Action\UpdateAuthorityUri.
 */

namespace Drupal\wisski_authfile\Plugin\Action;

use Drupal\Core\Action\ConfigurableActionBase;
use Drupal\Core\Annotation\Action;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;

use Drupal\wisski_salz\AdapterHelper;


/**
* Generates the title for the given WissKI entities.
*
* @Action(
*   id = "wisski_authfile_update_uri",
*   label = @Translation("Update authority file URI"),
*   type = "wisski_individual"
* )
*/
class UpdateAuthorityUri extends ConfigurableActionBase {
  
  
  public function getAuthorityEntryBundle() {
    return $this->configuration['bundle'];
  }


  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'auth_adapters' => '',
      'fields' => '',
    );
  }



  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['fields'] = array(
      '#type' => 'textarea',
      '#title' => t('Field IDs pointing to authority entries'),
      '#default_value' => $this->configuration['fields'],
      '#description' => $this->t('One field ID per line followed by whitespace and the field ID for the URI in the authority entry bundle.'),
      '#required' => TRUE,
    );
    $form['auth_adapters'] = array(
      '#type' => 'textarea',
      '#title' => t('Authority entry adapters'),
      '#default_value' => $this->configuration['auth_adapters'],
      '#description' => $this->t('One adapter ID per line followed by whitespace and a URI pattern belonging to that adapter. URIs associated to these adapters will by added and removed according to the authority entries.'),
      '#required' => TRUE,
    );
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
  
  
  /**
   * {@inheritdoc}
   */
  public function execute($object = NULL) {
#    return;
#    dpm(serialize($object), "yay!");
    /** \Drupal\wisski_core\Entity\WisskiEntity $object */
    if (empty($object)) {
      return;
    }

    $adapters = $this->parseMap($this->configuration['auth_adapters']);
    $fields = explode("\n", $this->configuration['fields']);
    if (empty($fields)) {
      return;
    }
    
    // collect all new auth uris and, if any, compare them to the old ones
    $new_auth_uris = [];

    // go through all field definitions and see if there is such a field for the entity
    foreach ($fields as $field_path) {
      // fetch the entity ids of the entries
      $field_path = trim($field_path);
      if (empty($field_path)) {
        continue;
      }
      $uris = $this->getFieldValues($object, $field_path);
#      dpm($field_path, "fp");
#      return;
#      dpm($uris, "uris");
      // go thru each entry and extract the uri
      foreach ($uris as $uri) {
        // check if it matches any adapter patterns
        foreach ($adapters as $aid => $pattern) {
          if (preg_match("$pattern", $uri)) {
            // record the matches, use a combined string for easier matching
            // we can safely use the blank as separator as the aid may not 
            // contain whitespace
            $new_auth_uris["$aid $uri"] = "$aid $uri";
          }
        }
      }
    }
    
#    dpm($new_auth_uris, "yay?");
#    return;
    // now collect all old authority uris
    $uris_by_adapter = AdapterHelper::getOnlyOneUriPerAdapterForDrupalId($object->id(), NULL, FALSE); //doGetUrisForDrupalIdAsArray($object->id());//::getUrisForDrupalId($object->id());
    $old_uris = [];
    $old_auth_uris = [];
    foreach ($uris_by_adapter as $aid => $uri) {
      $old_uris["$aid $uri"] = "$aid $uri";
      if (isset($adapters[$aid])) {
        $old_auth_uris["$aid $uri"] = "$aid $uri";
      }
    }
    
    // delete the old uris that are no longer there
    $to_delete = array_diff($old_auth_uris, $new_auth_uris);
    $delete_uris = [];
    foreach ($to_delete as $aid_uri) {
      list($aid, $uri) = explode(" ", $aid_uri, 2);
      $delete_uris[$uri] = $uri;
    }    
    if (!empty($delete_uris)) {
      AdapterHelper::removeSameUris($delete_uris, $object->id());
    }

#    return;

    // add the new uri
    // we need the whole bunch of current uris as the setSameUris() will
    // not handle correctly only partial set of uris
    $to_add = $new_auth_uris + array_diff($old_uris, $old_auth_uris);
        
    //dpm($old_uris, "old uris");
    //dpm($old_auth_uris, "old_auth_uris");
    //dpm($to_add, "add");
    $new_uris = [];
    foreach ($to_add as $aid_uri) {
      list($aid, $uri) = explode(" ", $aid_uri, 2);
      $new_uris[$aid] = $uri;
    }
    if (!empty($new_uris)) {
      AdapterHelper::setSameUris($new_uris, $object->id());
    }

  }


  /** Helper function that parses the textual patterns into an array
   *
   * @return a possibly empty array or NULL on failure
   */
  protected function parseMap($lines) {
    $lines = explode("\n", trim($lines));
    if (empty($lines)) {
      return [];
    }
#    $fieldss = [];
    foreach ($lines as $line) {
      $line = trim($line);
      if (empty($line)) {
        continue;
      }
      elseif (preg_match('/^(?<entry>\S+)\s+(?<uri>\S+)/u', $line, $matches)) {
        $fields[$matches['entry']] = $matches['uri'];
      }
      else {
        return NULL;
      }
    }
    return $fields;
  }
  

  /**
   * Starting with the given entity, descend the tree of referenced entities 
   * according to the given path of fields, finally returning the array of all
   * found leaf values
   */
  protected function getFieldValues($entity, $field_path) {
    $parts1 = explode('.', $field_path, 2);
    $parts2 = explode(' ', $parts1[0], 2);
    $field_id = $parts2[0];
 #   dpm($field_id, "fid");
    $rest_path = (isset($parts2[1]) ? $parts2[1] : '') . (isset($parts1[1]) ? $parts1[1] : '');
    $field_def = $entity->getFieldDefinition($field_id);
#    return;
    if (!$field_def) {
      return [];
    }
    $values = [];

    $field_list = $entity->get($field_id);
    if (!$field_list->isEmpty()) {
      $main_property = $field_def->getFieldStorageDefinition()->getMainPropertyName();
      foreach ($field_list as $item) {
        $values[] = $item->get($main_property)->getValue();
      }
    }
      
#    dpm($values, "val");
#    dpm($rest_path, "rest");
#    return;
#    dpm($values, "val");
    if (empty($rest_path)) {    
      return $values; 
    } else {     
      $new_values = [];
      foreach (entity_load_multiple('wisski_individual', $values) as $new_entity) {
        $new_values = array_merge($new_values, $this->getFieldValues($new_entity, $rest_path));
      }
#      dpm($new_values, "yay!!");
      return $new_values;
    }
  }

}


