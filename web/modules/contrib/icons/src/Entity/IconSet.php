<?php

namespace Drupal\icons\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\icons\IconLibraryPluginCollection;

/**
 * Defines the Icon Set entity.
 *
 * @ConfigEntityType(
 *   id = "icon_set",
 *   label = @Translation("Icon Set"),
 *   handlers = {
 *     "list_builder" = "Drupal\icons\IconSetListBuilder",
 *     "form" = {
 *       "add" = "Drupal\icons\Form\IconSetForm",
 *       "edit" = "Drupal\icons\Form\IconSetForm",
 *       "delete" = "Drupal\icons\Form\IconSetDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\icons\IconSetHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "icon_set",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/appearance/icon_set/{icon_set}",
 *     "add-form" = "/admin/appearance/icon_set/add",
 *     "edit-form" = "/admin/appearance/icon_set/{icon_set}/edit",
 *     "delete-form" = "/admin/appearance/icon_set/{icon_set}/delete",
 *     "collection" = "/admin/appearance/icon_set"
 *   }
 * )
 */
class IconSet extends ConfigEntityBase implements IconSetInterface {

  /**
   * The Icon Set ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Icon Set label.
   *
   * @var string
   */
  protected $label;

  /**
   * The plugin instance settings.
   *
   * @var array
   */
  protected $settings = array();

  /**
   * The plugin instance ID.
   *
   * @var string
   */
  protected $plugin;

  /**
   * The plugin collection that holds the icon library plugin for this entity.
   *
   * @var \Drupal\icons\IconLibraryPluginCollection
   */
  protected $pluginCollection;

  /**
   * {@inheritdoc}
   */
  public function getPlugin() {
    if (isset($this->plugin)) {
      return $this->getPluginCollection()->get($this->plugin);
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollection() {
    if (!$this->pluginCollection) {
      $this->pluginCollection = new IconLibraryPluginCollection(\Drupal::service('plugin.manager.icon_library'), $this->plugin, $this->get('settings'), $this->id());
    }
    return $this->pluginCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    return $this->plugin;
  }

}
