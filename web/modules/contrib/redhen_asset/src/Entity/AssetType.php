<?php

/**
 * @file
 * Contains \Drupal\redhen_asset\Entity\AssetType.
 */

namespace Drupal\redhen_asset\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\redhen_asset\AssetTypeInterface;

/**
 * Defines the Asset type entity.
 *
 * @ConfigEntityType(
 *   id = "redhen_asset_type",
 *   label = @Translation("Asset type"),
 *   handlers = {
 *     "list_builder" = "Drupal\redhen_asset\AssetTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\redhen_asset\Form\AssetTypeForm",
 *       "edit" = "Drupal\redhen_asset\Form\AssetTypeForm",
 *       "delete" = "Drupal\redhen_asset\Form\AssetTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\redhen_asset\AssetTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "redhen_asset_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "redhen_asset",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/redhen/asset_type/{redhen_asset_type}",
 *     "add-form" = "/admin/structure/redhen/asset_type/add",
 *     "edit-form" = "/admin/structure/redhen/asset_type/{redhen_asset_type}/edit",
 *     "delete-form" = "/admin/structure/redhen/asset_type/{redhen_asset_type}/delete",
 *     "collection" = "/admin/structure/redhen/asset_type"
 *   }
 * )
 */
class AssetType extends ConfigEntityBundleBase implements AssetTypeInterface {
  /**
   * The Asset type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Asset type label.
   *
   * @var string
   */
  protected $label;

}
