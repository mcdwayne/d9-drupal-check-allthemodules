<?php

namespace Drupal\stacks\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\stacks\WidgetExtendEntityInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Widget Extend entity.
 *
 * @ingroup stacks
 *
 * @ContentEntityType(
 *   id = "widget_extend",
 *   label = @Translation("Widget Extend"),
 *   bundle_label = @Translation("Widget Extend type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\stacks\WidgetExtendEntityListBuilder",
 *     "views_data" = "Drupal\stacks\Entity\WidgetExtendEntityViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\stacks\Form\WidgetExtendEntityForm",
 *       "add" = "Drupal\stacks\Form\WidgetExtendEntityForm",
 *       "edit" = "Drupal\stacks\Form\WidgetExtendEntityForm",
 *       "delete" = "Drupal\stacks\Form\WidgetExtendEntityDeleteForm",
 *     },
 *     "access" = "Drupal\stacks\WidgetExtendEntityAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\stacks\WidgetExtendEntityHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "widget_extend",
 *   admin_permission = "administer Widget Extend entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title",
 *     "bundle" = "type",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/stacks/widget_extend/{widget_extend}",
 *     "add-form" = "/admin/structure/stacks/widget_extend/add/{widget_extend_type}",
 *     "edit-form" = "/admin/structure/stacks/widget_extend/{widget_extend}/edit",
 *     "delete-form" = "/admin/structure/stacks/widget_extend/{widget_extend}/delete",
 *     "collection" = "/admin/structure/stacks/widget_extend",
 *   },
 *   bundle_entity_type = "widget_extend_type",
 *   field_ui_base_route = "entity.widget_extend_type.edit_form"
 * )
 */
class WidgetExtendEntity extends ContentEntityBase implements WidgetExtendEntityInterface {
  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->get('title')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTitle($title) {
    $this->set('title', $title);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->getTitle();
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->bundle();
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

  public function isPublished() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {}
  public function getOwnerId() {}
  public function setOwnerId($uid) {}
  public function setOwner(UserInterface $account) {}

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Widget Widget Extend instances entity entity.'))
      ->setReadOnly(TRUE);

    $fields['type'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Type'))
      ->setDescription(t('The Widget Widget Extend instances entity type/bundle.'))
      ->setSetting('target_type', 'widget_extend_type')
      ->setRequired(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Widget Widget Extend instances entity entity.'))
      ->setReadOnly(TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setRevisionable(TRUE)
      ->setSettings([
        'max_length' => 255,
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
        'weight' => -51,
      ])
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}
