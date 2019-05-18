<?php

namespace Drupal\business_rules;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Class BusinessRuleItemInterface.
 *
 * @package Drupal\business_rules
 */
interface ItemInterface extends ConfigEntityInterface {

  /**
   * Load all Business Rule's tags.
   *
   * @return array
   *   Array of tags.
   */
  public static function loadAllTags();

  /**
   * Load multiple items by type.
   *
   * @param string $type
   *   The item type. i.e. The plugin id.
   * @param array|null $ids
   *   The items ids.
   *
   * @return array
   *   Array with the matched items.
   */
  public static function loadMultipleByType($type, array $ids = NULL);

  /**
   * Get the Item type translated. Action|Condition.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The translates item type label.
   */
  public function getBusinessRuleItemTranslatedType();

  /**
   * Get the Item type: action|condition.
   *
   * @return string
   *   The item type: action|condition.
   */
  public function getBusinessRuleItemType();

  /**
   * Get the item description.
   *
   * @return string
   *   The item description.
   */
  public function getDescription();

  /**
   * Get the reactsOn ids applicable for the item.
   *
   * @return array
   *   Context: Array of reactsOn ids applicable for the item.
   */
  public function getReactOnEvents();

  /**
   * Return the item settings.
   *
   * @param string $settingId
   *   the settings ID for the config entity.
   *
   * @return array|string
   *   The item settings.
   */
  public function getSettings($settingId = '');

  /**
   * Get the tags value.
   *
   * @return array
   *   The tags value.
   */
  public function getTags();

  /**
   * Return the target entity bundle id which this item is applicable.
   *
   * @return string
   *   The target entity bundle id which this item is applicable.
   */
  public function getTargetBundle();

  /**
   * Return the entity type id which this item is applicable.
   *
   * @return string
   *   The entity type id which this item is applicable.
   */
  public function getTargetEntityType();

  /**
   * Get the item type.
   *
   * @return string
   *   The item type
   */
  public function getType();

  /**
   * Get the readable Type label.
   *
   * @return string
   *   The readable Type label.
   */
  public function getTypeLabel();

  /**
   * Get all item types available.
   *
   * @return array
   *   All item types available
   */
  public function getTypes();

  /**
   * Get the variables being used by the item.
   *
   * @return \Drupal\business_rules\VariablesSet
   *   The variables being used by the item.
   */
  public function getVariables();

  /**
   * Is the item context dependent?
   *
   * @return bool
   *   True if the item is context dependent, false if not.
   */
  public function isContextDependent();

  /**
   * Get the item label.
   *
   * @return string
   *   The item label.
   */
  public function label();

  /**
   * Set a value to the Item Settings.
   *
   * @param string $settingId
   *   The setting id.
   * @param mixed $value
   *   The value to be set on Item settings.
   *
   * @throws \Exception
   */
  public function setSetting($settingId, $value);

  /**
   * Set the tags value.
   *
   * @param array $tags
   *   The tags.
   */
  public function setTags(array $tags);

}
