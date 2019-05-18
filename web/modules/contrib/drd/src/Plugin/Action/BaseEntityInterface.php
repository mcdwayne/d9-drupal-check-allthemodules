<?php

namespace Drupal\drd\Plugin\Action;

use Drupal\drd\Entity\BaseInterface as RemoteEntityInterface;

/**
 * Interface for actions that can be executed on remote entities.
 */
interface BaseEntityInterface extends BaseInterface {

  /**
   * Execute the action on the given DRD entity.
   *
   * @param \Drupal\drd\Entity\BaseInterface $entity
   *   The DRD entity on which the action should be executed.
   *
   * @return array|bool
   *   The json decoded response from the remote entity or FALSE, if execution
   *   failed.
   */
  public function executeAction(RemoteEntityInterface $entity);

}
