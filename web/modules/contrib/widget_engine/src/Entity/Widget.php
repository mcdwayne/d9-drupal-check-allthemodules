<?php

namespace Drupal\widget_engine\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Widget entity.
 *
 * @ingroup widget_engine
 *
 * @ContentEntityType(
 *   id = "widget",
 *   label = @Translation("Widget"),
 *   bundle_label = @Translation("Widget type"),
 *   handlers = {
 *     "view_builder" = "Drupal\widget_engine\WidgetEngineViewBuilder",
 *     "list_builder" = "Drupal\widget_engine\WidgetListBuilder",
 *     "views_data" = "Drupal\widget_engine\Entity\WidgetViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\widget_engine\Form\WidgetForm",
 *       "add" = "Drupal\widget_engine\Form\WidgetForm",
 *       "edit" = "Drupal\widget_engine\Form\WidgetForm",
 *       "delete" = "Drupal\widget_engine\Form\WidgetDeleteForm",
 *     },
 *     "access" = "Drupal\widget_engine\WidgetAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\widget_engine\WidgetHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "widget",
 *   data_table = "widget_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer widget entities",
 *   entity_keys = {
 *     "id" = "wid",
 *     "bundle" = "type",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/widget/{widget}",
 *     "add-page" = "/admin/content/widget/add",
 *     "add-form" = "/admin/content/widget/add/{widget_type}",
 *     "edit-form" = "/admin/content/widget/{widget}/edit",
 *     "delete-form" = "/admin/content/widget/{widget}/delete",
 *     "collection" = "/admin/content/widgets",
 *   },
 *   bundle_entity_type = "widget_type",
 *   field_ui_base_route = "entity.widget_type.edit_form"
 * )
 */
class Widget extends ContentEntityBase implements WidgetInterface {

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
  public function getType() {
    return $this->bundle();
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
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Widget entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'entity_reference_autocomplete',
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
      ->setLabel(t('Title'))
      ->setRequired(TRUE)
      ->setDescription(t('The name of the Widget entity.'))
      ->setSettings(array(
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDefaultValue('')
      ->setTranslatable(TRUE)
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

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Widget is published.'))
      ->setDefaultValue(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['widget_preview'] = BaseFieldDefinition::create('image')
      ->setLabel(t('Widget preview'))
      ->setDescription(t('The image of widget preview.'))
      ->setDisplayOptions('view', [
        'label'   => 'above',
        'type'    => 'image',
        'weight'  => 0,
      ])
      ->setSettings([
        'alt_field' => 0,
        'alt_field_required' => 0,
        'file_extensions' => 'png gif jpeg jpg',
        'preview_image_style' => '',
      ])
      ->setDisplayOptions('form', [
        'type'    => 'hidden',
        'weight'  => 10,
      ])
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'hidden',
        'weight' => 12,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
