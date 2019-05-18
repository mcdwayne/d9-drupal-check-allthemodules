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
 *   id = "openlayers_layer",
 *   label = @Translation("OpenLayers Layer entity"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\openlayers\Entity\Controller\LayerListBuilder",
 *     "form" = {
 *       "add" = "Drupal\openlayers\Form\LayerForm",
 *       "edit" = "Drupal\openlayers\Form\LayerForm",
 *       "delete" = "Drupal\openlayers\Form\LayerDeleteForm",
 *     },
 *     "access" = "Drupal\openlayers\LayerAccessControlHandler",
 *   },
 *   list_cache_contexts = { "user" },
 *   base_table = "openlayers_layer",
 *   admin_permission = "administer openlayers_layer entity",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "user_id" = "user_id",
 *     "label" = "layer_name",
 *     "type" = "layer_type",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/openlayers/layer/{openlayers_layer}",
 *     "edit-form" = "/admin/structure/openlayers/layer/{openlayers_layer}/edit",
 *     "delete-form" = "/admin/structure/openlayers/layer/{openlayers_layer}/delete",
 *     "collection" = "/admin/structure/openlayers/layer/list"
 *   },
 * )
 */
class OpenLayers_Layer extends ContentEntityBase {
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
    $types = [];
    $types['tile'] = "Tile";
    $types['image'] = "Image";
    $types['node'] = "Node";
    $types['view'] = "View";
    
    
    /*
     * $layers is only a placeholder, these array is build on a dynamically way, if nothing is defined we get an error, by saving the new one.
     */
    $layers = [];
    $layers['eins'] = ['Eins'];
    
    $fields['layer_source_ref'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Source of the layer'))
      ->setDescription(t('Reference to the source of the layer'))
      ->setSetting('target_type', 'openlayers_source')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -20,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'options_buttons',
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);
    
    $fields['layer_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Type'))
      ->setDescription(t('Type of the Layer.'))
      ->setSettings(array(
        'default_value' =>  $types['tile'],
        'allowed_values' => $types,
        'required' => FALSE,
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
      
    $fields['layer_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('Name of the Layer.'))
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
        
      /*
       * Only when Drupal - Node is choosen.
       */
      $fields['layer_node_ref'] = BaseFieldDefinition::create('entity_reference')
        ->setLabel(t('Node'))
        ->setDescription(t('Reference to the node, which will be shown'))
        ->setSetting('target_type', 'node')
        ->setSetting('handler_settings', ['target_bundles' => OpenLayers_Layer::getContentTypesWithGeom()])
        ->setSetting('handler', 'default')
        ->setDisplayOptions('view', array(
          'label' => 'above',
          'type' => 'string',
          'weight' => -6,
        ))
        ->setDisplayOptions('form', array(
          'type' => 'entity_reference_autocomplete',
          'settings' => array(
            'match_operator' => 'CONTAINS',
            'size' => 60,
            'placeholder' => 'title of the node'
          ),
        ))
        ->setDisplayConfigurable('form', TRUE)
        ->setDisplayConfigurable('view', TRUE);

      $fields['layer_view_ref'] = BaseFieldDefinition::create('entity_reference')
        ->setLabel(t('View'))
        ->setDescription(t('Reference to the view, which will be shown'))
        ->setSetting('target_type', 'view')
        ->setSetting('handler', 'default')
        ->setDisplayOptions('view', array(
          'label' => 'above',
          'type' => 'string',
          'weight' => -6,
        ))
        ->setDisplayOptions('form', array(
          'type' => 'options_buttons',
        ))
        ->setDisplayConfigurable('form', TRUE)
        ->setDisplayConfigurable('view', TRUE);

      $fields['layer_machine'] = BaseFieldDefinition::create('list_string')
        ->setLabel(t('machine Names'))
        ->setDescription(t('Names of the machine layer names or the feature types, split by "," and no empty spaces.'))
        ->setCardinality(-1)
        ->setSettings(array(
          'default_value' =>  $layers['eins'],
          'allowed_values' => $layers,
          'required' => FALSE,
          'max_length' => 255,
          'text_processing' => 0,
        ))
        ->setDisplayOptions('view', array(
          'label' => 'above',
          'type' => 'string',
          'weight' => -6,
        ))
        ->setDisplayOptions('form', array(
          'type' => 'options_buttons',
          'weight' => -6,
        ))
        ->setDisplayConfigurable('form', TRUE)
        ->setDisplayConfigurable('view', TRUE);

      // Owner field of the contact.
      // Entity reference field, holds the reference to the user object.
      // The view shows the user name field of the user.
      // The form presents a auto complete field for the user name.
      $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
        ->setLabel(t('User Name'))
        ->setDescription(t('The Name of the associated user.'))
        ->setSetting('target_type', 'user')
        ->setSetting('handler', 'default')
        ->setDisplayOptions('view', array(
          'type' => 'hidden',
          'weight' => -3,
        ))
        ->setDisplayOptions('form', array(
          'type' => 'hidden',
          'weight' => -3,
        ))
        ->setDisplayConfigurable('form', TRUE)
        ->setDisplayConfigurable('view', TRUE);

      return $fields;
  }
    
  public static function getContentTypesWithGeom() {
    $contenttypes_with_geofield = [];
    $entityManager = \Drupal::service('entity_field.manager');
    $node_types = NodeType::loadMultiple();
    foreach($node_types as $contenttype => $node) {
      $fields = $entityManager->getFieldDefinitions('node', $contenttype);
      foreach($fields as $field_name => $field) {
        if($field->getType() === 'geofield') {
          $contenttypes_with_geofield [$contenttype] = $contenttype;
          break;
        }
      }  
    }
    return $contenttypes_with_geofield;
  }
}
