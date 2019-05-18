<?php

namespace Drupal\mass_contact\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Mass contact categories.
 */
interface MassContactCategoryInterface extends ConfigEntityInterface {

  /**
   * Gets the recipient category plugin definitions.
   *
   * @return \Drupal\mass_contact\Plugin\MassContact\GroupingMethod\GroupingInterface[]
   *   An array of configured selection plugins, keyed by plugin ID.
   */
  public function getGroupings();

  /**
   * Sets grouping definitions.
   *
   * @param \Drupal\mass_contact\Plugin\MassContact\GroupingMethod\GroupingInterface[] $groupings
   *   The grouping configurations, keyed by plugin ID.
   */
  public function setGroupings(array $groupings);

  /**
   * Gets grouping categories for a given plugin.
   *
   * @param string $grouping_id
   *   The grouping plugin ID.
   *
   * @return \Drupal\mass_contact\Plugin\MassContact\GroupingMethod\GroupingInterface
   *   A grouping category plugin instance.
   */
  public function getGroupingCategories($grouping_id);

  /**
   * Gets the recipients data.
   *
   * @return string[]
   *   An array of raw recipients configuration keyed by plugin ID. Use the
   *   `::getGroupings()` method to get the actual plugin with this
   *   configuration.
   */
  public function getRecipients();

  /**
   * Sets the recipients data.
   *
   * @param array $recipients
   *   The recipients data, keyed by plugin ID.
   */
  public function setRecipients(array $recipients);

  /**
   * Determines if this category should be selected by default on mass contacts.
   *
   * @return bool
   *   Returns TRUE if the category should be selected by default.
   */
  public function getSelected();

  /**
   * Sets category to be selected by default.
   *
   * @param bool $selected
   *   Set to TRUE if the category should be selected by default.
   */
  public function setSelected($selected);

}
