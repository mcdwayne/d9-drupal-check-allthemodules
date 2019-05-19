<?php

/**
 * @file
 * Contains \Drupal\user_badges\Entity\Badge.
 */

namespace Drupal\user_badges\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user_badges\BadgeInterface;
use Drupal\user\RoleInterface;

/**
 * Defines the Badge entity.
 *
 * @ingroup user_badges
 *
 * @ContentEntityType(
 *   id = "badge",
 *   label = @Translation("Badge"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\user_badges\BadgeListBuilder",
 *     "views_data" = "Drupal\user_badges\Entity\BadgeViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\user_badges\Entity\Form\BadgeForm",
 *       "add" = "Drupal\user_badges\Entity\Form\BadgeForm",
 *       "edit" = "Drupal\user_badges\Entity\Form\BadgeForm",
 *       "delete" = "Drupal\user_badges\Entity\Form\BadgeDeleteForm",
 *     },
 *   },
 *   base_table = "badge",
 *   bundle_entity_type = "badge_type",
 *   admin_permission = "administer Badge entity",
 *   field_ui_base_route = "entity.badge_type.edit_form",
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "type",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/badge/{badge}",
 *     "edit-form" = "/admin/badge/{badge}/edit",
 *     "delete-form" = "/admin/badge/{badge}/delete"
 *   },
 *   field_ui_base_route = "badge.settings"
 * )
 */
class Badge extends ContentEntityBase implements BadgeInterface {

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
  public function getBadgeWeight() {
    return $this->get('weight')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setBadgeWeight($weight) {
    return $this->set('weight', $weight);
  }

  /**
   * {@inheritdoc}
   */
  public function getBadgeRoleIds() {
    //return $this->get('role_id')->value;
    $roles = array();
    foreach ($this->get('role_id') as $role) {
      $roles[] = $role->target_id;
    }

    return $roles;
  }

  /**
   * {@inheritdoc}
   */
  public function setBadgeRoleId($rid) {
    // return $this->set('role_id', $rid);
    $roles = $this->getBadgeRoleIds();
    $roles[] = $rid;
    $this->set('role_id', array_unique($roles));
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
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

    $fields['weight'] = BaseFieldDefinition::create('list_integer')
      ->setLabel(t('Badge Weight'))
      ->setDescription(t('The weight of badge that allows to display badges as per weight order.'))
      ->setSetting('unsigned', TRUE)
      ->setRequired(TRUE)
      ->setSetting('allowed_values', range(-10, 10))
      ->setDisplayOptions('form', array(
        'type' => 'options_select',
        'weight' => -2,
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['image'] = BaseFieldDefinition::create('image')
      ->setLabel(t('Image'))
      ->setDescription(t('The badge image'))
      ->setDisplayOptions('view', array(
        'type' => 'image',
        'weight' => 1,
        'label' => 'hidden',
        'settings' => array(
          'image_style' => 'thumbnail',
        ),
      ))
      ->setDisplayOptions('form', array(
        'type' => 'image_image',
        'weight' => 1,
      ))
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['role_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Role ID'))
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setDescription(t('The ID of the Role entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user_role')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'string',
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

    return $fields;
  }

}
