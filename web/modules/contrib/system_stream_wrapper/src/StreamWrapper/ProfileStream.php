<?php

namespace Drupal\system_stream_wrapper\StreamWrapper;

/**
 * Defines the read-only profile:// stream wrapper for installed profile files.
 */
class ProfileStream extends ModuleStream {

  use LocalStreamTrait;

  /**
   * {@inheritdoc}
   */
  protected function getOwnerName() {
    return drupal_get_profile();
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->t('Installed profile files');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Local files stored under installed profile directory.');
  }

}
