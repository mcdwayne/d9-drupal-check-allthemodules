<?php

namespace Drupal\views_sort_null_field\Plugin\views\sort;

use Drupal\views\Plugin\views\sort\SortPluginBase;

/**
 * Basic sort handler for NULL values.
 *
 * @ViewsSort("null_sort")
 */
class NullSort extends SortPluginBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();

    // Make an alias.
    $alias = implode('_', [$this->tableAlias, $this->realField, 'is_null']);

    $this->query->addOrderBy(NULL,
      "ISNULL($this->tableAlias.$this->realField)",
      $this->options['order'],
      $alias
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function sortOptions() {
    // ASC puts NULL last, as ISNULL is 1 for NULLs, and 0 for non-NULLs.
    return array(
      'ASC' => $this->t('Sort NULL last'),
      'DESC' => $this->t('Sort NULL first'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function adminSummary() {
    if (!empty($this->options['exposed'])) {
      return $this->t('Exposed');
    }
    switch ($this->options['order']) {
      case 'ASC':
      case 'asc':
      default:
        return $this->t('NULL last');

      case 'DESC';
      case 'desc';
        return $this->t('NULL first');
    }
  }
}
