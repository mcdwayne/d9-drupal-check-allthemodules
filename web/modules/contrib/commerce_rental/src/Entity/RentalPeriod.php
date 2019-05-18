<?php

namespace Drupal\commerce_rental\Entity;

use Drupal\commerce\EntityHelper;
use Drupal\commerce_price\Price;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Url;
use Drupal\user\UserInterface;

/**
 * Defines the rental period entity class.
 *
 * @ContentEntityType(
 *   id = "commerce_rental_period",
 *   label = @Translation("Rental period"),
 *   label_collection = @Translation("Rental periods"),
 *   label_singular = @Translation("rental period"),
 *   label_plural = @Translation("rental periods"),
 *   label_period = @PluralTranslation(
 *     singular = "@count rental period",
 *     plural = "@count rental periods",
 *   ),
 *   bundle_label = @Translation("Rental period type"),
 *   handlers = {
 *     "list_builder" = "Drupal\commerce_rental\RentalPeriodListBuilder",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\commerce_rental\Form\RentalPeriodForm",
 *       "add" = "Drupal\commerce_rental\Form\RentalPeriodForm",
 *       "edit" = "Drupal\commerce_rental\Form\RentalPeriodForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *       "delete-multiple" = "Drupal\entity\Routing\DeleteMultipleRouteProvider",
 *     },
 *     "translation" = "Drupal\content_translation\ContentTranslationHandler"
 *   },
 *   admin_permission = "administer commerce_rental",
 *   fieldable = TRUE,
 *   translatable = TRUE,
 *   content_translation_ui_skip = TRUE,
 *   base_table = "commerce_rental_period",
 *   data_table = "commerce_rental_period_field_data",
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "type",
 *     "langcode" = "langcode",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *   },
 *   links = {
 *     "canonical" = "/admin/commerce/rental-periods/{commerce_rental_period}",
 *     "add-page" = "/admin/commerce/rental-periods/add",
 *     "add-form" = "/admin/commerce/rental-periods/add/{commerce_rental_period_type}",
 *     "edit-form" = "/admin/commerce/rental-periods/{commerce_rental_period}/edit",
 *     "delete-form" = "/admin/commerce/rental-periods/{commerce_rental_period}/delete",
 *     "delete-multiple-form" = "/admin/commerce/rental-periods/delete",
 *     "collection" = "/admin/commerce/rental-periods"
 *   },
 *   bundle_entity_type = "commerce_rental_period_type",
 *   field_ui_base_route = "entity.commerce_rental_period_type.edit_form",
 * )
 */
class RentalPeriod extends ContentEntityBase implements RentalPeriodInterface {

  const GRANULARITY_DAYS = 'days';
  const GRANULARITY_HOURS = 'hours';

  /**
   * {@inheritdoc}
   */
  public function getGranularity() {
    return $this->get('granularity')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setGranularity($granularity) {
    $this->set('granularity', $granularity);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTimeUnits() {
    return $this->get('time_units')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTimeUnits($time_units) {
    $this->set('time_units', $time_units);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function calculatePeriod($start_date, $end_date) {
    $type = RentalPeriodType::load($this->bundle());
    return $type->getCalculator()->calculate($start_date, $end_date, $this);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The rental period title.'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setSettings([
        'default_value' => '',
        'max_length' => 255,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['granularity'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Granularity'))
      ->setDescription(t('The granularity for the time units attached to this rental period'))
      ->setSettings([
        'allowed_values' => [self::GRANULARITY_DAYS => 'Days', self::GRANULARITY_HOURS => 'Hours']
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 1,
      ])
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['time_units'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Time Units'))
      ->setDescription(t('The amount of time units based on granularity that defines this period'))
      ->setRequired(TRUE)
      ->setSettings([
        'min' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The rental period language code.'));


    return $fields;
  }
}
