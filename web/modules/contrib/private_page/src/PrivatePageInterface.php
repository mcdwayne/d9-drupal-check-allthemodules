<?php

namespace Drupal\private_page;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a Private page.
 *
 * @ingroup private_page
 */
interface PrivatePageInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  /**
   * Get entity private page path.
   * 
   * @return string
   *   Entity private page path.
   * 
   */
  public function getPrivatePagePath();

  /**
   * Get entity permissions.
   * 
   * @return array
   *   Array of entity permissions.
   * 
   */
  public function getPermissions();
}
