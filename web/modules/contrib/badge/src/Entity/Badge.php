<?php

namespace Drupal\badge\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Badge entity.
 *
 * @ingroup badge
 *
 * @ContentEntityType(
 *   id = "badge",
 *   label = @Translation("Badge"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\badge\BadgeListBuilder",
 *     "views_data" = "Drupal\badge\Entity\BadgeViewsData",
 *     "translation" = "Drupal\badge\BadgeTranslationHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\badge\Form\BadgeForm",
 *       "add" = "Drupal\badge\Form\BadgeForm",
 *       "edit" = "Drupal\badge\Form\BadgeForm",
 *       "delete" = "Drupal\badge\Form\BadgeDeleteForm",
 *     },
 *     "access" = "Drupal\badge\BadgeAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\badge\BadgeHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "badge",
 *   data_table = "badge_field_data",
 *   translatable = TRUE,
  *   admin_permission = "administer badge entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/badge/badge/{badge}",
 *     "add-form" = "/admin/structure/badge/badge/add",
 *     "edit-form" = "/admin/structure/badge/badge/{badge}/edit",
 *     "delete-form" = "/admin/structure/badge/badge/{badge}/delete",
 *     "collection" = "/admin/structure/badge/badge",
 *   },
 *   field_ui_base_route = "entity.badge.collection"
 * )
 */
class Badge extends ContentEntityBase implements BadgeInterface {

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
  public function setImage($image) {
    $this->set('image', $image);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getImage() {
    return $this->get('image')->value;
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
    $this->set('status', $published ? NODE_PUBLISHED : NODE_NOT_PUBLISHED);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['image'] = BaseFieldDefinition::create('image')
      ->setLabel(t('Image'))
      ->setDescription(t('An image representing the badge.'))
      ->setSettings(array(
        'target_type' => 'file'
      ))
      ->setDisplayOptions('view', array(
        'type' => 'image',
        'weight' => 1,
        'label' => 'hidden',
        'settings' => array(
          'image_style' => 'thumbnail',
        ),
      ))
      ->setDisplayOptions('form', array('type' => 'image_image'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['weight'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Weight'))
      ->setDescription(t('The weight of the Badge'))
      ->setDefaultValue(0)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Badge entity.'))
      ->setSettings(array(
        'max_length' => 50,
        'text_processing' => 0,
      ))
      ->setDefaultValue('')
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
      ->setDescription(t('A boolean indicating whether the Badge is published.'))
      ->setDefaultValue(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}
