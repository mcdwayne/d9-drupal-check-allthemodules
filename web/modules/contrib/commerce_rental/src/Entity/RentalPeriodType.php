<?php

namespace Drupal\commerce_rental\Entity;

use Drupal\commerce\Entity\CommerceBundleEntityBase;

/**
 * Defines the rental period entity class.
 *
 * @ConfigEntityType(
 *   id = "commerce_rental_period_type",
 *   label = @Translation("Rental period type"),
 *   label_collection = @Translation("Rental period types"),
 *   label_singular = @Translation("rental period type"),
 *   label_plural = @Translation("rental period types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count rental period type",
 *     plural = "@count rental period types",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\commerce_rental\RentalPeriodTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\commerce_rental\Form\RentalPeriodTypeForm",
 *       "edit" = "Drupal\commerce_rental\Form\RentalPeriodTypeForm",
 *       "delete" = "Drupal\commerce\Form\CommerceBundleEntityDeleteFormBase"
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "commerce_rental_period_type",
 *   admin_permission = "administer commerce_rental_type",
 *   bundle_of = "commerce_rental_period",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "calculator",
 *     "traits",
 *   },
 *   links = {
 *     "add-form" = "/admin/commerce/config/rental-period-types/add",
 *     "edit-form" = "/admin/commerce/config/rental-period-types/{commerce_rental_period_type}/edit",
 *     "delete-form" = "/admin/commerce/config/rental-period-types/{commerce_rental_period_type}/delete",
 *     "collection" =  "/admin/commerce/config/rental-period-types"
 *   }
 * )
 */
class RentalPeriodType extends CommerceBundleEntityBase implements RentalPeriodTypeInterface {

  /**
   * The rental period calculator plugin id.
   *
   * @var string
   */
  protected $calculator;

  /**
   * {@inheritdoc}
   */
  public function getCalculatorTypesList() {
    /** @var \Drupal\commerce_rental\PeriodCalculatorManager $type */
    $type = \Drupal::service('plugin.manager.period_calculator');
    return $type->getDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public function getCalculatorId() {
    return $this->calculator;
  }

  /**
   * {@inheritdoc}
   */
  public function getCalculator() {
    /** @var \Drupal\commerce_rental\PeriodCalculatorManager $type */
    $type = \Drupal::service('plugin.manager.period_calculator');
    return $type->createInstance($this->calculator);
  }

  /**
   * {@inheritdoc}
   */
  public function setCalculatorId($calculator) {
    $this->calculator = $calculator;
    return $this;
  }

}
