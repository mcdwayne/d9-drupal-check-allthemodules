<?php

namespace Drupal\module_builder;

use Drupal\Core\Url;
use DrupalCodeBuilder\Exception\SanityException;

/**
 * Handles exceptions from the library and outputs messages.
 */
class ExceptionHandler {

  /**
   * Handle a sanity exception from the library and output a message.
   *
   * @param DrupalCodeBuilder\Exception\SanityException $e
   *  A sanity exception object.
   */
  public static function handleSanityException(SanityException $e) {
    $failed_sanity_level = $e->getFailedSanityLevel();
    switch ($failed_sanity_level) {
      case 'data_directory_exists':
        drupal_set_message(t("The component data directory could not be created or is not writable."), 'error');
        break;
      case 'component_data_processed':
        drupal_set_message(t("No component data is present. Go to the <a href=\":url\">'Analyse code' page</a> to collect data about your codebase's Drupal components.", [
          ':url' => Url::fromRoute('module_builder.analyse')->toString(),
        ]), 'error');
        break;
    }
  }

}
