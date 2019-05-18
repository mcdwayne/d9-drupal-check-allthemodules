<?php
/**
 * @file
 * Fasttoggle Node Promotion
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
 *   name = "promoted",
 *   description = "Promoted <small>(promoted/not promoted)</small>",
 *   id = "node_promoted",
 *   group = "node_core",
 *   weight = 20,
 *   default = false,
 *   base_formatter = "Drupal\fasttoggle\Plugin\Field\FieldFormatter\BooleanFormatter",
 *   attributes = {
 *    "promoted" = "Promoted",
 *   },
 *   labels = {
 *     FASTTOGGLE_LABEL_ACTION = {
 *       0 = @Translation("promote"),
 *       1 = @Translation("demote"),
 *     },
 *     FASTTOGGLE_LABEL_STATUS = {
 *       0 = @Translation("not promoted"),
 *       1 = @Translation("promoted"),
 *     },
 *   },
 * )
 */
class NodePromotion extends NodeCore implements SettingInterface {

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
    $sitewide = $config->get('node_core_promoted');

    $result = AccessResult::allowedIf($sitewide)
      ->addCacheableDependency($config)
      ->andIf(AccessResult::allowedIfHasPermission($user, [
        "override {$this->object->getType()} promoted option",
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
    return ($entity == 'node' && $definition->getName() == 'promote');
  }

}
