<?php

namespace Drupal\wwaf\Entity;

use Drupal\Core\Cache\Cache;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Field\BaseFieldDefinition;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;

use Drupal\user\UserInterface;
use Drupal\wwaf\WWAFEntityInterface;


/**
 * Defines the WWAF Entity.
 *
 * @ingroup wwaf_entity
 *
 * @ContentEntityType(
 *   id = "wwaf_entity",
 *   label = @Translation("WWAF Entity"),
 *   label_collection = @Translation("WWAF Points"),
 *   label_singular = @Translation("point"),
 *   label_plural = @Translation("points"),
 *   label_count = @PluralTranslation(
 *     singular = "@count point",
 *     plural = "@count points",
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\wwaf\WWAFEntityListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\wwaf\Form\WWAFEntityForm",
 *       "add" = "Drupal\wwaf\Form\WWAFEntityForm",
 *       "edit" = "Drupal\wwaf\Form\WWAFEntityForm",
 *       "delete" = "Drupal\wwaf\Form\WWAFEntityDeleteForm",
 *     },
 *     "access" = "Drupal\wwaf\WWAFEntityAccessControlHandler"
 *   },
 *   fieldable = TRUE,
 *   base_table = "wwaf_entity",
 *   data_table = "wwaf_entity_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer wwaf entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *     "uid" = "user_id",
 *   },
 *   links = {
 *     "canonical"          = "/wwaf/{wwaf_entity}",
 *     "add-form"           = "/wwaf/add",
 *     "edit-form"          = "/wwaf/{wwaf_entity}/edit",
 *     "delete-form"        = "/wwaf/{wwaf_entity}/delete",
 *     "collection"         = "/admin/wwaf/list"
 *   },
 *   field_ui_base_route = "wwaf.configuration.structure"
 * )
 */
class WWAFEntity extends ContentEntityBase implements WWAFEntityInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += array(
      'user_id' => \Drupal::currentUser()->id(),
    );
  }

  
  
  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isPublished() {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published) {
    $this->set('status', $published ? TRUE : FALSE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Created by'))
      ->setDescription(t('The user ID of author of the WWAF Store.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'hidden',
        'weight' => 0,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'hidden',
        'weight' => 5,
        'settings' => array(
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ),
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Point of Interest entity.'))
      ->setTranslatable(TRUE)
      ->setSettings(array(
        'max_length' => 100,
        'text_processing' => 0,
      ))
      ->setDefaultValue('')
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Point of Interest is published.'))
      ->setDefaultValue(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Description'))
      ->setDescription(t('Please enter a basic objective description about this Point of Interest.'))
      ->setTranslatable(TRUE)
      ->setDefaultValue('')
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'text_default',
        'weight' => -1,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'text_textarea_with_summary',
        'weight' => -1,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['gps'] = BaseFieldDefinition::create('geolocation')
      ->setLabel(t('GPS Coordinates'))
      ->setDescription(t('The GPS Coordinates of this Destination. You may enter address in the search field and the location will be retrieved via Google Maps.'))
      ->setTranslatable(FALSE)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'geolocation_map',
        'weight' => -2,
        'settings' => array(
          'set_marker' => '1',
          'info_text' => 'lat,long: :lat,:lng',
          'google_map_settings' => array(
            'type' => 'TERRAIN',
            'zoom' => 9,
            'mapTypeControl' => TRUE,
            'streetViewControl' => FALSE,
            'zoomControl' => TRUE,
            'scrollwheel' => FALSE,
            'disableDoubleClickZoom' => FALSE,
            'draggable' => TRUE,
            'height' => '300px',
            'width' => '100%',
            'info_auto_display' => TRUE,
            'disableAutoPan' => TRUE,
            'preferScrollingToZooming' => FALSE,
            'gestureHandling' => 'auto',
          ),
        ),
      ))
      ->setDisplayOptions('form', array(
        'type' => 'geolocation_googlegeocoder',
        'weight' => -2,
        'title' => t('This is a test title'),
        'settings' => array(
          'set_marker' => '1',
          'info_text' => t('Some info text'),
          'use_overridden_map_settings' => 0,
          'google_map_settings' => array(
            'type' => 'TERRAIN',
            'zoom' => 5,
            'mapTypeControl' => TRUE,
            'streetViewControl' => FALSE,
            'zoomControl' => TRUE,
            'scrollwheel' => FALSE,
            'disableDoubleClickZoom' => FALSE,
            'draggable' => TRUE,
            'height' => '450px',
            'width' => '100%',
            'info_auto_display' => TRUE,
            'disableAutoPan' => TRUE,
            'preferScrollingToZooming' => FALSE,
            'gestureHandling' => 'auto',
          ),
          
          // Disabled till the issue "https://www.drupal.org/project/geolocation/issues/2936722" will be fixed
          // 'populate_address_field' => TRUE,       // Enables the AJAX POPULATION of the address field
          // 'target_address_field' => 'address',    // machine_name of the field that must be populated
          'default_longitude' => 11.9000639,
          'default_latitude'  => 45.510844,
          'auto_client_location' => FALSE,
          'auto_client_location_marker' => FALSE,
          'allow_override_map_settings' => FALSE,
        ),
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['type'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Point type'))
      ->setDescription(t('The type of this point.'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setCardinality(1)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setSetting('handler', 'default')
      ->setSetting('handler_settings', [
        'target_bundles' => [
          'wwaf_point_types'
        ]
      ]);

    $fields['address'] = BaseFieldDefinition::create('address')
          ->setLabel(t('Address'))
          ->setDescription(t('The address of this Destination, if known. Choosing a location on the map above will attempt to fill this out using the Google Maps API. You might know better!'))
          ->setRevisionable(TRUE)
          ->setDisplayOptions('view', array(
            'label' => 'hidden',
            'type' => 'address_default',
            'weight' => -2,
          ))
          ->setDisplayOptions('form', array(
            'type' => 'address_default',
            'weight' => -2,
          ))
          ->setSettings(array(
            'fields' => array(
              'givenName'          => FALSE,
              'additionalName'     => FALSE,
              'familyName'         => FALSE,
              'organization'       => FALSE,
              'addressLine1'       => 'addressLine1',
              'addressLine2'       => 'addressLine2',
              'locality'           => 'locality',
              'dependentLocality'  => 'dependentLocality',
              'administrativeArea' => 'administrativeArea',
              'postalCode'         => 'postalCode',
              'sortingCode'        => 'sortingCode',
            ),
          ))
          ->setDisplayConfigurable('form', TRUE)
          ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

  protected function invalidateRestCache() {
    $address = $this->hasField('address') && !$this->get('address')->isEmpty() ?
      $this->get('address')->getValue()[0] : NULL;

    if ($address) {
      $country = $address['country_code'];
      $invalidate_tag = 'wwaf_list_' . $country;

      Cache::invalidateTags([$invalidate_tag]);
    }
  }
  
  protected function invalidateTagsOnSave($update) {
    $this->invalidateRestCache();
    parent::invalidateTagsOnSave($update); // TODO: Change the autogenerated stub
  }

  protected static function invalidateTagsOnDelete(EntityTypeInterface $entity_type, array $entities) {
    $entity = reset($entities);
    $entity->invalidateRestCache();
    parent::invalidateTagsOnDelete($entity_type, $entities); // TODO: Change the autogenerated stub
  }

  /**
   * @return array of serialized data
   */
  public function toDataArray() {

    $data = [];

    // Default fields:
    $data['id'] = $this->id();
    $data['label'] = $this->label();
    $data['description'] = $this->get('description')->value;
    $data['gps'] = $this->get('gps')->getValue()[0];
    $data['address'] = $this->get('address')->getValue()[0];

    // Configured fields:
    $fields = $this->getFields();
    foreach($fields as $key => $field) {
      if (strpos($key, 'field_') === FALSE)
        continue;
      
      
      $definition = $field->getFieldDefinition();
      $storage = $definition->getFieldStorageDefinition();
      $cardinality = $storage->getCardinality();


      if ($cardinality !== 1) {
        $values = $field->getValue();
        $data[$key] = !empty($values)? $values : [];
      }
      else {
        $values = $field->getValue();
        $data[$key] = !empty($values)? $values[0] : null;
      }
    }

    return $data;
  }
}