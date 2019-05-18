<?php
/**
 * @file
 * Fasttoggle Node Status
 */

namespace Drupal\fasttoggle\Plugin\Setting;

require_once __DIR__ . '/../../../fasttoggle.inc';

use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Field\FieldItemList;
use Drupal\fasttoggle\Plugin\Field\FieldFormatter\BooleanFormatter;
use Drupal\fasttoggle\Plugin\SettingGroup\NodeCore;
use Drupal\fasttoggle\Plugin\Setting\SettingTrait;
use Drupal\node\Plugin\views\filter\Access;

/**
 * Abstract interface for settings. Plugin strings are used for quick
 * filtering without the need to instantiate the class.
 *
 * @Plugin(
 *   entityType = "node",
 *   name = "status",
 *   description = "Status <small>(published/unpublished)</small>",
 *   id = "node_status",
 *   group = "node_core",
 *   weight = 0,
 *   default = false,
 *   base_formatter = "Drupal\fasttoggle\Plugin\Field\FieldFormatter\BooleanFormatter",
 *   attributes = {
 *    "status" = "Status",
 *   },
 *   labels = {
 *     FASTTOGGLE_LABEL_ACTION = {
 *       0 = @Translation("publish"),
 *       1 = @Translation("unpublish"),
 *     },
 *     FASTTOGGLE_LABEL_STATUS = {
 *       0 = @Translation("not published"),
 *       1 = @Translation("published"),
 *     },
 *   },
 * )
 */
class NodeStatus extends NodeCore implements SettingInterface {

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

    $config = \Drupal::config('fasttoggle.settings');
    $sitewide = $config->get('node_core_status');

    $result = AccessResult::allowedIf($sitewide)
      ->addCacheableDependency($config)
      ->andIf(AccessResult::allowedIfHasPermission($user, [
        "override {$this->object->getType()} published option",
        'moderate posts'
      ], 'OR'));

    return $result;
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
    return ($entity == 'node' && $definition->getName() == 'status');
  }

  /**
   * Retrieve the current value of the setting.
   *
   * @return string
   *   The current key matching getHumanReadableValueList / getValueList.
   */
  function get_value($instance = '') {
    return $this->object->get('status')->value;
  }

  /**
   * Retrieve the current value of the setting.
   *
   * @return string
   *   The current key matching getHumanReadableValueList / getValueList.
   */
  function set_value($instance = '', $newValue) {
    return $this->object->set('status', $newValue);
  }

}
