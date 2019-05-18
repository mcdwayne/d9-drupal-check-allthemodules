<?php

/**
 * @file
 * Contains \Drupal\redhen_asset\Entity\Asset.
 */

namespace Drupal\redhen_asset\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\redhen_asset\AssetInterface;

/**
 * Defines the Asset entity.
 *
 * @ingroup redhen_asset
 *
 * @ContentEntityType(
 *   id = "redhen_asset",
 *   label = @Translation("Asset"),
 *   label_singular = @Translation("asset"),
 *   label_plural = @Translation("assets"),
 *   label_count = @PluralTranslation(
 *     singular = "@count asset",
 *     plural = "@count asset",
 *   ),
 *   bundle_label = @Translation("Asset type"),
 *   handlers = {
 *     "view_builder" = "Drupal\redhen_asset\AssetViewBuilder",
 *     "list_builder" = "Drupal\redhen_asset\AssetListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\redhen_asset\Form\AssetForm",
 *       "add" = "Drupal\redhen_asset\Form\AssetForm",
 *       "edit" = "Drupal\redhen_asset\Form\AssetForm",
 *       "delete" = "Drupal\redhen_asset\Form\AssetDeleteForm",
 *     },
 *     "access" = "Drupal\redhen_asset\AssetAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\redhen_asset\AssetHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "redhen_asset",
 *   revision_table = "redhen_asset_revision",
 *   admin_permission = "administer asset entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "revision" = "revision_id",
 *     "bundle" = "type",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/redhen/asset/{redhen_asset}",
 *     "add-form" = "/redhen/asset/add/{redhen_asset_type}",
 *     "edit-form" = "/redhen/asset/{redhen_asset}/edit",
 *     "delete-form" = "/redhen/asset/{redhen_asset}/delete",
 *     "collection" = "/redhen/asset",
 *   },
 *   bundle_entity_type = "redhen_asset_type",
 *   field_ui_base_route = "entity.redhen_asset_type.edit_form"
 * )
 */
class Asset extends ContentEntityBase implements AssetInterface {
  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->getName();
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    $name = $this->get('name')->value;
    // Allow other modules to alter the name of the org.
    \Drupal::moduleHandler()->alter('redhen_asset_name', $name, $this);
    return $name;
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
   * {@inheritdoc}
   */
  public function isActive() {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   */
  public function setActive($active) {
    $this->set('status', $active ? REDHEN_ASSET_INACTIVE : REDHEN_ASSET_ACTIVE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the asset.'))
      ->setSettings(array(
        'max_length' => 50,
        'text_processing' => 0,
      ))
      ->setDefaultValue('')
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -10,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRevisionable(TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Active'))
      ->setDescription(t('A boolean indicating whether the asset is active.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'boolean_checkbox',
        'settings' => array(
          'display_label' => TRUE,
        ),
        'weight' => 16,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setRevisionable(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the asset was created.'))
      ->setRevisionable(TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the asset was last edited.'))
      ->setRevisionable(TRUE);

    return $fields;
  }

}
