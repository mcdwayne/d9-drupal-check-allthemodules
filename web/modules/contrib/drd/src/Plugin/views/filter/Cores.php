<?php

namespace Drupal\drd\Plugin\views\filter;

use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\filter\ManyToOne;
use Drupal\views\ViewExecutable;

/**
 * Filters by given list of available cores.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("drd_cores")
 */
class Cores extends ManyToOne {

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->valueTitle = t('Available cores');
    $this->definition['options callback'] = [$this, 'generateOptions'];
  }

  /**
   * Helper function that generates the options.
   *
   * @return array
   *   List of cores for a select form element.
   */
  public function generateOptions() {
    $query = \Drupal::database()->select('drd_core', 'c');
    return $query
      ->fields('c', ['id', 'name'])
      ->orderBy('c.name')
      ->execute()
      ->fetchAllKeyed(0, 1);
  }

}
