<?php

namespace Drupal\spectra\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Spectra noun entity.
 *
 * @ingroup spectra
 *
 * @ContentEntityType(
 *   id = "spectra_noun",
 *   label = @Translation("Spectra Noun"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\spectra\Entity\Controller\SpectraNounListBuilder",
 *     "views_data" = "Drupal\spectra\Entity\Views\SpectraNounViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\spectra\Form\SpectraNounForm",
 *       "add" = "Drupal\spectra\Form\SpectraNounForm",
 *       "edit" = "Drupal\spectra\Form\SpectraNounForm",
 *       "delete" = "Drupal\spectra\Form\SpectraNounDeleteForm",
 *     },
 *     "access" = "Drupal\spectra\SpectraNounAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\spectra\SpectraNounHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "spectra_noun",
 *   admin_permission = "administer spectra noun entities",
 *   entity_keys = {
 *     "id" = "noun_id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/spectra/spectra_noun/{spectra_noun}",
 *     "add-form" = "/admin/structure/spectra/spectra_noun/add",
 *     "edit-form" = "/admin/structure/spectra/spectra_noun/{spectra_noun}/edit",
 *     "delete-form" = "/admin/structure/spectra/spectra_noun/{spectra_noun}/delete",
 *     "collection" = "/admin/structure/spectra/spectra_noun",
 *   },
 *   field_ui_base_route = "spectra_noun.settings"
 * )
 */
class SpectraNoun extends ContentEntityBase implements SpectraNounInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function getSpectraEntityType($type = 'machine_name') {
    switch ($type) {
      case 'class_name':
        return 'SpectraNoun';
        break;
      case 'short':
        return 'noun';
        break;
      case 'machine_name':
      default:
        return 'spectra_noun';
    }
  }

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
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Standard field, used as unique if primary index.
    $fields['noun_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the SpectraNoun entity.'))
      ->setReadOnly(TRUE);

    // Standard field, unique outside of the scope of the current project.
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the SpectraNoun entity.'))
      ->setReadOnly(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('Name'))
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -4,
      ))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);
    $fields['type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Type'))
      ->setDescription(t('Used for determining the correct plugin to call, and for indexing.'))
      ->setSettings(array(
        'default_value' => 'default',
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -4,
      ))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['source'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Actor Source'))
      ->setDescription(t('The source of the actor information. May be internal, external, or a string of the source website/server'))
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 1023,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['source_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Source ID'))
      ->setDescription(t('The ID of the actor. This may be an internal or external user ID, or similar identification string'))
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);


    $fields['description'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Description'))
      ->setDescription(t('Short description of Item'))
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 1023,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['data'] = BaseFieldDefinition::create('jsonb')
      ->setLabel(t('Additional Data'))
      ->setDescription(t('Additional data to associate with this actor.'));

    return $fields;
  }

}
