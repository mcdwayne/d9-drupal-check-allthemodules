<?php

namespace Drupal\widget_engine_domain_access\Plugin\views\filter;

use Drupal\domain_access\Plugin\views\filter\DomainAccessCurrentAllFilter;

/**
 * Handles matching of current domain.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("widget_engine_domain_access_current_all_filter")
 */
class WidgetEngineDomainAccessCurrentAllFilter extends DomainAccessCurrentAllFilter {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();
    $all_table = $this->query->ensureTable('widget__field_domain_all_affiliates');
    $current_domain = \Drupal::service('domain.negotiator')->getActiveId();
    if (empty($this->value)) {
      $where = "$this->tableAlias.$this->realField <> '$current_domain'";
      $where = '(' . $where . " OR $this->tableAlias.$this->realField IS NULL)";
      $where = '(' . $where . " AND ($all_table.field_domain_all_affiliates_value = 0 OR $all_table.field_domain_all_affiliates_value IS NULL))";
    }
    else {
      $where = "($this->tableAlias.$this->realField = '$current_domain' OR $all_table.field_domain_all_affiliates_value = 1)";
    }
    $this->query->addWhereExpression($this->options['group'], $where);
    // This filter causes duplicates.
    $this->query->options['distinct'] = TRUE;
  }

}
