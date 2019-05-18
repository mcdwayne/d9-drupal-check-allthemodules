<?php

/**
 * @file
 * Definition of Drupal\owner_or_admin\Plugin\views\filter/OwnerOrAdminFilter
 */

namespace Drupal\owner_or_admin\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;

/**
 * Filter view results by a combination of entity owner and logged-in user
 * permissions.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("owner_or_admin_filter")
 */
class OwnerOrAdmin extends FilterPluginBase {

  /**
   * Make sure there's no admin summary.
   */
  public function adminSummary() { }

  /**
   * Make sure an operator form is not exposed.
   */
  protected function operatorForm(&$form, FormStateInterface $form_state) { }

  /**
   * Make sure the filter is not exposed.
   */
  public function canExpose() { return FALSE; }

  /**
   * Add where expression to the query to filter the content based on ownership
   * of the content or on the administer nodes permission.
   */
  public function query() {
    $table = $this->ensureMyTable();
    $this->query->addWhereExpression($this->options['group'], "($table.uid = ***CURRENT_USER*** AND ***CURRENT_USER*** <> 0) OR ***ADMINISTER_NODES*** = 1");
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = parent::getCacheContexts();

    $contexts[] = 'user';

    return $contexts;
  }

}
