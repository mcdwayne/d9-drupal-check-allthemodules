<?php

namespace Drupal\upgrade_tool\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the Upgrade log entity.
 *
 * @ingroup upgrade_tool
 *
 * @ContentEntityType(
 *   id = "upgrade_log",
 *   label = @Translation("Upgrade log"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\upgrade_tool\UpgradeLogListBuilder",
 *     "views_data" = "Drupal\upgrade_tool\Entity\UpgradeLogViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\upgrade_tool\Form\UpgradeLogForm",
 *       "add" = "Drupal\upgrade_tool\Form\UpgradeLogForm",
 *       "edit" = "Drupal\upgrade_tool\Form\UpgradeLogForm",
 *       "delete" = "Drupal\upgrade_tool\Form\UpgradeLogDeleteForm",
 *     },
 *     "access" = "Drupal\upgrade_tool\UpgradeLogAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\upgrade_tool\UpgradeLogHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "upgrade_log",
 *   admin_permission = "administer upgrade log entities",
 *   fieldable = FALSE,
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/config/upgrade_log/{upgrade_log}",
 *     "add-form" = "/admin/config/upgrade_log/add",
 *     "edit-form" = "/admin/config/upgrade_log/{upgrade_log}/edit",
 *     "delete-form" = "/admin/config/upgrade_log/{upgrade_log}/delete",
 *     "diff" = "/admin/config/upgrade_log/{upgrade_log}/diff",
 *     "collection" = "/admin/config/upgrade_log",
 *   },
 *   field_ui_base_route = "upgrade_log.settings"
 * )
 */
class UpgradeLog extends ContentEntityBase implements UpgradeLogInterface {

  use EntityChangedTrait;

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
  public function getConfigPath() {
    return $this->get('config_path')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfigPath($path) {
    $this->set('config_path', $path);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigProperty() {
    return $this->get('config_property')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfigProperty($property) {
    $this->set('config_property', $property);
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
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('Config name.'))
      ->setSettings([
        'max_length' => 100,
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
      ->setDisplayConfigurable('view', TRUE);

    $fields['config_path'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Config path'))
      ->setDescription(t('Path to config.'))
      ->setSettings([
        'max_length' => 300,
        'text_processing' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -3,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -3,
      ])
      ->setRequired(FALSE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['config_property'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Config property'))
      ->setDescription(t('Config property.'))
      ->setSettings([
        'max_length' => 300,
        'text_processing' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -2,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -2,
      ])
      ->setRequired(FALSE)
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
