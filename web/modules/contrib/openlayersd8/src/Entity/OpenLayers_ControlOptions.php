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
 *   id = "openlayers_controloptions",
 *   label = @Translation("OpenLayers ControlOptions entity"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\openlayers\Entity\Controller\ControlOptionsListBuilder",
 *     "form" = {
 *       "add" = "Drupal\openlayers\Form\ControlOptionsForm",
 *       "edit" = "Drupal\openlayers\Form\ControlOptionsForm",
 *       "delete" = "Drupal\openlayers\Form\ControlOptionsDeleteForm",
 *     },
 *     "access" = "Drupal\openlayers\ControlOptionsAccessControlHandler",
 *   },
 *   list_cache_contexts = { "user" },
 *   base_table = "openlayers_controloptions",
 *   admin_permission = "administer openlayers_controloptions entity",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/openlayers/controloptions/{openlayers_controloptions}",
 *     "edit-form" = "/admin/structure/openlayers/controloptions/{openlayers_controloptions}/edit",
 *     "delete-form" = "/admin/structure/openlayers/controloptions/{openlayers_controloptions}/delete",
 *     "collection" = "/admin/structure/openlayers/controloptions/list"
 *   },
 * )
 */
class OpenLayers_ControlOptions extends ContentEntityBase {
  use EntityChangedTrait;
 
  /**
  * {@inheritdoc}
  *
  * When a new entity instance is added, set the user_id entity reference to
  * the current user as the creator of the instance.
  */
  public static function preCreate(EntityStorageInterface $storage_controloptionsler, array &$values) {
    parent::preCreate($storage_controloptionsler, $values);
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
    
    $fields['control_ref'] = BaseFieldDefinition::create('entity_reference')
          ->setLabel(t('Control'))
          ->setDescription(t('The used control.'))
          ->setSetting('target_type', 'openlayers_control')
          ->setSetting('handler', 'default')
          ->setRequired(TRUE)
          ->setDisplayOptions('view', array(
            'label' => 'above',
            'type' => 'author',
          ))
          ->setDisplayOptions('form', array(
            'type' => 'options_select',
            'settings' => array(
            ),
          ))
          ->setDisplayConfigurable('form', TRUE)
          ->setDisplayConfigurable('view', TRUE);  
    
    $fields['tooltip'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Tooltip'))
      ->setDescription(t('shown tooltip, leave empty to use the default one'))
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
