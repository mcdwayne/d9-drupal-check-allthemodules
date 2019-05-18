<?php

namespace Drupal\drd\Plugin\views\filter;

use Drupal\drd\UpdateProcessor;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\filter\ManyToOne;
use Drupal\views\ViewExecutable;

/**
 * Filters by given list of available update statuses.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("drd_update_status")
 */
class UpdateStatus extends ManyToOne {

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->valueTitle = t('Available update statuses');
    $this->definition['options callback'] = [$this, 'generateOptions'];
  }

  /**
   * Helper function that generates the options.
   *
   * @return array
   *   List of update states for a select form element.
   */
  public function generateOptions() {
    $statuses = UpdateProcessor::getStatuses();
    $list = [];
    foreach ($statuses['status'] as $status => $def) {
      $list[$status] = $def['label'];
    }
    return $list;
  }

}
