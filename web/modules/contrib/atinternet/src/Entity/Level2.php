<?php

namespace Drupal\atinternet\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Level2 entity.
 *
 * @ConfigEntityType(
 *   id = "level2",
 *   label = @Translation("Level2"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\atinternet\Level2ListBuilder",
 *     "form" = {
 *       "add" = "Drupal\atinternet\Form\Level2Form",
 *       "edit" = "Drupal\atinternet\Form\Level2Form",
 *       "delete" = "Drupal\atinternet\Form\Level2DeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\atinternet\Level2HtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "level2",
 *   admin_permission = "at internet administration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/system/atinternet/level2/{level2}",
 *     "add-form" = "/admin/config/system/atinternet/level2/add",
 *     "edit-form" = "/admin/config/system/atinternet/level2/{level2}/edit",
 *     "delete-form" = "/admin/config/system/atinternet/level2/{level2}/delete",
 *     "collection" = "/admin/config/system/atinternet/level2"
 *   }
 * )
 */
class Level2 extends ConfigEntityBase implements Level2Interface {

  /**
   * The Level2 entity ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Level2 entity label.
   *
   * @var string
   */
  protected $label;

}
