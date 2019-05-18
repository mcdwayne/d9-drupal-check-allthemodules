<?php

/**
 * @file
 * Definition of Drupal\docker\Plugin\views\wizard\DockerApp.
 */

namespace Drupal\docker\Plugin\views\wizard;

use Drupal\views\Plugin\views\wizard\WizardPluginBase;
use Drupal\views\Annotation\ViewsWizard;
use Drupal\Core\Annotation\Translation;

/**
 * Defines a wizard for the watchdog table.
 *
 * @ViewsWizard(
 *   id = "docker_apps",
 *   module = "docker",
 *   base_table = "docker_apps",
 *   title = @Translation("Docker apps")
 * )
 */
class DockerApp extends WizardPluginBase {

  /**
   * Set the created column.
   */
  protected $createdColumn = 'created';
}
