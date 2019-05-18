<?php

namespace Drupal\menu_svg_icons\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the IconSetMenuItemTree entity.
 *
 * @ConfigEntityType(
 *   id = "menu_svg_icons_item_tree",
 *   label = @Translation("Icon set menu item tree"),
 *   config_prefix = "item_tree",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *   },
 * )
 */
class MenuSvgIconsItemTree extends ConfigEntityBase {

  /**
   * The IconSetMenuItemTree ID.
   *
   * @var string
   */
  public $id;

}
