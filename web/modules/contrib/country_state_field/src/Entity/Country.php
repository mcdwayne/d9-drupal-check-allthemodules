<?php

namespace Drupal\country_state_field\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Country entity.
 *
 * @ingroup country_state_field
 *
 * @ContentEntityType(
 *   id = "country",
 *   label = @Translation("Country"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\country_state_field\CountryListBuilder",
 *     "views_data" = "Drupal\country_state_field\Entity\CountryViewsData",
 *     "translation" = "Drupal\country_state_field\CountryTranslationHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\country_state_field\Form\CountryForm",
 *       "add" = "Drupal\country_state_field\Form\CountryForm",
 *       "edit" = "Drupal\country_state_field\Form\CountryForm",
 *       "delete" = "Drupal\country_state_field\Form\CountryDeleteForm",
 *     },
 *     "access" = "Drupal\country_state_field\CountryAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\country_state_field\CountryHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "country",
 *   data_table = "country_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer country entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/country/{country}",
 *     "add-form" = "/admin/structure/country/add",
 *     "edit-form" = "/admin/structure/country/{country}/edit",
 *     "delete-form" = "/admin/structure/country/{country}/delete",
 *     "collection" = "/admin/structure/country",
 *   },
 *   field_ui_base_route = "country.settings"
 * )
 */
class Country extends ContentEntityBase implements CountryInterface {

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

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Country entity.'))
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
