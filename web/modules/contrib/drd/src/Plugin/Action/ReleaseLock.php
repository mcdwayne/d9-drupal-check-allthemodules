<?php

namespace Drupal\drd\Plugin\Action;

use Drupal\drd\Entity\Release;

/**
 * Provides a 'ReleaseLock' action.
 *
 * @Action(
 *  id = "drd_action_release_lock",
 *  label = @Translation("Lock a project release"),
 *  type = "drd",
 * )
 */
class ReleaseLock extends BaseGlobal {

  protected $lock = TRUE;
  protected $function = 'lockRelease';

  /**
   * {@inheritdoc}
   */
  public function executeAction() {
    $release = Release::find($this->arguments['projectName'], $this->arguments['version']);
    if (!$release) {
      $this->log('error', 'Release not found.');
      return FALSE;
    }

    if (is_null($this->arguments['cores'])) {
      $release
        ->setLocked($this->lock)
        ->save();
      return TRUE;
    }
    elseif (empty($this->arguments['cores'])) {
      $this->log('warning', 'No core entity found.');
      return FALSE;
    }

    /** @var \Drupal\drd\Entity\CoreInterface $core */
    foreach ($this->arguments['cores'] as $core) {
      $core
        ->{$this->function}($release)
        ->save();
    }
    return TRUE;
  }

}
