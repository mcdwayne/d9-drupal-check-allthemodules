<?php

namespace Drupal\acsf\Event;

use Drupal\acsf\AcsfSite;
use Drupal\file\Entity\File;

/**
 * Handles initialization operations for the scrub.
 */
class AcsfDuplicationScrubInitializeHandler extends AcsfEventHandler {

  /**
   * Implements AcsfEventHandler::handle().
   */
  public function handle() {
    $this->consoleLog(dt('Entered @class', ['@class' => get_class($this)]));
    if (!$this->isComplete()) {
      $site = AcsfSite::load();
      $site->clean();

      \Drupal::config('system.site')
        ->set('name', $this->event->context['site_name'])
        ->save();
      $state_storage = \Drupal::state();
      $state_storage->delete('acsf_duplication_scrub_status');
      $state_storage->set('install_time', time());

      // As a preparatory step, remove any corrupt file entries that may prevent
      // duplication from succeeding. Specifically, remove any file with an
      // empty URI string.
      $fids = \Drupal::entityQuery('file')
        ->condition('uri', '')
        ->execute();
      $files = File::loadMultiple($fids);
      foreach ($files as $file) {
        try {
          $file->delete();
        }
        catch (\Exception $e) {
          // OK, we'll live with not scrubbing this.
        }
      }

      $this->setComplete();
    }
  }

  /**
   * Returns if this step has already completed.
   */
  public function isComplete() {
    return \Drupal::service('acsf.variable_storage')
      ->get('acsf_site_duplication_step_initialize_complete', FALSE);
  }

  /**
   * Sets a flag to indicate that this step has completed.
   */
  protected function setComplete() {
    \Drupal::service('acsf.variable_storage')
      ->set('acsf_site_duplication_step_initialize_complete', TRUE, 'acsf_duplication_scrub');
  }

}
