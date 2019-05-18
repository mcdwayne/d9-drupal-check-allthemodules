<?php

/**
 * @file
 * Definition of Drupal\docker\Plugin\views\wizard\File.
 */

namespace Drupal\docker\Plugin\views\wizard;

use Drupal\views\Plugin\views\wizard\WizardPluginBase;
use Drupal\views\Annotation\ViewsWizard;
use Drupal\Core\Annotation\Translation;

/**
 * Defines a wizard for the watchdog table.
 *
 * @ViewsWizard(
 *   id = "docker_host",
 *   module = "docker",
 *   base_table = "docker_host",
 *   title = @Translation("Docker host")
 * )
 */
class DockerHost extends WizardPluginBase {

  /**
   * Set the created column.
   */
  protected $createdColumn = 'created';

  /**
   * Set default values for the path field options.
   */
  protected $pathField = array(
    'id' => 'dhid',
    'table' => 'docker_host',
    'field' => 'dhid',
    'exclude' => TRUE,
    'link_to_comment' => FALSE,
    'alter' => array(
      'alter_text' => TRUE,
      'text' => 'docker/host/[dhid]'
    ),
  );

  /**
   * Set default values for the filters.
   */
  protected $filters = array(
    'status' => array(
      'value' => TRUE,
      'table' => 'docker_host',
      'field' => 'status',
      'provider' => 'docker'
    ),
    'host' => array(
      'value' => TRUE,
      'table' => 'docker_host',
      'field' => 'host',
      'provider' => 'docker',
    )
  );

  /**
   * Overrides Drupal\views\Plugin\views\wizard\WizardPluginBase::defaultDisplayOptions().
   */
  protected function defaultDisplayOptions() {
    $display_options = parent::defaultDisplayOptions();

    // Add permission-based access control.
    $display_options['access']['type'] = 'perm';

    // Remove the default fields, since we are customizing them here.
    unset($display_options['fields']);

    /* Field: Comment: Title */
    $display_options['fields']['title']['id'] = 'title';
    $display_options['fields']['title']['table'] = 'docker_host';
    $display_options['fields']['title']['field'] = 'title';
    $display_options['fields']['title']['provider'] = 'docker';
    $display_options['fields']['title']['label'] = '';
    $display_options['fields']['title']['alter']['alter_text'] = 0;
    $display_options['fields']['title']['alter']['make_link'] = 0;
    $display_options['fields']['title']['alter']['absolute'] = 0;
    $display_options['fields']['title']['alter']['trim'] = 0;
    $display_options['fields']['title']['alter']['word_boundary'] = 0;
    $display_options['fields']['title']['alter']['ellipsis'] = 0;
    $display_options['fields']['title']['alter']['strip_tags'] = 0;
    $display_options['fields']['title']['alter']['html'] = 0;
    $display_options['fields']['title']['hide_empty'] = 0;
    $display_options['fields']['title']['empty_zero'] = 0;
    $display_options['fields']['title']['link_to_docker_host'] = 1;

    return $display_options;
  }
}
