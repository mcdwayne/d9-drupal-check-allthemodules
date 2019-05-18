<?php

namespace Drupal\bueditor\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines the BUEditor Editor entity.
 *
 * @ConfigEntityType(
 *   id = "bueditor_editor",
 *   label = @Translation("BUEditor Editor"),
 *   handlers = {
 *     "list_builder" = "Drupal\bueditor\BUEditorEditorListBuilder",
 *     "form" = {
 *       "add" = "Drupal\bueditor\Form\BUEditorEditorForm",
 *       "edit" = "Drupal\bueditor\Form\BUEditorEditorForm",
 *       "delete" = "Drupal\bueditor\Form\BUEditorEditorDeleteForm",
 *       "duplicate" = "Drupal\bueditor\Form\BUEditorEditorForm"
 *     }
 *   },
 *   admin_permission = "administer bueditor",
 *   config_prefix = "editor",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/content/bueditor/{bueditor_editor}",
 *     "delete-form" = "/admin/config/content/bueditor/{bueditor_editor}/delete",
 *     "duplicate-form" = "/admin/config/content/bueditor/{bueditor_editor}/duplicate"
 *   }
 * )
 */
class BUEditorEditor extends ConfigEntityBase {

  /**
   * Editor ID.
   *
   * @var string
   */
  protected $id;

  /**
   * Label.
   *
   * @var string
   */
  protected $label;

  /**
   * Description.
   *
   * @var string
   */
  protected $description;

  /**
   * Settings.
   *
   * @var array
   */
  protected $settings = [];

  /**
   * Javascript data including settings and libraries.
   *
   * @var array
   */
  protected $js;

  /**
   * Returns all settings or a specific setting by key.
   */
  public function getSettings($key = NULL, $default = NULL) {
    $settings = $this->settings;
    if (isset($key)) {
      return isset($settings[$key]) ? $settings[$key] : $default;
    }
    return $settings;
  }

  /**
   * Sets the toolbar array.
   */
  public function setToolbar(array $toolbar) {
    return $this->settings['toolbar'] = $toolbar;
  }

  /**
   * Returns the toolbar array.
   */
  public function getToolbar() {
    return $this->getSettings('toolbar', []);
  }

  /**
   * Checks if an item exists in the toolbar.
   */
  public function hasToolbarItem($id) {
    return in_array($id, $this->getToolbar(), TRUE);
  }

  /**
   * Returns JS libraries.
   */
  public function getLibraries(Editor $editor = NULL) {
    $js = $this->getJS($editor);
    return $js['libraries'];
  }

  /**
   * Returns JS settings.
   */
  public function getJSSettings(Editor $editor = NULL) {
    $js = $this->getJS($editor);
    return $js['settings'];
  }

  /**
   * Returns JS data including settings and libraries.
   */
  public function getJS(Editor $editor = NULL) {
    if (!isset($this->js)) {
      $this->js = [
        'libraries' => ['bueditor/drupal.bueditor'],
        'settings' => array_filter($this->getSettings()) + ['toolbar' => []],
      ];
      \Drupal::service('plugin.manager.bueditor.plugin')->alterEditorJS($this->js, $this, $editor);
    }
    return $this->js;
  }

}
