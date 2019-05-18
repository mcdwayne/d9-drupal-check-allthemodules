<?php

/**
 * @file
 * Contains \Drupal\content_entity_example\Entity\ContentEntityExample.
 */

namespace Drupal\openlayers\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\node\Entity\NodeType;
use Drupal\views\Entity\View;



/**
 * Defines the ContentEntityExample entity.
 *
 * @ingroup openlayers
 *
 *
 * @ContentEntityType(
 *   id = "openlayers_control",
 *   label = @Translation("OpenLayers Control entity"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\openlayers\Entity\Controller\ControlListBuilder",
 *     "form" = {
 *       "add" = "Drupal\openlayers\Form\ControlForm",
 *       "edit" = "Drupal\openlayers\Form\ControlForm",
 *       "delete" = "Drupal\openlayers\Form\ControlDeleteForm",
 *     },
 *     "access" = "Drupal\openlayers\ControlAccessControlHandler",
 *   },
 *   list_cache_contexts = { "user" },
 *   base_table = "openlayers_control",
 *   admin_permission = "administer openlayers_control entity",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "control_name",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/openlayers/control/{openlayers_control}",
 *     "edit-form" = "/admin/structure/openlayers/control/{openlayers_control}/edit",
 *     "delete-form" = "/admin/structure/openlayers/control/{openlayers_control}/delete",
 *     "collection" = "/admin/structure/openlayers/control/list"
 *   },
 * )
 */
class OpenLayers_Control extends ContentEntityBase {
  use EntityChangedTrait;
 
  /**
  * {@inheritdoc}
  *
  * When a new entity instance is added, set the user_id entity reference to
  * the current user as the creator of the instance.
  */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    // Default author to current user.
    $values += array(
      'user_id' => \Drupal::currentUser()->id(),
    );
  }

  /**
  * {@inheritdoc}
  *
  * Define the field properties here.
  *
  * Field name, type and size determine the table structure.
  *
  * In addition, we can define how the field and its content can be manipulated
  * in the GUI. The behaviour of the widgets used can be determined here.
  */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $types = array();
    $types['basic'] = "Basic Control";
    $types['custom'] = "Custom Control";
    
    $fields['control_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Type'))
      ->setDescription(t('Type of the Control.'))
      ->setSettings(array(
        'allowed_values' => $types,
        'required' => TRUE,
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -6,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -6,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Standard field, used as unique if primary index.
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Term entity.'))
      ->setReadOnly(TRUE);

    // Standard field, unique outside of the scope of the current project.
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Contact entity.'))
      ->setReadOnly(TRUE);
      
    $fields['control_namespace'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Namespace'))
      ->setDescription(t('Namespace of the Control.'))
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -6,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -6,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);
    
     $fields['control_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('Name of the Control.'))
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -6,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -6,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);
    
    
    
    $fields['control_factory'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Module name'))
      ->setDescription(t('Name of module, which provides the control.'))
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -6,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -6,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);
      
    $fields['tooltip'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Tooltip'))
      ->setDescription(t('Tooltip shown on mouse hover.'))
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -6,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -6,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);
        
    $fields['description'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Description'))
      ->setDescription(t('description'))
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -6,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -6,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);
        

    $fields['machine_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('machine Names'))
      ->setDescription(t('machine Name'))
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -6,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -6,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);
    
    $fields['icon'] = BaseFieldDefinition::create('string')
    ->setLabel(t('Icon'))
    ->setDescription(t('Icon, leave empty to use the default one'))
    ->setSettings(array(
      'default_value' => '',
      'max_length' => 255,
      'text_processing' => 0,
    ))
    ->setDisplayOptions('view', array(
      'label' => 'above',
      'type' => 'string',
    ))
    ->setDisplayOptions('form', array(
      'type' => 'string_textfield',
    ))
    ->setDisplayConfigurable('form', TRUE)
    ->setDisplayConfigurable('view', TRUE);
    return $fields;
  }
}
