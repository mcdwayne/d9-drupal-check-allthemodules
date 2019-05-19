<?php

namespace Drupal\whitelabel;

/**
 * Provides an active white label object.
 *
 * If there is no current white label session active, NULL is returned.
 */
interface WhiteLabelProviderInterface {

  /**
   * Returns the white label associated with this session.
   *
   * @return \Drupal\whitelabel\Entity\WhiteLabel
   *   The loaded white label entity.
   */
  public function getWhiteLabel();

  /**
   * Returns the id of the white label associated with this session.
   *
   * @return int
   *   The id of a white label.
   */
  public function getWhiteLabelId();

  /**
   * Sets a new white label entity for this session.
   *
   * @param \Drupal\whitelabel\WhiteLabelInterface $white_label
   *   The white label object to set.
   */
  public function setWhiteLabel(WhiteLabelInterface $white_label);

  /**
   * Resets a white label entity from the session.
   */
  public function resetWhiteLabel();

}
