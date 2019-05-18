<?php
/**
 * @file
 * Contains \Drupal\jvector\Entity\Jvector.
 */

namespace Drupal\jvector\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\jvector\JvectorInterface;
use Drupal\Core\RouteProcessor;

/**
 * Defines the Jvector entity.
 *
 * @ConfigEntityType(
 *   id = "jvector",
 *   label = @Translation("Jvector"),
 *   handlers = {
 *     "list_builder" = "Drupal\jvector\Controller\JvectorListBuilder",
 *     "form" = {
 *       "add" = "Drupal\jvector\Form\JvectorForm",
 *       "edit" = "Drupal\jvector\Form\JvectorForm",
 *       "delete" = "Drupal\jvector\Form\JvectorDeleteForm",
 *       "values" = "Drupal\jvector\Form\JvectorValuesForm",
 *       "view" = "Drupal\jvector\Form\JvectorViewForm",
 *       "config_add" = "Drupal\jvector\Form\JvectorConfigAddForm",
 *       "config_edit" = "Drupal\jvector\Form\JvectorConfigForm",
 *       "config_delete" = "Drupal\jvector\Form\JvectorConfigDeleteForm"
 *     }
 *   },
 *   config_prefix = "jvector.type",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/system/jvector/{jvector}/edit",
 *     "values-form" = "/admin/config/system/jvector/{jvector}/values",
 *     "delete-form" = "/admin/config/system/jvector/{jvector}/delete",
 *     "view-form" = "/admin/config/system/jvector/{jvector}",
 *     "color-add-form" = "/admin/config/system/jvector/{jvector}/config/add"
 *   }
 * )
 */
class Jvector extends ConfigEntityBase implements JvectorInterface {

  /**
   * The Jvector ID.
   *
   * @var string
   */
  public $id;

  /**
   * The Jvector label.
   *
   * @var string
   */
  public $label;
  /**
   * The Paths value.
   *
   * @var string
   */
  public $paths;

  /**
   * Get path configuration. Contains name, id & path.
   * @return string
   */
  public function getPaths() {
    return $this->paths;
  }

  /**
   * Set path configuration.
   * @return string
   */
  public function setPaths($new_paths) {
    $this->paths = $new_paths;
  }

  /**
   * Retrieve a default path list.
   * This is formatted as a allowed values list, separated by |.
   *
   * @return string
   * A text string with allowed values.
   */
  public function getJvectorAllowedList() {
    $paths = $this->getPaths();
    $text = "";
    foreach ($paths AS $path_id => $path) {
      $text .= $path_id . "|" . $path['name'];
      end($paths);
      $text .= ($path_id === key($paths)) ? "" : "\n";
    }

    return $text;
  }

  /**
   * Retrieve all custom configurations, including the default set.
   * @return mixed
   */
  public function getJvectorConfigSets() {
    return $this->customconfig;
  }

  /**
   * Retrieve single config set
   * @param $id
   * The config set ID.
   *
   * @return mixed
   * Config set, or false if not found.
   *
   */
  public function getJvectorConfigSet($id) {
    if (isset($this->customconfig[$id]) && !empty($this->customconfig[$id])) {
      return $this->customconfig[$id];
    }
    return FALSE;
  }

  /**
   * Retrieve config set list as values for a select field.
   * @return array
   */
  public function getJvectorConfigSetsAsSelect() {
    $select = array();
    foreach ($this->customconfig AS $config_id => $config) {
      $select[$config_id] = $config['label'];
    }
    return $select;
  }

  /**
   * Retrieve jvector paths (regions) as a select field.
   * Fills the #options values for admin control fields.
   *
   * @return array
   * Array of path names keyed by path ID.
   */
  public function getJvectorPathsAsSelect() {
    $select = array();
    foreach ($this->paths AS $config_id => $config) {
      $select[$config_id] = $config['name'];
    }
    return $select;
  }

  /**
   * Test for existing config
   * @todo Consider deprecating this.
   */
  public function loadConfig($config_id) {
    $entity = \Drupal::routeMatch()->getParameter('jvector');
    if (!isset($entity->customconfig[$config_id])) {
      return FALSE;
    }
    return TRUE;
  }

  //@todo Move to configuration.
  public function custom_defaults() {
    $config = \Drupal::config('jvector.default')->get();
    $defaults = array(
      'default' => $config,
    );
    return $defaults;
  }
    public function custom_path_config(){
     return \Drupal::config('jvector.default_region')->get();
    }
}

