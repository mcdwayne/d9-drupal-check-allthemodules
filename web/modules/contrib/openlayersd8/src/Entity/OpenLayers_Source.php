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
use Drupal\user\UserInterface;
use Drupal\Core\Entity\EntityChangedTrait;

/**
 * Defines the ContentEntityExample entity.
 *
 * @ingroup openlayers
 *
 *
 * @ContentEntityType(
 *   id = "openlayers_source",
 *   label = @Translation("OpenLayers Source entity"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\openlayers\Entity\Controller\SourceListBuilder",
 *     "form" = {
 *       "add" = "Drupal\openlayers\Form\SourceForm",
 *       "edit" = "Drupal\openlayers\Form\SourceForm",
 *       "delete" = "Drupal\openlayers\Form\SourceDeleteForm",
 *     },
 *     "access" = "Drupal\openlayers\SourceAccessControlHandler",
 *   },
 *   list_cache_contexts = { "user" },
 *   base_table = "openlayers_source",
 *   admin_permission = "administer openlayers_source entity",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "user_id" = "user_id",
 *     "label" = "source_name",
 *     "source_type" = "source_type",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/openlayers/source/{openlayers_source}",
 *     "edit-form" = "/admin/structure/openlayers/source/{openlayers_source}/edit",
 *     "delete-form" = "/admin/structure/openlayers/source/{openlayers_source}/delete",
 *     "collection" = "/admin/structure/openlayers/source/list"
 *   },
 * )
 */
//kommt eigentlich zwischen Zeile 51 und 52 hin
//field_ui_base_route = "entity.openlayers.source.settings",
class OpenLayers_Source extends ContentEntityBase {

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
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type)
    {
        $types =array();
        
//        $types['bingmaps'] = 'BingMaps';
//        $types['cartodb'] = 'CartoDB';
//        $types['cluster'] = 'Cluster';
//        $types['image'] = 'Image';
//        $types['imagearcgisrest'] = 'ImageArcGISRest';
//        $types['imagecanvas'] = 'ImageCanvas';
//        $types['imagemapguide'] = 'ImageMapGuide';
//        $types['imagestatic'] = 'ImageStatic';
//        $types['imagevector'] = 'ImageVector';
        $types['imagewms'] = 'ImageWMS';
        $types['osm'] = 'OSM';
//        $types['raster'] = 'Raster';
//        $types['source'] = 'Source';
//        $types['stamen'] = 'Stamen';
//        $types['tile'] = 'Tile';
//        $types['tilearcgisrest'] = 'TileArcGISRest';
//        $types['tiledebug'] = 'TileDebug';
//        $types['tileimage'] = 'TileImage';
//        $types['tilejson'] = 'TileJSON';
//        $types['tileutfgrid'] = 'TileUTFGrid';
//        $types['tilewms'] = 'TileWMS';
//        $types['urltile'] = 'UrlTile';
        $types['vector'] = 'Vector';
//        $types['vectortile'] = 'VectorTile';
//        $types['wmts'] = 'WMTS';
        $types['xyz'] = 'XYZ';
//        $types['zoomify'] = 'Zoomify';
        
        $server_types = array();
        $server_types['mapserver'] = 'mapserver';
        $server_types['geoserver'] = 'geoserver';
        $server_types['drupalintern'] = 'Drupal intern';
        $server_types['drupalextern'] = 'Drupal extern';

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
    
    $fields['source_type'] = BaseFieldDefinition::create('list_string')
        ->setLabel(t('Type'))
        ->setDescription(t('Type of the Source.'))
        ->setDefaultValue('none')
        ->setSettings(array(
            'allowed_values' => $types,
            'required' => FALSE,
            'max_length' => 255,
            'text_processing' => 0,
            'options' => $types,
            
        ))
        ->setDisplayOptions('view', array(
            'label' => 'above',
            'type' => 'string',
            'weight' => -6,
            'options' => $types,
            
        ))
        ->setDisplayOptions('form', array(
            'type' => 'string_textfield',
            'weight' => -6,
        ))
        ->setDisplayConfigurable('form', TRUE)
        ->setDisplayConfigurable('view', TRUE);
    // Name field for the contact.
    // We set display options for the view as well as the form.
    // Users with correct privileges can change the view and edit configuration.
    $fields['source_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('Name of the Source.'))
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
    
   $fields['source_url'] = BaseFieldDefinition::create('string')
        ->setLabel(t('URL'))
        ->setDescription(t('URL of the Source.'))
        ->setDefaultValue('none')
        ->setSettings(array(
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
   
    $fields['server_type'] = BaseFieldDefinition::create('list_string')
        ->setLabel(t('Servertype'))
        ->setDescription(t('Type of the Server.'))
        ->setDefaultValue('none')
        ->setSettings(array(
            'allowed_values' => $server_types,
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