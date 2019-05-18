<?php

namespace Drupal\dmt\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;

/**
 * Defines the Weekly usage entity.
 *
 * @ingroup dmt
 *
 * @ContentEntityType(
 *   id = "weekly_usage",
 *   label = @Translation("Weekly usage"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\dmt\WeeklyUsageListBuilder",
 *     "views_data" = "Drupal\dmt\Entity\WeeklyUsageViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\dmt\Form\WeeklyUsageForm",
 *       "add" = "Drupal\dmt\Form\WeeklyUsageForm",
 *       "edit" = "Drupal\dmt\Form\WeeklyUsageForm",
 *       "delete" = "Drupal\dmt\Form\WeeklyUsageDeleteForm",
 *     },
 *     "access" = "Drupal\dmt\WeeklyUsageAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\dmt\WeeklyUsageHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "weekly_usage",
 *   admin_permission = "administer weekly usage entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/weekly_usage/{weekly_usage}",
 *     "add-form" = "/admin/structure/weekly_usage/add",
 *     "edit-form" = "/admin/structure/weekly_usage/{weekly_usage}/edit",
 *     "delete-form" = "/admin/structure/weekly_usage/{weekly_usage}/delete",
 *     "collection" = "/admin/structure/weekly_usage",
 *   },
 *   field_ui_base_route = "weekly_usage.settings"
 * )
 */
class WeeklyUsage extends ContentEntityBase implements WeeklyUsageInterface {

  /**
   * {@inheritdoc}
   */
  public function getModule() {
    return $this->get('module')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setModule($module) {
    $this->set('module', $module);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getInstallCount() {
    return $this->get('install_count')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setInstallCount($count) {
    $this->set('install_count', $count);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDate() {
    return $this->get('date')->date;
  }

  /**
   * {@inheritdoc}
   */
  public function setDate($date) {
    $this->set('date', $date->format(DATETIME_DATE_STORAGE_FORMAT));
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['install_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Usage'))
      ->setSetting('unsigned', TRUE);

    $fields['date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Date'))
      ->setDescription(t('Weekly usage date.'))
      ->setRequired(TRUE)
      ->setSetting('datetime_type', DateTimeItem::DATETIME_TYPE_DATE);

    $fields['module'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Module'))
      ->setDescription(t('The module of the Weekly usage entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'module')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
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

    return $fields;
  }

}
