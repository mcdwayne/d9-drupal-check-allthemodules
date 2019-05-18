<?php

namespace Drupal\monster_menus\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\BooleanOperator;

/**
 * Filter on whether or not a node is in the recycle bin.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("is_recycled")
 */
class IsRecycled extends BooleanOperator {

  const IS_RECYCLED  = 'recycled';
  const NOT_RECYCLED = 'not_recycled';

  /**
   * Returns an array of operator information.
   *
   * @return array
   */
  protected function operators() {
    return [
      static::IS_RECYCLED => [
        'title' => $this->t('Is in a recycle bin'),
        'method' => 'queryOp',
        'short' => $this->t(static::IS_RECYCLED),
        'values' => 1,
        'query_operator' => 'IS NOT NULL',
      ],
      static::NOT_RECYCLED => [
        'title' => $this->t('Is not in a recycle bin'),
        'method' => 'queryOp',
        'short' => $this->t(static::NOT_RECYCLED),
        'values' => 1,
        'query_operator' => 'IS NULL',
      ],
    ];
  }

  /**
   * @inheritDoc
   */
  public function canExpose() {
    return FALSE;
  }

  /**
   * @inheritDoc
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['operator']['default'] = static::NOT_RECYCLED;

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function adminSummary() {
    if ($this->isAGroup()) {
      return $this->t('grouped');
    }
    if (!empty($this->options['exposed'])) {
      return $this->t('exposed');
    }
    return $this->operator == static::NOT_RECYCLED ? $this->t('False') : $this->t('True');
  }

  /**
   * Adds a where condition to the query.
   *
   * @param string $field
   *   The field name to add the where condition for.
   * @param string $query_operator
   *   The operator: 'IS NULL' or 'IS NOT NULL'
   */
  protected function queryOp($field, $query_operator) {
    $this->query->ensureTable('mm_recycle');
    $this->query->addWhere($this->options['group'], $field, 0, $query_operator);
  }

}
