<?php

/**
 * @file
 * Contains \Drupal\content_entity_example\Entity\OpenLayers_LayerOptions.
 */

namespace Drupal\openlayers\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;
use Drupal\Core\Entity\EntityChangedTrait;

/**
 * Defines the ContentEntityExample entity.
 *
 * @ingroup openlayers
 *
 *
 * @ContentEntityType(
 *   id = "openlayers_layeroptions",
 *   label = @Translation("OpenLayers LayerOptions entity"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\openlayers\Entity\Controller\LayerOptionsListBuilder",
 *     "form" = {
 *       "add" = "Drupal\openlayers\Form\LayerOptionsForm",
 *       "edit" = "Drupal\openlayers\Form\LayerOptionsForm",
 *       "delete" = "Drupal\openlayers\Form\LayerOptionsDeleteForm",
 *     },
 *     "access" = "Drupal\openlayers\LayerOptionsAccessControlHandler",
 *   },
 *   list_cache_contexts = { "user" },
 *   base_table = "openlayers_layeroptions",
 *   admin_permission = "administer openlayers_layeroptions entity",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "layeroptions_name",
 *     "uuid" = "uuid",
 *     "user_id" = "user_id",
 *     "layer_active" = "layer_active",
 *     "layer_ref" = "layer_ref",
 *     "layer_opacity" = "layer_opacity" 
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/openlayers/layeroptions/{openlayers_layeroptions}",
 *     "edit-form" = "/admin/structure/openlayers/layeroptions/{openlayers_layeroptions}/edit",
 *     "delete-form" = "/admin/structure/openlayers/layeroptions/{openlayers_layeroptions}/delete",
 *     "collection" = "/admin/structure/openlayers/layeroptions/list"
 *   },
 * )
 */
class OpenLayers_LayerOptions extends ContentEntityBase
{
    use EntityChangedTrait;

    /**
    * {@inheritdoc}
    *
    * When a new entity instance is added, set the user_id entity reference to
    * the current user as the creator of the instance.
    */
    public static function preCreate(EntityStorageInterface $storage_controller, array &$values) 
    {
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
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type) 
    {
        // Standard field, used as unique if primary index.
        $fields['id'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('ID'))
            ->setDescription(t('The ID of the Term entity.'))
            ->setReadOnly(TRUE);

        // Standard field, unique outside of the scope of the current project.
        $fields['uuid'] = BaseFieldDefinition::create('uuid')
            ->setLabel(t('UUID'))
            ->setDescription(t('The UUID of the LayerOptions entity.'))
            ->setReadOnly(TRUE);
        $fields['layeroptions_name'] = BaseFieldDefinition::create('uuid')
            ->setLabel(t('Layeroptionsname'))
            ->setDescription(t('Is set programmatically'))
            ->setReadOnly(TRUE);
        $fields['layer_opacity'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Opacity'))
            ->setDescription(t('Opacity.'))
            ->setDefaultValue('0')
            ->setSettings(array(
                'max_length' => 3,
                'min' => 0,
                'max' => 100,
            ))
            ->setDisplayOptions('view', array(
                'label' => 'above',
                'type' => 'integer',
                'weight' => -6,
            ))
            ->setDisplayOptions('form', array(
                'type' => 'integer_textfield',
                'weight' => -6,
            ))
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);
        
        $fields['layer_active'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Active'))
            ->setDescription(t('Active.'))
            ->setSettings(array(
                'default_value' => '0',
            ))
            ->setDisplayOptions('view', array(
                'label' => 'above',
                'type' => 'integer',
                'weight' => -6,
            ))
            ->setDisplayOptions('form', array(
                'type' => 'integer_textfield',
                'weight' => -6,
            ))
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['layer_ref'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Layer'))
            ->setDescription(t('The used Layers.'))
            ->setSetting('target_type', 'openlayers_layer')
            ->setSetting('handler', 'default')
            ->setDisplayOptions('view', array(
                'label' => 'above',
                'type' => 'author',
                'weight' => -3,
            ))
            ->setDisplayOptions('form', array(
                'type' => 'entity_reference_autocomplete',
                'settings' => array(
                    'match_operator' => 'CONTAINS',
                    'size' => 60,
                    'placeholder' => '',
                ),
                'weight' => -3,
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

}