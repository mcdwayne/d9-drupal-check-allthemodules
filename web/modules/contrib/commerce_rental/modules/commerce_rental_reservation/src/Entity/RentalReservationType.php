<?php

namespace Drupal\commerce_rental_reservation\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the rental reservation type entity class.
 *
 * @ConfigEntityType(
 *   id = "commerce_rental_reservation_type",
 *   label = @Translation("Rental reservation type"),
 *   label_collection = @Translation("Rental reservation types"),
 *   label_singular = @Translation("rental reservation type"),
 *   label_plural = @Translation("rental reservation types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count rental reservation type",
 *     plural = "@count rental reservation types",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\commerce_rental_reservation\RentalReservationTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\commerce_rental_reservation\Form\RentalReservationTypeForm",
 *       "edit" = "Drupal\commerce_rental_reservation\Form\RentalReservationTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "commerce_rental_reservation_type",
 *   admin_permission = "administer commerce_rental_type",
 *   bundle_of = "commerce_rental_reservation",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "workflow",
 *     "reservationSelector",
 *   },
 *   links = {
 *     "add-form" = "/admin/commerce/config/rental-reservation-types/add",
 *     "edit-form" = "/admin/commerce/config/rental-reservation-types/{commerce_rental_reservation_type}/edit",
 *     "delete-form" = "/admin/commerce/config/rental-reservation-types/{commerce_rental_reservation_type}/delete",
 *     "collection" =  "/admin/commerce/config/rental-reservation-types"
 *   }
 * )
 */
class RentalReservationType extends ConfigEntityBundleBase implements RentalReservationTypeInterface {

  /**
   * The rental reservation type workflow ID.
   *
   * @var string
   */
  protected $workflow;

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
}
