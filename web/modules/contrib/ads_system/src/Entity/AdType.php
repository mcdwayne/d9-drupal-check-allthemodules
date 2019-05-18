<?php

namespace Drupal\ads_system\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\ads_system\AdTypeInterface;

/**
 * Defines the Ad type entity.
 *
 * @ConfigEntityType(
 *   id = "ad_type",
 *   label = @Translation("Ad type"),
 *   handlers = {
 *     "list_builder" = "Drupal\ads_system\AdTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\ads_system\Form\AdTypeForm",
 *       "edit" = "Drupal\ads_system\Form\AdTypeForm",
 *       "delete" = "Drupal\ads_system\Form\AdTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\ads_system\AdTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "ad_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "ad",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/ad-types/ad_type/{ad_type}",
 *     "add-form" = "/admin/structure/ad-types/ad_type/add",
 *     "edit-form" = "/admin/structure/ad-types/ad_type/{ad_type}/edit",
 *     "delete-form" = "/admin/structure/ad-types/ad_type/{ad_type}/delete",
 *     "collection" = "/admin/structure/ad-types/ad_type"
 *   }
 * )
 */
class AdType extends ConfigEntityBundleBase implements AdTypeInterface {
  /**
   * The Ad type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Ad type label.
   *
   * @var string
   */
  protected $label;

}
