<?php

namespace Drupal\simple_megamenu\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Simple mega menu type entity.
 *
 * @ConfigEntityType(
 *   id = "simple_mega_menu_type",
 *   label = @Translation("Simple mega menu type"),
 *   handlers = {
 *     "list_builder" = "Drupal\simple_megamenu\SimpleMegaMenuTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\simple_megamenu\Form\SimpleMegaMenuTypeForm",
 *       "edit" = "Drupal\simple_megamenu\Form\SimpleMegaMenuTypeForm",
 *       "delete" = "Drupal\simple_megamenu\Form\SimpleMegaMenuTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\simple_megamenu\SimpleMegaMenuTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "simple_mega_menu_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "simple_mega_menu",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/simple_mega_menu_type/{simple_mega_menu_type}",
 *     "add-form" = "/admin/structure/simple_mega_menu_type/add",
 *     "edit-form" = "/admin/structure/simple_mega_menu_type/{simple_mega_menu_type}/edit",
 *     "delete-form" = "/admin/structure/simple_mega_menu_type/{simple_mega_menu_type}/delete",
 *     "collection" = "/admin/structure/simple_mega_menu_type"
 *   }
 * )
 */
class SimpleMegaMenuType extends ConfigEntityBundleBase implements SimpleMegaMenuTypeInterface {

  /**
   * The Simple mega menu type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Simple mega menu type label.
   *
   * @var string
   */
  protected $label;

  /**
   * The target menus this mega menu type is used for.
   *
   * @var array
   */
  protected $targetMenu = [];

  /**
   * {@inheritdoc}
   */
  public function getTargetMenu() {
    return $this->targetMenu;
  }

  /**
   * {@inheritdoc}
   */
  public function setTargetMenu($target_menu) {
    $this->targetMenu = $target_menu;
    return $this;
  }

}
