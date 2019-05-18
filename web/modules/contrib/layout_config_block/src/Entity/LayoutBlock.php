<?php

namespace Drupal\layout_config_block\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Defines the Layout block entity.
 *
 * @ConfigEntityType(
 *   id = "layout_block",
 *   label = @Translation("Layout block"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\layout_config_block\LayoutBlockListBuilder",
 *     "form" = {
 *       "add" = "Drupal\layout_config_block\Form\LayoutBlockForm",
 *       "edit" = "Drupal\layout_config_block\Form\LayoutBlockForm",
 *       "delete" = "Drupal\layout_config_block\Form\LayoutBlockDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\layout_config_block\LayoutBlockHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "layout_block",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/layout_block/{layout_block}",
 *     "add-form" = "/admin/structure/layout_block/add",
 *     "edit-form" = "/admin/structure/layout_block/{layout_block}/edit",
 *     "delete-form" = "/admin/structure/layout_block/{layout_block}/delete",
 *     "collection" = "/admin/structure/layout_block"
 *   }
 * )
 */
class LayoutBlock extends ConfigEntityBase implements ConfigEntityInterface {

  /**
   * The Layout block ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Layout block label.
   *
   * @var string
   */
  protected $label;

}
