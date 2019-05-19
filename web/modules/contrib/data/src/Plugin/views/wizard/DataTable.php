<?php

namespace Drupal\data\Plugin\views\wizard;

use Drupal\views\Plugin\views\wizard\WizardPluginBase;

/**
 * Standard Views wizard plugin.
 *
 * @ingroup views_wizard_plugins
 *
 * @ViewsWizard(
 *   id = "data_table",
 *   deriver = "Drupal\data\Plugin\Derivative\DataDeriver",
 *   title = @Translation("Data")
 * )
 */
class DataTable extends WizardPluginBase {
  function defaultDisplayOptions() {
    $parent = parent::defaultDisplayOptions();
    $parent['base_table'] = $this->getDerivativeId();
    return $parent;
  }

}
