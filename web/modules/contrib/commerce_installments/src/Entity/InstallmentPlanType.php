<?php

namespace Drupal\commerce_installments\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Installment Plan type entity.
 *
 * @ConfigEntityType(
 *   id = "installment_plan_type",
 *   label = @Translation("Installment Plan type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\commerce_installments\InstallmentPlanTypeListBuilder",
 *     "form" = {
 *       "default" = "Drupal\commerce_installments\Form\InstallmentPlanTypeForm",
 *       "add" = "Drupal\commerce_installments\Form\InstallmentPlanTypeForm",
 *       "edit" = "Drupal\commerce_installments\Form\InstallmentPlanTypeForm",
 *       "delete" = "Drupal\commerce_installments\Form\InstallmentPlanTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "installment_plan_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "installment_plan",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "collection" = "/admin/commerce/config/installment_plan_type",
 *     "add-form" = "/admin/commerce/config/installment_plan_type/add",
 *     "canonical" = "/admin/commerce/config/installment_plan_type/manage/{installment_plan_type}",
 *     "edit-form" = "/admin/commerce/config/installment_plan_type/manage/{installment_plan_type}/edit",
 *     "delete-form" = "/admin/commerce/config/installment_plan_type/manage/{installment_plan_type}/delete",
 *   }
 * )
 */
class InstallmentPlanType extends ConfigEntityBundleBase implements InstallmentPlanTypeInterface {}
