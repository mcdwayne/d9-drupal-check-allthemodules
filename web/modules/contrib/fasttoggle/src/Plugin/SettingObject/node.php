<?php
/**
 * @file
 * Fasttoggle Managed Node
 */

namespace Drupal\fasttoggle\Plugin\SettingObject;

require_once __DIR__ . "/AbstractSettingObject.php";

use Drupal\Core\Plugin\PluginBase;
use Drupal\fasttoggle\ObjectInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Class for managing nodes.
 *
 * @Plugin(
 *   id = "node",
 *   title = @Translation("Nodes"),
 *   description = @Translation("Select which options for fast toggling of post settings are available."),
 *   weight = 0,
 * )
 */
class node extends AbstractSettingObject implements SettingObjectInterface {

  /**
   * Set the node object.
   */
  public function setObject($node) {
    $this->object = $node;
  }

  /**
   * Object ID.
   *
   * @return integer
   *   The unique ID of this instance of the object.
   */
  public function get_id() {
    return $this->object->nid;
  }

  /**
   * Object title.
   *
   * @return string
   *   The title of the node.
   */
  public function get_title() {
    return $this->object->title;
  }

  /**
   * Save function.
   */
  public function save() {
    $this->object->save();
  }

  /**
   * Object subtype machine name.
   *
   * @return string
   *   A subtype (if any) of the object (eg node type).
   */
  public function get_type() {
    return $this->object->getType();
  }

  /**
   * Matches an object?
   */
  public function objectMatches($object) {
    return ($object instanceof \Drupal\node\Entity\Node);
  }

  /**
   * Access.
   *
   * @param $object
   *   The object for which update access is being checked.
   *
   * @return AccessResult
   *   AccessResult (cacheability info and the outcome).
   */
  public function mayEditEntity() {
    return AccessResult::allowedIf($this->object->access('update'))
      ->addCacheableDependency($this->object);
  }

}
