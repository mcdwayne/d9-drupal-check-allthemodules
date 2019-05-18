<?php

namespace Drupal\commerce_installments\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Installment type entity.
 *
 * @ConfigEntityType(
 *   id = "installment_type",
 *   label = @Translation("Installment type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\commerce_installments\InstallmentTypeListBuilder",
 *     "form" = {
 *       "default" = "Drupal\commerce_installments\Form\InstallmentTypeForm",
 *       "add" = "Drupal\commerce_installments\Form\InstallmentTypeForm",
 *       "edit" = "Drupal\commerce_installments\Form\InstallmentTypeForm",
 *       "delete" = "Drupal\commerce_installments\Form\InstallmentTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "installment_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "installment",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "workflow" = "workflow",
 *   },
 *   links = {
 *     "collection" = "/admin/commerce/config/installment_type",
 *     "add-form" = "/admin/commerce/config/installment_type/add",
 *     "canonical" = "/admin/commerce/config/installment_type/manage/{installment_type}",
 *     "edit-form" = "/admin/commerce/config/installment_type/manage/{installment_type}/edit",
 *     "delete-form" = "/admin/commerce/config/installment_type/manage/{installment_type}/delete",
 *   }
 * )
 */
class InstallmentType extends ConfigEntityBundleBase implements InstallmentTypeInterface {

  /**
   * The order type workflow ID.
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
