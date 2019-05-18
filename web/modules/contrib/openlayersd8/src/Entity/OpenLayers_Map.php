<?php

/**
 * @file
 * Contains \Drupal\content_entity_example\Entity\OpenLayers_Map.
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
 *   id = "openlayers_map",
 *   label = @Translation("OpenLayers Map entity"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\openlayers\Entity\Controller\MapListBuilder",
 *     "form" = {
 *       "add" = "Drupal\openlayers\Form\MapForm",
 *       "edit" = "Drupal\openlayers\Form\MapForm",
 *       "delete" = "Drupal\openlayers\Form\MapDeleteForm",
 *     },
 *     "access" = "Drupal\openlayers\MapAccessControlHandler",
 *   },
 *   list_cache_contexts = { "user" },
 *   base_table = "openlayers_map",
 *   admin_permission = "administer openlayers_map entity",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "user_id" = "user_id",
 *     "label" = "map_name",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/openlayers/map/{openlayers_map}",
 *     "edit-form" = "/admin/structure/openlayers/map/{openlayers_map}/edit",
 *     "delete-form" = "/admin/structure/openlayers/map/{openlayers_map}/delete",
 *     "collection" = "/admin/structure/openlayers/map/list"
 *   },
 * )
 */
class OpenLayers_Map extends ContentEntityBase 
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

        // Name field for the contact.
        // We set display options for the view as well as the form.
        // Users with correct privileges can change the view and edit configuration.
        $fields['map_name'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Name'))
            ->setDescription(t('Name of the Map.'))
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
        
        $fields['map_height'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Map Height'))
            ->setDescription(t('Map Height.'))
            ->setSettings(array(
                'default_value' => '450',
                'max_length' => 3,
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
        
        $fields['zoom'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Zoom'))
            ->setDescription(t('initial Zoomlevel.'))
            ->setSettings(array(
                'default_value' => '0',
                'max_length' => 2,
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
        
        $fields['maxzoom'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Max Zoom'))
            ->setDescription(t('Max Zoom.'))
            ->setSettings(array(
                'default_value' => '0',
                'max_length' => 2,
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
        
        $fields['minzoom'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Min Zoom'))
            ->setDescription(t('Min Zoom.'))
            ->setSettings(array(
                'default_value' => '0',
                'max_length' => 2,
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

					$fields['center'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Center'))
            ->setDescription(t('initial center of the Map.'))
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
					
					$fields['max_extent'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Extent'))
            ->setDescription(t('max Extent, leave empty to have no max extent'))
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


        // 
        $fields['layer_ref_overlay'] = BaseFieldDefinition::create('entity_reference')
          ->setLabel(t('OverlayLayers'))
          ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
          ->setDescription(t('The used Layers.'))
          ->setSetting('target_type', 'openlayers_layeroptions')
          ->setSetting('handler', 'default')
          ->setRequired(false)
          ->setDisplayOptions('view', array(
            'label' => 'above',
            'type' => 'author',
            'weight' => -3,
          ))
          ->setDisplayOptions('form', array(
            'type' => 'inline_entity_form_complex',
            'settings' => array(
                'match_operator' => 'CONTAINS',
                'size' => 60,
                'placeholder' => '',
                'allow_new' => true,
            ),
            'weight' => -3,
          ))
          ->setDisplayConfigurable('form', TRUE)
          ->setDisplayConfigurable('view', TRUE);
        
        $fields['layer_ref_base'] = BaseFieldDefinition::create('entity_reference')
          ->setLabel(t('BaseLayers'))
          ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
          ->setDescription(t('The used Layers.'))
          ->setSetting('target_type', 'openlayers_layer')
          ->setSetting('handler', 'default')
          ->setRequired(false)
          ->setDisplayOptions('view', array(
            'label' => 'above',
            'type' => 'author',
            'weight' => -3,
          ))
          ->setDisplayOptions('form', array(
            'type' => 'inline_entity_form_complex',
            'settings' => array(
              'match_operator' => 'CONTAINS',
              'size' => 60,
              'placeholder' => '',
              'allow_new' => false,
              'allow_existing' => true,  // could be used if someone uses layer configurations in more than one map
            ),
            'weight' => -3,
          ))
          ->setDisplayConfigurable('form', TRUE)
          ->setDisplayConfigurable('view', TRUE);
        
         $fields['control_ref'] = BaseFieldDefinition::create('entity_reference')
          ->setLabel(t('Controls'))
          ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
          ->setDescription(t('define the used controls.'))
          ->setSetting('target_type', 'openlayers_controloptions')
          ->setSetting('handler', 'default')
          ->setRequired(false)
          ->setDisplayOptions('view', array(
            'label' => 'above',
            'type' => 'author',
            'weight' => -3,
          ))
          ->setDisplayOptions('form', array(
            'type' => 'inline_entity_form_complex',
            'settings' => array(
                'match_operator' => 'CONTAINS',
                'size' => 60,
                'placeholder' => '',
                'allow_new' => true,
				),
				'weight' => -3,
			))
			->setDisplayConfigurable('form', TRUE)
			->setDisplayConfigurable('view', TRUE);
		return $fields;
	}
}