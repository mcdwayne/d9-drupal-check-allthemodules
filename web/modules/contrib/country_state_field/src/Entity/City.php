<?php

namespace Drupal\country_state_field\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the City entity.
 *
 * @ingroup country_state_field
 *
 * @ContentEntityType(
 *   id = "city",
 *   label = @Translation("City"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\country_state_field\CityListBuilder",
 *     "views_data" = "Drupal\country_state_field\Entity\CityViewsData",
 *     "translation" = "Drupal\country_state_field\CityTranslationHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\country_state_field\Form\CityForm",
 *       "add" = "Drupal\country_state_field\Form\CityForm",
 *       "edit" = "Drupal\country_state_field\Form\CityForm",
 *       "delete" = "Drupal\country_state_field\Form\CityDeleteForm",
 *     },
 *     "access" = "Drupal\country_state_field\CityAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\country_state_field\CityHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "city",
 *   data_table = "city_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer city entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/city/{city}",
 *     "add-form" = "/admin/structure/city/add",
 *     "edit-form" = "/admin/structure/city/{city}/edit",
 *     "delete-form" = "/admin/structure/city/{city}/delete",
 *     "collection" = "/admin/structure/city",
 *   },
 *   field_ui_base_route = "city.settings"
 * )
 */
class City extends ContentEntityBase implements CityInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
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
  public function getState() {
    return $this->get('state_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getCountry() {
    $state = $this->get('state_id')->entity;
    return $state->getCountry();
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

    $fields['state_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('State'))
      ->setDescription(t('The user ID of state of the State entity.'))
      ->setSetting('target_type', 'state')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the City entity.'))
      ->setTranslatable(TRUE)
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}
