<?php
/**
 * @file
 * Fasttoggle Node Promoted
 */

namespace Drupal\fasttoggle\Plugin\Setting;

require_once __DIR__ . '/../../../fasttoggle.inc';

use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Field\FieldItemList;
use Drupal\fasttoggle\Plugin\Field\FieldFormatter\BooleanFormatter;
use Drupal\fasttoggle\Plugin\SettingGroup\NodeCore;
use Drupal\fasttoggle\Plugin\Setting\SettingTrait;

/**
 * Abstract interface for settings. Plugin strings are used for quick
 * filtering without the need to instantiate the class.
 *
 * @Plugin(
 *   entityType = "node",
 *   name = "comments",
 *   description = "Comments <small>(full access/read only/disabled)</small>",
 *   id = "node_comments",
 *   group = "node_core",
 *   weight = 10,
 *   default = false,
 *   base_formatterbase_formatter = "Drupal\fasttoggle\Plugin\Field\FieldFormatter\BooleanFormatter",
 *   attributes = {
 *    "comments" = "Commenting",
 *   },
 *   labels = {
 *     FASTTOGGLE_LABEL_ACTION = {
 *       0 = @Translation("enable"),
 *       1 = @Translation("make read-only"),
 *       2 = @Translation("disable"),
 *     },
 *     FASTTOGGLE_LABEL_STATUS = {
 *       0 = @Translation("read/write"),
 *       1 = @Translation("read only"),
 *       2 = @Translation("disabled"),
 *     },
 *   },
 * )
 */
class NodeComments extends NodeCore implements SettingInterface {

  use SettingTrait;

  /**
   * Access control function.
   *
   * @param $node
   *   The node against which to check (un)publish permission.
   *
   * @return boolean
   *   Whether the user is allowed to (un)publish the node.
   */
  public function mayEditSetting() {
    $user = \Drupal::currentUser();
    return AccessResult::allowedIfHasPermission($user,"override {$this->object->getType()} comment option")
      ->orIf(AccessResult::allowedIfHasPermission($user,'administer comments'));
  }

  /**
   * Return whether this setting matches the provided field definition.
   *
   * @param $definition
   *   The field definition for which a match is being sought.
   *
   * @return boolean
   *   Whether this plugin handles the definition.
   */
  public static function matches($definition) {
    $has_get = is_callable(array($definition, 'get'));
    $entity = $has_get ? $definition->get('entity_type') : $definition->getProvider();
    return ($entity == 'node' && $definition->getName() == 'comments');
  }

}
