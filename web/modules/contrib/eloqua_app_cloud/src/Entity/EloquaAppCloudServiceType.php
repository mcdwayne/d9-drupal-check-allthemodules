<?php

namespace Drupal\eloqua_app_cloud\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Eloqua AppCloud Service type entity.
 *
 * @ConfigEntityType(
 *   id = "eloqua_app_cloud_service_type",
 *   label = @Translation("Eloqua AppCloud Service type"),
 *   handlers = {
 *     "list_builder" = "Drupal\eloqua_app_cloud\EloquaAppCloudServiceTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\eloqua_app_cloud\Form\EloquaAppCloudServiceTypeForm",
 *       "edit" = "Drupal\eloqua_app_cloud\Form\EloquaAppCloudServiceTypeForm",
 *       "delete" = "Drupal\eloqua_app_cloud\Form\EloquaAppCloudServiceTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\eloqua_app_cloud\EloquaAppCloudServiceTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "eloqua_app_cloud_service_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "eloqua_app_cloud_service",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/eloqua_app_cloud_service_type/{eloqua_app_cloud_service_type}",
 *     "add-form" = "/admin/structure/eloqua_app_cloud_service_type/add",
 *     "edit-form" = "/admin/structure/eloqua_app_cloud_service_type/{eloqua_app_cloud_service_type}/edit",
 *     "delete-form" = "/admin/structure/eloqua_app_cloud_service_type/{eloqua_app_cloud_service_type}/delete",
 *     "collection" = "/admin/structure/eloqua_app_cloud_service_type"
 *   }
 * )
 */
class EloquaAppCloudServiceType extends ConfigEntityBundleBase implements EloquaAppCloudServiceTypeInterface {

  /**
   * The Eloqua AppCloud Service type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Eloqua AppCloud Service type label.
   *
   * @var string
   */
  protected $label;

}
