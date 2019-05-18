<?php

namespace Drupal\flashpoint_community_content\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Flashpoint community content type entity.
 *
 * @ConfigEntityType(
 *   id = "flashpoint_community_c_type",
 *   label = @Translation("Flashpoint community content type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\flashpoint_community_content\FlashpointCommunityContentTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\flashpoint_community_content\Form\FlashpointCommunityContentTypeForm",
 *       "edit" = "Drupal\flashpoint_community_content\Form\FlashpointCommunityContentTypeForm",
 *       "delete" = "Drupal\flashpoint_community_content\Form\FlashpointCommunityContentTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\flashpoint_community_content\FlashpointCommunityContentTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "flashpoint_community_content",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/flashpoint/type/{flashpoint_community_c_type}",
 *     "add-form" = "/admin/structure/flashpoint/type/add",
 *     "edit-form" = "/admin/structure/flashpoint/type/{flashpoint_community_c_type}/edit",
 *     "delete-form" = "/admin/structure/flashpoint/type/{flashpoint_community_c_type}/delete",
 *     "collection" = "/admin/structure/flashpoint/type"
 *   }
 * )
 */
class FlashpointCommunityContentType extends ConfigEntityBundleBase implements FlashpointCommunityContentTypeInterface {

  /**
   * The Flashpoint community content type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Flashpoint community content type label.
   *
   * @var string
   */
  protected $label;

}
