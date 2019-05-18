<?php

namespace Drupal\consent\Storage;

use Drupal\consent\ConsentInterface;

/**
 * Interface for consent storages.
 */
interface ConsentStorageInterface {

  /**
   * Saves the given consent.
   *
   * @param \Drupal\consent\ConsentInterface $consent
   *   The consent to save.
   *
   * @throws \Drupal\consent\Storage\ConsentStorageException
   *   When something went wrong.
   */
  public function save(ConsentInterface $consent);

}
