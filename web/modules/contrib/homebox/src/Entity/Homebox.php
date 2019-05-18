<?php

namespace Drupal\homebox\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Homebox entity.
 *
 * @ConfigEntityType(
 *   id = "homebox",
 *   label = @Translation("Homebox"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\homebox\HomeboxListBuilder",
 *     "form" = {
 *       "settings" = "Drupal\homebox\Form\HomeboxSettingsForm",
 *       "add" = "Drupal\homebox\Form\HomeboxForm",
 *       "edit" = "Drupal\homebox\Form\HomeboxForm",
 *       "delete" = "Drupal\homebox\Form\HomeboxDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\homebox\HomeboxHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "homebox",
 *   admin_permission = "administer homebox",
 *   bundle_of = "homebox_layout",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "settings-form" = "/admin/structure/homebox/{homebox}/settings",
 *     "canonical" = "/admin/structure/homebox/{homebox}",
 *     "add-form" = "/admin/structure/homebox/add",
 *     "add-page" = "/admin/content/homebox/homebox/add",
 *     "edit-form" = "/admin/structure/homebox/{homebox}/edit",
 *     "delete-form" = "/admin/structure/homebox/{homebox}/delete",
 *     "collection" = "/admin/structure/homebox"
 *   }
 * )
 */
class Homebox extends ConfigEntityBundleBase implements HomeboxInterface {

  /**
   * The Homebox ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Homebox label.
   *
   * @var string
   */
  protected $label;

  /**
   * The path of the Homebox.
   *
   * @var string
   */
  protected $path;

  /**
   * List of user role IDs to grant access to use this homebox.
   *
   * @var array
   */
  protected $roles;

  /**
   * The layout ID.
   *
   * @var string
   *
   * @todo it seems a little bit confusing because we store here layout ID, not the columns number.
   */
  protected $columns;

  /**
   * Enabled blocks.
   *
   * @var array
   */
  protected $blocks;

  /**
   * List of visibility this homebox.
   *
   * @var array
   */
  protected $options;

  /**
   * {@inheritdoc}
   */
  public function getPath() {
    return $this->path;
  }

  /**
   * {@inheritdoc}
   */
  public function getRoles() {
    return $this->roles;
  }

  /**
   * {@inheritdoc}
   */
  public function getRegions() {
    return $this->columns;
  }

  /**
   * {@inheritdoc}
   */
  public function getBlocks() {
    return $this->blocks;
  }

  /**
   * {@inheritdoc}
   */
  public function setPath($path) {
    $this->path = $path;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setRoles(array $roles) {
    $this->roles = $roles;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setBlocks(array $blocks) {
    $this->blocks = $blocks;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions() {
    return $this->options;
  }

}
