<?php

namespace Drupal\menu_svg_icons\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\menu_svg_icons\IconSetInterface;
use Drupal\menu_svg_icons\Entity\MenuSvgIconsItemTree;
use enshrined\svgSanitize\Sanitizer;

/**
 * Defines the IconSet entity.
 *
 * @ConfigEntityType(
 *   id = "menu_svg_icons_icon_set",
 *   label = @Translation("Icon set"),
 *   handlers = {
 *     "list_builder" = "Drupal\menu_svg_icons\Controller\IconSetListBuilder",
 *     "form" = {
 *       "add" = "Drupal\menu_svg_icons\Form\IconSetForm",
 *       "edit" = "Drupal\menu_svg_icons\Form\IconSetForm",
 *       "delete" = "Drupal\menu_svg_icons\Form\IconSetDeleteForm",
 *     }
 *   },
 *   config_prefix = "icon_set",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/media/menu-svg-icons/icon-set/{menu_svg_icons_icon_set}",
 *     "delete-form" = "/admin/config/media/menu-svg-icons/icon-set/{menu_svg_icons_icon_set}/delete",
 *     "collection" = "/admin/config/media/menu-svg-icons",
 *   },
 * )
 */
class IconSet extends ConfigEntityBase implements IconSetInterface {

  /**
   * The IconSet ID.
   *
   * @var string
   */
  public $id;

  /**
   * The IconSet label.
   *
   * @var string
   */
  public $label;

  /**
   * The IconSet description.
   *
   * @var string
   */
  public $description;

  /**
   * The IconSet placement, either 'left' or 'right'.
   *
   * @var string
   */
  public $placement;

  /**
   * The IconSet source, a valid XML string.
   *
   * @var string
   */
  public $source;

  /**
   * The height of the icons that a set will print icons with.
   *
   * @var string
   */
  public $icon_height;

  /**
   * The width of the icons that a set will print icons with.
   *
   * @var string
   */
  public $icon_width;

  /**
   * Turns menu link titles into titles with SVG icons.
   *
   * This function calls itself recursively in order to add the icons to the
   * menu link titles on all levels.
   *
   * @param \Drupal\menu_svg_icons\IconSetInterface $icon_set
   *   The IconSet config entity which icons are pulled from.
   * @param array $menu_items
   *   The menu items to alter, passed by reference.
   */
  public static function processMenuLinks(IconSetInterface $icon_set, array &$menu_items) {
    foreach ($menu_items as $key => $item) {
      // Assume it's a 'menu_link_content', overwrite if a module is providing the link.
      $icon = $item['url']->getOption('icon') == 'no_icon' ? NULL : $item['url']->getOption('icon');
      $original_link = $item['original_link'];
      if (!$original_link instanceof Drupal\menu_link_content\Plugin\Menu\MenuLinkContent) {
        if ($plugin_definition = $original_link->getPluginDefinition()) {
          $menu_item_tree = MenuSvgIconsItemTree::load($plugin_definition['id']);
          if(!empty($menu_item_tree) && $menu_item_tree->icon) {
            $icon = $menu_item_tree->icon;
          }
        }
      }

      $item_title = $item['title'];
      $menu_items[$key]['title'] = [
        '#theme' => 'menu_svg_icons_link',
        '#title' => $item_title,
        '#icon' => $icon,
        '#icon_height' => $icon_set->get('icon_width') ? 'height: ' . $icon_set->get('icon_width') . 'px;' : '',
        '#icon_width' => $icon_set->get('icon_height') ? ' width: ' . $icon_set->get('icon_height') . 'px;' : '',
        '#placement' => $icon_set->get('placement'),
      ];

      if (!empty($item['below'])) {
        self::processMenuLinks($icon_set, $menu_items[$key]['below']);
      }
    }
  }

  /**
   * Helper function to sanitize a svg string.
   *
   * @param string $svg
   *   SVG string.
   *
   * @return string
   *   Markup.
   */
  public static function sanitize($svg) {
    // Instantiate the sanitizer class.
    $sanitizer = new Sanitizer();

    // Run the svg through the sanitizer.
    $sanitized_svg = $sanitizer->sanitize($svg);

    return $sanitized_svg;
  }

}
