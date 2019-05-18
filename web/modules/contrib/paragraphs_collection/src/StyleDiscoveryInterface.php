<?php

namespace Drupal\paragraphs_collection;

use Drupal\Core\Session\AccountProxyInterface;

/**
 * Provides discovery for a YAML style files in specific directories.
 *
 * @package Drupal\paragraphs_collection
 */
interface StyleDiscoveryInterface {

  /**
   * Gets libraries for a specific style or empty list if style is not found.
   *
   * @param $style
   *   The name of the style.
   * @return array
   *   The names of the libraries, or empty list if not found.
   */
  public function getLibraries($style);

  /**
   * Gets sorted style titles keyed by their names belonging to the given group.
   *
   * If an empty string is given, returns all styles.
   *
   * @param string $group
   *   (optional) The style group. Defaults to empty string.
   * @param bool $access_check
   *   (optional) Whether we should check the style access. Defaults to false.
   *
   * @return array
   *   An array of style titles keyed by the respective style machine names.
   */
  public function getStyleOptions($group = '', $access_check = FALSE);

  /**
   * Gets style groups.
   *
   * @return array
   *    Collection of style groups.
   */
  public function getStyleGroups();

  /**
   * Gets style groups label.
   *
   * @return array
   *    Collection of style groups labels.
   */
  public function getStyleGroupsLabel();

  /**
   * Returns associative array of name and definition of style.
   *
   * @return array
   *   Collection of styles.
   */
  public function getStyles();

  /**
   * Get style by name.
   *
   * @param string $style
   *   The style name.
   * @param string|null $default
   *   (optional) The default style if the specified style does not exist.
   *
   * @return array|null
   *   The style configuration array of the given style or NULL.
   */
  public function getStyle($style, $default = NULL);

  /**
   * Checks whether the given account has access to the given style name.
   *
   * @param array $style_definition
   *   The style definition.
   * @param \Drupal\Core\Session\AccountProxyInterface|null $account
   *   (optional) The account to check access for. Defaults to current user.
   *
   * @return bool
   *   TRUE if the user has access to the style. Otherwise, FALSE.
   */
  public function isAllowedAccess(array $style_definition, AccountProxyInterface $account = NULL);

  /**
   * Gets group label.
   *
   * @param string $group_id
   *   The group id.
   *
   * @return string
   *   The translatable group label.
   */
  public function getGroupLabel($group_id);

  /**
   * Gets group label for a widget context.
   *
   * @param string $group_id
   *   The group id.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The translatable group label.
   */
  public function getGroupWidgetLabel($group_id);

  /**
   * Reset the cached definitions.
   */
  public function reset();

}
