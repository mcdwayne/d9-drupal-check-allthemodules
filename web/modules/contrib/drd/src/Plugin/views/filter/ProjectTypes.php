<?php

namespace Drupal\drd\Plugin\views\filter;

use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\filter\ManyToOne;
use Drupal\views\ViewExecutable;

/**
 * Filters by given list of available project types.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("drd_project_types")
 */
class ProjectTypes extends ManyToOne {

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->valueTitle = t('Available project types');
    $this->definition['options callback'] = [$this, 'generateOptions'];
  }

  /**
   * Helper function that generates the options.
   *
   * @return array
   *   List of project types for a select form element.
   */
  public function generateOptions() {
    return \Drupal::database()->select('drd_project', 'p')
      ->fields('p', ['type'])
      ->distinct()
      ->execute()
      ->fetchAllKeyed(0, 0);
  }

}
