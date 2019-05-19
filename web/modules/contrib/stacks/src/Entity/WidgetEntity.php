<?php

namespace Drupal\stacks\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\stacks\WidgetEntityInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Widget Entity entity.
 *
 * @ingroup stacks
 *
 * @ContentEntityType(
 *   id = "widget_entity",
 *   label = @Translation("Widget Entity"),
 *   bundle_label = @Translation("Widget Entity type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\stacks\WidgetEntityListBuilder",
 *     "views_data" = "Drupal\stacks\Entity\WidgetEntityViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\stacks\Form\WidgetEntityForm",
 *       "add" = "Drupal\stacks\Form\WidgetEntityForm",
 *       "edit" = "Drupal\stacks\Form\WidgetEntityForm",
 *       "delete" = "Drupal\stacks\Form\WidgetEntityDeleteForm",
 *     },
 *     "access" = "Drupal\stacks\WidgetEntityAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\stacks\WidgetEntityHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "widget_entity",
 *   admin_permission = "administer stacks entity entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "type",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/stacks/widget_entity/{widget_entity}",
 *     "add-form" = "/admin/structure/stacks/widget_entity/add/{widget_entity_type}",
 *     "edit-form" = "/admin/structure/stacks/widget_entity/{widget_entity}/edit",
 *     "delete-form" = "/admin/structure/stacks/widget_entity/{widget_entity}/delete",
 *     "collection" = "/admin/structure/stacks/widget_entity",
 *   },
 *   bundle_entity_type = "widget_entity_type",
 *   field_ui_base_route = "entity.widget_entity_type.edit_form"
 * )
 */
class WidgetEntity extends ContentEntityBase implements WidgetEntityInterface {
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
  public function label() {
    return t("Entity") . " " . $this->get('id')->value;
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

  /**
   * Increments the widget_times_used value for this entity.
   */
  public function triggerTimesUsed() {
    $time_used = (int) $this->get('widget_times_used')->value;
    $time_used++;
    $this->set('widget_times_used', $time_used);
    return $this;
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
  public function isPublished() {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published) {
    $this->set('status', $published ? NODE_PUBLISHED : NODE_NOT_PUBLISHED);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the stacks entity.'))
      ->setReadOnly(TRUE);

    $fields['type'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Type'))
      ->setDescription(t('The Widget Entity type/bundle.'))
      ->setSetting('target_type', 'widget_entity_type')
      ->setRequired(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Widget Entity entity.'))
      ->setReadOnly(TRUE);

    $fields['widget_times_used'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Times Used'))
      ->setDescription(t('How many times has this widget been used?'))
      ->setDefaultValue(1);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Widget Entity is published.'))
      ->setDefaultValue(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

 /**
   * Takes a widget bundle. If the bundle is in a group, returns the group value,
   * otherwise returns the bundle value.
   *
   * This is used for the widget_type admin part
   *
   * @param $bundle
   * @return string
   */
  public function getWidgetType() {
    $config = \Drupal::service('config.factory')->getEditable('stacks.settings');
    $widget_type_groups = $config->get("widget_type_groups");

    if (preg_match('/.+?(?=_)/', $this->bundle(), $matches)) {
      $group = $matches[0];
      if (isset($widget_type_groups[$group])) {
        return $group;
      }
    }

    return $this->bundle();
  }

}
