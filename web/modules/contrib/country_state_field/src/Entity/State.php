<?php

namespace Drupal\country_state_field\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the State entity.
 *
 * @ingroup country_state_field
 *
 * @ContentEntityType(
 *   id = "state",
 *   label = @Translation("State"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\country_state_field\StateListBuilder",
 *     "views_data" = "Drupal\country_state_field\Entity\StateViewsData",
 *     "translation" = "Drupal\country_state_field\StateTranslationHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\country_state_field\Form\StateForm",
 *       "add" = "Drupal\country_state_field\Form\StateForm",
 *       "edit" = "Drupal\country_state_field\Form\StateForm",
 *       "delete" = "Drupal\country_state_field\Form\StateDeleteForm",
 *     },
 *     "access" = "Drupal\country_state_field\StateAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\country_state_field\StateHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "state",
 *   data_table = "state_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer state entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/state/{state}",
 *     "add-form" = "/admin/structure/state/add",
 *     "edit-form" = "/admin/structure/state/{state}/edit",
 *     "delete-form" = "/admin/structure/state/{state}/delete",
 *     "collection" = "/admin/structure/state",
 *   },
 *   field_ui_base_route = "state.settings"
 * )
 */
class State extends ContentEntityBase implements StateInterface {

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
  public function getCountry() {
    return $this->get('country_id')->entity;
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

    $fields['country_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Country'))
      ->setDescription(t('The user ID of country of the State entity.'))
      ->setSetting('target_type', 'country')
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
      ->setDescription(t('The name of the State entity.'))
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
