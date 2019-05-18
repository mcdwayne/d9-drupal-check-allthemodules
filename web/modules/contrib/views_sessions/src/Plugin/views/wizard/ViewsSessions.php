<?php

namespace Drupal\views_sessions\Plugin\views\wizard;

use Drupal\views\Plugin\views\wizard\WizardPluginBase;

/**
 * Defines a wizard for the watchdog table.
 *
 * @ViewsWizard(
 *   id = "sessions",
 *   module = "views_sessions",
 *   base_table = "sessions",
 *   title = @Translation("Active sessions")
 * )
 */
class Sessions extends WizardPluginBase {

  /**
   * Set the created column.
   *
   * @var string
   */
  protected $createdColumn = 'timestamp';

  /**
   * {@inheritdoc}
   */
  protected function defaultDisplayOptions() {
    $display_options = parent::defaultDisplayOptions();

    // Add permission-based access control.
    $display_options['access']['type'] = 'perm';
    $display_options['access']['options']['perm'] = 'administer users';

    return $display_options;
  }

}
