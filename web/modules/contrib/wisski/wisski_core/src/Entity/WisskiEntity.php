<?php

namespace Drupal\wisski_core\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\RevisionableContentEntityBase;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

use Drupal\Core\Language\LanguageInterface;

use Drupal\wisski_core\WisskiEntityInterface;

//keep for later use
// *		 "views_data" = "Drupal\wisski_core\WisskiEntityViewsData",


/**
 * Defines the entity class.
 *
 * @ContentEntityType(
 *   id = "wisski_individual",
 *   label = @Translation("Wisski Entity"),
 *   bundle_label = @Translation("Wisski Bundle"),
 *   handlers = {
 *     "storage" = "Drupal\wisski_core\WisskiStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\wisski_core\WisskiEntityViewsData",
 *     "list_builder" = "Drupal\wisski_core\Controller\WisskiEntityListBuilder",
 *     "list_controller" = "Drupal\wisski_core\Controller\WisskiEntityListController",
 *     "form" = {
 *       "default" = "Drupal\wisski_core\Form\WisskiEntityForm",
 *       "edit" = "Drupal\wisski_core\Form\WisskiEntityForm",
 *       "add" = "Drupal\wisski_core\Form\WisskiEntityForm",
 *       "delete" = "Drupal\wisski_core\Form\WisskiEntityDeleteForm",
 *     },
 *     "access" = "Drupal\wisski_core\Controller\WisskiEntityAccessHandler",
 *   },
 *   render_cache = FALSE,
 *   field_cache = FALSE,
 *   persistent_cache = FALSE,
 *   entity_keys = {
 *     "id" = "eid",
 *     "revision" = "vid",
 *     "bundle" = "bundle",
 *     "label" = "label",
 *     "preview_image" = "preview_image",
 *     "langcode" = "langcode",
 *     "uuid" = "uuid"
 *   },
 *   bundle_entity_type = "wisski_bundle",
 *   label_callback = "wisski_core_generate_title",
 *   permission_granularity = "bundle",
 *   admin_permission = "administer wisski",
 *   fieldable = TRUE,
 *   field_ui_base_route = "entity.wisski_bundle.edit_form",
 *   links = {
 *     "canonical" = "/wisski/navigate/{wisski_individual}",
 *     "delete-form" = "/wisski/navigate/{wisski_individual}/delete",
 *     "add-form" = "/wisski/create/{wisski_bundle}",
 *     "edit-form" = "/wisski/navigate/{wisski_individual}/edit",
 *     "admin-form" = "/admin/structure/wisski_core/{wisski_bundle}/edit",
 *   },
 *   translatable = FALSE,
 * )
 */
class WisskiEntity extends RevisionableContentEntityBase implements WisskiEntityInterface {

#  public static function create(array $values = array()) {
#    dpm("hallo welt!");
#    return parent::create($values);
#  }
  
  //@TODO we have a 'name' entity key and don't know what to do with it. SPARQL adapter uses a 'Tempo Hack'
  //making it the same as 'eid'
  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
  
    $fields = array();
    
    $fields['eid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Entity ID'))
      ->setDescription(t('The ID of this entity.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('This entity\'s UUID.'))
      ->setReadOnly(TRUE);

    $fields['vid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Revision ID'))
      ->setDescription(t('The revision ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['bundle'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Bundle'))
      ->setDescription(t('The bundle.'))
      ->setSetting('target_type', 'wisski_bundle')
      ->setReadOnly(TRUE);
    
    // TODO: wisski entities are not translatable. do we thus need the lang code?
    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('Language code.'))
      ->setRevisionable(TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Entity name'))
      ->setDescription(t('The human readable name of this entity.'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setDefaultValueCallback("wisski_core_generate_title")
//      ->setDefaultValue('')
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -5,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ))
      ->setDisplayConfigurable('view', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Creator ID'))
      ->setDescription(t('The user ID of the entity creator.'))
      ->setRevisionable(TRUE)
      ->setDefaultValue(0)
      ->setSetting('target_type', 'user')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ))
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => array(
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ),
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Published'))
#      ->setDescription(t('A boolean indicating whether the entity is published.'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE);
    
    $set = \Drupal::configFactory()->getEditable('wisski_core.settings');
    $use_status = $set->get('enable_published_status_everwhere');
    
    if($use_status)
      $fields['status']->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => TRUE,
          ],
        'weight' => 120,
        ])
      ->setDisplayConfigurable('form', TRUE);
    
    $fields['preview_image'] = BaseFieldDefinition::create('image')
      ->setLabel(t('Preview Image'))
      ->setDescription(t('A reference to an image file that is used as the preview image of the entity'))
      ->setSetting('target_type','file')
      ->setDefaultValue(NULL)
      ->setDisplayConfigurable('view', TRUE);
    
    return $fields;
  }
  

  protected $label = NULL;
  

  /**
   * {@inheritdoc}
   */
  public function label() {
    // we cache the label to prevent that it is fetched from db all the time
    if ($this->label === NULL) {
      $this->label = parent::label();
    }

    // this occurs on creation only!
    if($this->label === NULL) {
    #      dpm("yay!");
      $this->label = wisski_core_generate_title($this);
    }
    
    return $this->label;
  }


  public function tellMe() {

    $keys = func_get_args();
    $return = array();
    foreach ($keys as $key) {
      $field_name = $this->getEntityType()->getKey($key);
      $definition = $this->getFieldDefinition($field_name);
      $property = $definition->getFieldStorageDefinition()->getMainPropertyName();
      $value = $this->get($field_name)->$property;
      $return[$key] = array('key'=>$key,'field_name'=>$field_name,'property'=>$property,'value'=>$value);
    }
    return $return;
  }

#  public function id() {
#    
#    dpm($this->tellMe('id'));
#    dpm($this->tellMe('label'));
#    return 42;//parent::id();
#  }

  protected $original_values;
  
  public function saveOriginalValues($storage) {
#    drupal_set_message("save ori" . microtime());
#    drupal_set_message("ori was: " . serialize($this->original_values));
    // if there were already original values - do nothing.
    if(empty($this->original_values))
      $this->original_values = $this->extractFieldData($storage);
#    drupal_set_message("ori is now: " . serialize($this->original_values));
  }

  public function getOriginalValues() {
    
    return $this->original_values;
  }
  
  public function getValues($storage,$save_field_properties=FALSE) {
#    drupal_set_message("get values");
    return array($this->extractFieldData($storage,$save_field_properties),$this->original_values);
  }
  
  protected function extractFieldData($storage,$save_field_properties=FALSE) {
#    dpm("calling extractfieldData with sfp: " . serialize($save_field_properties));
#    dpm(func_get_args(), "extractFieldData in the beginning");
    $out = array();

    $fields_to_save = array();

    if ($save_field_properties) {
      //clear the field values for this field in entity in bundle
      db_delete('wisski_entity_field_properties')
        ->condition('eid',$this->id())
        ->condition('bid',$this->bundle())
    #    ->condition('fid',$field_name)
        ->execute();
      
      // prepare the insert query.
      $query = db_insert('wisski_entity_field_properties')
        ->fields(array('eid', 'bid', 'fid', 'delta', 'ident', 'properties'));
        
    }

    //$this is iterable itself, iterates over field list
    foreach ($this as $field_name => $field_item_list) {

      $out[$field_name] = array();
      
      // the main property is for all items of the field the same
      // so we buffer it here.
      $main_property = NULL;

#      dpm($field_item_list, "save!!");      
      foreach($field_item_list as $weight => $field_item) {
        
        $field_values = $field_item->getValue();
        $field_def = $field_item->getFieldDefinition()->getFieldStorageDefinition();

#        dpm($field_name, "fn");
#        dpm($field_values, "fv");

        if (!empty($field_values) && method_exists($field_def,'getDependencies') && in_array('file',$field_def->getDependencies()['module'])) {
          //when loading we assume $target_id to be the file uri
          //this is a workaround since Drupal File IDs do not carry any information when not in drupal context
          if (!isset($field_values['target_id'])) {
#dpm(func_get_args(), __METHOD__.__LINE__);
            continue;
          }
          $field_values['target_id'] = $storage->getPublicUrlFromFileId($field_values['target_id']);
        }

        // if it is empty it is probably not correctly initialized        
        if(empty($main_property))
          $main_property = $field_item->mainPropertyName();

        // if it is not initalized by now we do it by hand.
        if(empty($main_property) || (!empty($field_values) && empty($field_values[$main_property]))) {

          // this is not the best heuristic. better save something that is bigger...
          if(!empty($field_values))
            $main_property = current(array_keys($field_values));
          else
            $main_property = "value";
#          dpm($main_property, "reset main prop to");
#          dpm($field_values, "fv was");
#          dpm($field_item);
#          return;
        }

        //we transfer the main property name to the adapters
        $out[$field_name]['main_property'] = $main_property;
        //gathers the ARRAY of field properties for each field list item
        //e.g. $out[$field_name][] = array(value => 'Hans Wurst', 'format' => 'basic_html');
        $out[$field_name][$weight] = $field_values;
#        drupal_set_message("saved: " . serialize($field_values));

        if ($save_field_properties && !empty($this->id())) {

          if(!isset($field_values[$main_property])) {
            drupal_set_message("I could not store value " . serialize($field_values) . " for this field because the main property (" . $main_property . ") is not in there.", "warning");
#            dpm($field_values);
#            dpm($main_property, "mp");
            continue;
          }

          $fields_to_save = array(
            'eid' => $this->id(),
            'bid' => $this->bundle(),
            'fid' => $field_name,
            'delta' => $weight,
            'ident' => strlen($field_values[$main_property]) > 1000 ? substr($field_values[$main_property], 0, 1000) : $field_values[$main_property], 
            // this formerly was in here
            // the problem however is that this could never be written, because we don't know what is the disamb...
            #isset($field_values['wisskiDisamb']) ? $field_values['wisskiDisamb'] : $field_values[$main_property],
            'properties' => serialize($field_values),
          );
          
#          dpm($fields_to_save, "fts");
          
          // add the values to the insert query
          $query->values($fields_to_save);
        }
      }

#      dpm($fields_to_save, "fts");

      // do not do this per field, do it as a bunch.
      // execute the insert query
      #if ($save_field_properties && !empty($this->id())) {     
      #  dpm($query);
      #  $query->execute();
      #}
 
#      dpm($out[$field_name], "out");
        
      if (!isset($out[$field_name][0]) || empty($out[$field_name][0]) || empty($out[$field_name][0][$main_property])) unset($out[$field_name]);
#      if (!isset($out[$field_name][0]) || empty($out[$field_name][0]) ) unset($out[$field_name]);
    }
    
    if ($save_field_properties && !empty($this->id())) {     
      // @todo: somehow this seems to be triggered twice?!
#      dpm($query);
      $query->execute();
    }

    return $out;
  }

  public function getFieldDataTypes() {
    $types = array();

    // Gather a list of referenced entities.
    foreach ($this->getFields() as $field_name => $field_items) {
      foreach ($field_items as $field_item) {
        // Loop over all properties of a field item.
        foreach ($field_item->getProperties(TRUE) as $property_name => $property) {
          $types[$field_name][$property_name][] = get_class($property);
        }
      }
    }

    return $types;
  }
  
  /**
   * Is the entity new? We cannot answer that question with certainty, so we always say NO unless we definitely know it better
   */
  public function isNew() {
  
    return !empty($this->enforceIsNew);
  }
  
}
