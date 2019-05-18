<?php

namespace Drupal\migrate_d2d_ui\Form;

use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Base class for all steps.
 */
abstract class DrupalMigrateForm extends FormBase {

  /**
   * Cached database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $sourceConnection;

  /**
   * Gets the database connection for the source Drupal database.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state containing the database connection info.
   *
   * @return \Drupal\Core\Database\Connection
   *   The database connection for the source Drupal database.
   */
  protected function connection(FormStateInterface $form_state) {
    if (!isset($this->sourceConnection)) {
      $cached_values = $form_state->getTemporaryValue('wizard');
      // Set up the connection.
      Database::addConnectionInfo('drupal_import', 'default', $cached_values['database']);
      $this->sourceConnection = Database::getConnection('default', 'drupal_import');
    }
    return $this->sourceConnection;
  }

}
