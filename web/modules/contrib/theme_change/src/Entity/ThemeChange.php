<?php

namespace Drupal\theme_change\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\theme_change\ThemeChangeInterface;

/**
 * Defines the Theme Change entity.
 *
 * @ConfigEntityType(
 *   id = "theme_change",
 *   label = @Translation("Theme Change"),
 *   handlers = {
 *     "list_builder" = "Drupal\theme_change\Controller\ThemeChangeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\theme_change\Form\ThemeChangeForm",
 *       "edit" = "Drupal\theme_change\Form\ThemeChangeForm",
 *       "delete" = "Drupal\theme_change\Form\ThemeChangeDeleteForm",
 *     }
 *   },
 *   config_prefix = "theme_change",
 *   admin_permission = "access theme change settings page",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "path" = "path",
 *     "route" = "route",
 *     "type" = "type",
 *     "theme" = "theme"
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/system/theme_change/{theme_change}",
 *     "delete-form" = "/admin/config/system/theme_change/{theme_change}/delete",
 *   }
 * )
 */
class ThemeChange extends ConfigEntityBase implements ThemeChangeInterface {

  /**
   * The UrlRedirect ID.
   *
   * @var string
   */
  public $id;

  /**
   * The Themechange label.
   *
   * @var string
   */
  public $label;

  /**
   * The Themechange path url.
   *
   * @var string
   */
  protected $path;

  /**
   * The Themechange route name.
   *
   * @var string
   */
  protected $route;

  /**
   * The Themechange type..
   *
   * @var string
   */
  protected $type;

  /**
   * The Themechange used theme.
   *
   * @var string
   */
  protected $theme;

  /**
   * {@inheritdoc}
   */
  public function get_path() {
    return $this->path;
  }

  /**
   * {@inheritdoc}
   */
  public function get_route() {
    return $this->route;
  }

  /**
   * {@inheritdoc}
   */
  public function get_type() {
    return $this->type;
  }

  /**
   * {@inheritdoc}
   */
  public function get_theme() {
    return $this->theme;
  }

}
