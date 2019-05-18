<?php

namespace Drupal\paragraphs_type_help;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\Display\EntityDisplayInterface;

/**
 * Provides an interface defining a paragraphs type help entity.
 */
interface ParagraphsTypeHelpInterface extends ContentEntityInterface, EntityChangedInterface, EntityPublishedInterface {

  /**
   * Get the paragraphs type object that this help belongs to.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The paragraphs type entity.
   */
  public function getHostBundle();

  /**
   * Get the paragraphs type id that this help belongs to.
   *
   * @return string
   *   The id of the paragraphs type.
   */
  public function getHostBundleId();

  /**
   * Get the paragraphs type label that this help belongs to.
   *
   * @return string
   *   The label of the paragraphs type.
   */
  public function getHostBundleLabel();

  /**
   * Sets the label of the help.
   *
   * @param int $label
   *   The help's label.
   *
   * @return $this
   */
  public function setLabel($label);

  /**
   * Gets the weight of this help.
   *
   * @return int
   *   The weight of the help.
   */
  public function getWeight();

  /**
   * Gets the weight of this help.
   *
   * @param int $weight
   *   The term's weight.
   *
   * @return $this
   */
  public function setWeight($weight);

  /**
   * Gets the machine name of the host view mode.
   *
   * @return string
   *   The host view mode name.
   */
  public function getHostViewMode();

  /**
   * Gets the human-readable label of the host view mode.
   *
   * @return string
   *   The host view mode label.
   */
  public function getHostViewModeLabel();

  /**
   * Gets the machine name of the host form mode.
   *
   * @return string
   *   The host form mode name.
   */
  public function getHostFormMode();

  /**
   * Gets the human-readable label of the host form mode.
   *
   * @return string
   *   The host form mode label.
   */
  public function getHostFormModeLabel();

  /**
   * Load all published helps for the provided host bundle info.
   *
   * @param string $host_bundle
   *   The bundle name of the host entity type (paragraph).
   * @param string $host_form_mode
   *   The form mode name of the host form display. Example: 'default'.
   * @param string $host_view_mode
   *   The view mode name of the host view display. Example: 'preview'.
   *
   * @return array
   *   An array of loaded paragraphs_type_help entities.
   */
  public static function loadPublishedByHostBundle($host_bundle, $host_form_mode = NULL, $host_view_mode = NULL);

  /**
   * Load all published helps for the provided host display.
   *
   * If there are no paragraphs_type_help entities for the host display mode,
   * then paragraphs_type_help entities with the 'default' mode are queried as
   * a fallback.
   *
   * @param \Drupal\Core\Entity\Display\EntityDisplayInterface $host_display
   *   The host display object.
   *
   * @return array
   *   An array of loaded paragraphs_type_help entities.
   */
  public static function loadPublishedByHostDisplay(EntityDisplayInterface $host_display);

}
