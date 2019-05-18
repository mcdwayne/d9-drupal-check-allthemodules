<?php

namespace Drupal\commerce_rental_reservation\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the rental instance type entity class.
 *
 * @ConfigEntityType(
 *   id = "commerce_rental_instance_type",
 *   label = @Translation("Rental instance type"),
 *   label_collection = @Translation("Rental instance types"),
 *   label_singular = @Translation("rental instance type"),
 *   label_plural = @Translation("rental instance types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count rental instance type",
 *     plural = "@count rental instance types",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\commerce_rental_reservation\RentalInstanceTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\commerce_rental_reservation\Form\RentalInstanceTypeForm",
 *       "edit" = "Drupal\commerce_rental_reservation\Form\RentalInstanceTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "commerce_rental_instance_type",
 *   admin_permission = "administer commerce_rental_type",
 *   bundle_of = "commerce_rental_instance",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "workflow",
 *     "instanceSelector",
 *   },
 *   links = {
 *     "add-form" = "/admin/commerce/config/rental-instance-types/add",
 *     "edit-form" = "/admin/commerce/config/rental-instance-types/{commerce_rental_instance_type}/edit",
 *     "delete-form" = "/admin/commerce/config/rental-instance-types/{commerce_rental_instance_type}/delete",
 *     "collection" =  "/admin/commerce/config/rental-instance-types"
 *   }
 * )
 */
class RentalInstanceType extends ConfigEntityBundleBase implements RentalInstanceTypeInterface {

  /**
   * The rental instance type workflow ID.
   *
   * @var string
   */
  protected $workflow;

  /**
   * The rental instance selector plugin ID.
   *
   * @var string
   */
  protected $instanceSelector;

  /**
   * {@inheritdoc}
   */
  public function getWorkflowId() {
    return $this->workflow;
  }

  /**
   * {@inheritdoc}
   */
  public function setWorkflowId($workflow_id) {
    $this->workflow = $workflow_id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSelectorId() {
    return $this->instanceSelector;
  }

  /**
   * {@inheritdoc}
   */
  public function getSelector() {
    /** @var \Drupal\commerce_rental_reservation\RentalInstanceSelectorManager $type */
    $type = \Drupal::service('plugin.manager.rental_instance_selector');
    return $type->createInstance($this->instanceSelector);
  }

  /**
   * {@inheritdoc}
   */
  public function setSelectorId($selector_id) {
    $this->instanceSelector = $selector_id;
    return $this;
  }
}
