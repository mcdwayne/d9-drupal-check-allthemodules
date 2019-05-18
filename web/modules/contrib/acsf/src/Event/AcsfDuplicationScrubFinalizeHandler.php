<?php

namespace Drupal\acsf\Event;

/**
 * Handles final operations for the scrub.
 */
class AcsfDuplicationScrubFinalizeHandler extends AcsfEventHandler {

  /**
   * Implements AcsfEventHandler::handle().
   */
  public function handle() {
    $this->consoleLog(dt('Entered @class', ['@class' => get_class($this)]));

    // Clear the caches to ensure that the registries and other structural data
    // is rebuilt.
    drupal_flush_all_caches();

    // Clean up expirable key-value stores (form cache)
    $bins = [
      'form',
      'form_state',
    ];
    foreach ($bins as $bin) {
      \Drupal::keyValueExpirable($bin)->deleteAll();
    }

    // Clean up ACSF variables.
    /** @var \Drupal\acsf\AcsfVariableStorage $storage */
    $storage = \Drupal::service('acsf.variable_storage');
    $acsf_variables = $storage->getGroup('acsf_duplication_scrub');
    foreach ($acsf_variables as $name => $value) {
      $storage->delete($name);
    }

    // Begin the site without any watchdog records. This should happen right at
    // the end of the scrubbing process to remove any log entries added by the
    // scrubbing process itself.
    try {
      \Drupal::database()->truncate('watchdog')->execute();
    }
    catch (\Exception $e) {
      // OK, we'll live with not scrubbing this.
    }

    // Mark the entire scrubbing process as complete.
    \Drupal::state()->set('acsf_duplication_scrub_status', 'complete');
  }

}
