<?php

namespace Drupal\drd\Plugin\Action;

/**
 * Provides a 'ReleaseUnlock' action.
 *
 * @Action(
 *  id = "drd_action_release_unlock",
 *  label = @Translation("Unlock a project release"),
 *  type = "drd",
 * )
 */
class ReleaseUnlock extends ReleaseLock {

  protected $lock = FALSE;
  protected $function = 'unlockRelease';

}
