<?php
/**
 * @file
 * Fasttoggle Node Sticky
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
 *   name = "sticky",
 *   description = "Sticky <small>(sticky/not sticky)</small>",
 *   id = "node_sticky",
 *   group = "node_core",
 *   weight = 30,
 *   default = false,
 *   base_formatter = "Drupal\fasttoggle\Plugin\Field\FieldFormatter\BooleanFormatter",
 *   attributes = {
 *    "sticky" = "Sticky",
 *   },
 *   labels = {
 *     FASTTOGGLE_LABEL_ACTION = {
 *       0 = @Translation("make sticky"),
 *       1 = @Translation("remove stickiness"),
 *     },
 *     FASTTOGGLE_LABEL_STATUS = {
 *       0 = @Translation("not sticky"),
 *       1 = @Translation("sticky"),
 *     },
 *   },
 * )
 */
class NodeSticky extends NodeCore implements SettingInterface {

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
    $sitewide = $config->get('node_core_sticky');

    $result = AccessResult::allowedIf($sitewide)
      ->addCacheableDependency($config)
      ->andIf(AccessResult::allowedIfHasPermission($user, [
        "override {$this->object->getType()} sticky option",
        'promote posts'
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
    return ($entity == 'node' && $definition->getName() == 'sticky');
  }

}
