<?php

namespace Drupal\cloudwords\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\ManyToOne;

/**
 * Filter based on project status.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("cloudwords_project_translation_status_filter")
 */
class ProjectStatus extends ManyToOne {
  /**
   * Gets the values of the options.
   *
   * @return array
   *   Returns options.
   */
  public function getValueOptions() {
    switch($this->view->current_display) {
      case 'block_1':
      case 'default':
      $this->valueOptions = cloudwords_project_active_options_list();
       break;
      case 'block_2':
        $this->valueOptions = cloudwords_project_closed_options_list();
        break;
    }
    return $this->valueOptions;
  }
}
