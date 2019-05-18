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
use Drupal\Core\Form\OptGroup;
use Drupal\fasttoggle\Plugin\SettingGroup\NodeCore;
use Drupal\fasttoggle\Plugin\Setting\SettingTrait;

/**
 * Abstract interface for settings. Plugin strings are used for quick
 * filtering without the need to instantiate the class.
 *
 * @Plugin(
 *   entityType = "node",
 *   name = "node_list_field",
 *   description = "List field",
 *   id = "node_field_list",
 *   group = "node_core",
 *   weight = 40,
 *   default = false,
 *   base_formatter = "Drupal\fasttoggle\Plugin\Field\FieldFormatter\OptionsDefaultFormatter",
 *   labels = {
 *     FASTTOGGLE_LABEL_ACTION = {
 *       0 = @Translation("apply attribute"),
 *       1 = @Translation("remove attribute"),
 *     },
 *     FASTTOGGLE_LABEL_STATUS = {
 *       0 = @Translation("not attribute"),
 *       1 = @Translation("attribute"),
 *     },
 *   },
 * )
 */
class NodeListField extends NodeCore implements SettingInterface {

  use SettingTrait;

  /**
   * Access control function.
   *
   * @param $node
   *   The node against which to check permission.
   *
   * @return boolean
   *   Whether the user is allowed to modify the node.
   */
  public function mayEditSetting() {
    $user = \Drupal::currentUser();
    //@TODO Needs more tests?
    return AccessResult::allowedIfHasPermission($user,'edit any article content');
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
    $matching_field_types = [
        'list_integer',
        'list_string',
      ];
    $does_match = ($entity == 'node' && in_array($definition->getType(), $matching_field_types));
    return $does_match;
  }

  /**
   * Get a list of actual values for the setting, in the order used.
   *
   * Keys should match those returned for the list of human readable labels.
   *
   * @return array
   *   An array of the actual values for the field, with keys matching those
   *   returned by getHumanReadableValueList.
   */
  public function getValueList() {
    $provider = $this->field
      ->getFieldStorageDefinition()
      ->getOptionsProvider('value', $this->object);
    // Flatten the possible options, to support opt groups.
    return OptGroup::flattenOptions($provider->getPossibleOptions());
  }

  public function get_attributes() {
    return [ ];
  }

  public function getDefault() {
    $options = $this->getValueList();
    return array_shift(array_values($options));
  }
}
