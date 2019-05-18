<?php

/**
 * @file
 * Contains \Drupal\colossal_menu\Entity\LinkType.
 */

namespace Drupal\colossal_menu\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\colossal_menu\LinkTypeInterface;

/**
 * Defines the Link type entity.
 *
 * @ConfigEntityType(
 *   id = "colossal_menu_link_type",
 *   label = @Translation("Link type"),
 *   handlers = {
 *     "list_builder" = "Drupal\colossal_menu\LinkTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\colossal_menu\Form\LinkTypeForm",
 *       "edit" = "Drupal\colossal_menu\Form\LinkTypeForm",
 *       "delete" = "Drupal\colossal_menu\Form\LinkTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "link_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "colossal_menu_link",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/colossal_menu/link_type/{colossal_menu_link_type}",
 *     "add-form" = "/admin/structure/colossal_menu/link_type/add",
 *     "edit-form" = "/admin/structure/colossal_menu/link_type/{colossal_menu_link_type}/edit",
 *     "delete-form" = "/admin/structure/colossal_menu/link_type/{colossal_menu_link_type}/delete",
 *     "collection" = "/admin/structure/colossal_menu/link_type"
 *   }
 * )
 */
class LinkType extends ConfigEntityBundleBase implements LinkTypeInterface {
  /**
   * The Link type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Link type label.
   *
   * @var string
   */
  protected $label;

}
