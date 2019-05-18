<?php

/**
 * @file
* Definition of \Plugin\views\filter\MaestroEngineTemplateFilter.
*/

namespace Drupal\maestro\Plugin\views\filter;

use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;
use Drupal\maestro\Engine\MaestroEngine;
use Drupal\views\Plugin\views\filter\InOperator;

/**
 * Filter for Maestro Template names
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("maestro_process_template_filter")
 */
class MaestroEngineTemplateFilter extends InOperator {

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->valueTitle = $this->t('Templates to Filter On');
    $this->definition['options callback'] = array($this, 'generateTemplateOptions');
  }

  /**
   * Override the query so that no filtering takes place if the user doesn't
   * select any options.
   */
  public function query() {
    if (!empty($this->value) && current($this->value) != '0') {
      parent::query();
    }
  }

  /**
   * Skip validation if no options have been chosen so we can use it as a
   * non-filter.
   */
  public function validate() {
    if (!empty($this->value)) {
      parent::validate();
    }
  }


  
  protected function generateTemplateOptions() {
    $templates = MaestroEngine::getTemplates();
    $options = [];
    $options[0] = $this->t(' - Any -');
    foreach($templates as $machine_name => $template) {
      $options[$machine_name] = $template->label;
    }

    return $options;
  }

}