<?php

namespace Drupal\drd\Plugin\views\filter;

use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\filter\ManyToOne;
use Drupal\views\ViewExecutable;

/**
 * Filters by given list of available core versions.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("drd_core_versions")
 */
class CoreVersions extends ManyToOne {

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->valueTitle = t('Core versions');
    $this->definition['options callback'] = [$this, 'generateOptions'];
  }

  /**
   * Helper function that generates the options.
   *
   * @return array
   *   List of core versions for a select form element.
   */
  public function generateOptions() {
    /* @var \Drupal\Core\Database\Query\SelectInterface $query */
    $query = \Drupal::database()->select('drd_major', 'm')
      ->fields('m', ['coreversion'])
      ->condition('m.hidden', 0)
      ->isNull('m.parentproject');
    return $query
      ->orderBy('m.coreversion')
      ->distinct()
      ->execute()
      ->fetchAllKeyed(0, 0);
  }

}
