<?php

namespace Drupal\menu_svg_icons\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the IconSetMenu entity.
 *
 * @ConfigEntityType(
 *   id = "menu_svg_icons_icon_set_menu",
 *   label = @Translation("Icon set menu"),
 *   config_prefix = "icon_set_menu",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *   },
 * )
 */
class IconSetMenu extends ConfigEntityBase {

  /**
   * The IconSetMenu ID.
   *
   * @var string
   */
  public $id;

}
