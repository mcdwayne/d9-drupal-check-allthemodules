<?php

namespace Drupal\hidden_tab\Entity\Helper;

/**
 * Implements StatusedEntityInterface.
 *
 * @see \Drupal\hidden_tab\Entity\Base\StatusedEntityInterface
 */
trait StatusedEntityTrait {

  /**
   * See isEnabled() in StatusedEntityInterface
   *
   * @return bool
   *   See isEnabled() in StatusedEntityInterface
   *
   * @see \Drupal\hidden_tab\Entity\Base\StatusedEntityInterface
   */
  public function isEnabled(): bool {
    // FIXME
    return TRUE;
    //    return $this->get('status');
  }

  /**
   * See enable() in StatusedEntityInterface
   *
   * @see \Drupal\hidden_tab\Entity\Base\StatusedEntityInterface
   */
  public function enable() {
    return $this->set('status', TRUE);
  }

  /**
   * See disable() in StatusedEntityInterface
   *
   * @see \Drupal\hidden_tab\Entity\Base\StatusedEntityInterface
   */
  public function disable() {
    return $this->set('status', FALSE);
  }

}
