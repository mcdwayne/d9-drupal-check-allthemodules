<?php

namespace Drupal\audit_log\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\Date;

/**
 * Filter to handle dates stored as a timestamp.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("audit_log_date")
 */
class AuditLogDate extends Date {

  /**
   * {@inheritdoc}
   */
  protected function opSimple($field) {

    if (!empty($this->value['type']) && $this->value['type'] == 'offset') {
      $value = intval(strtotime($this->value['value'], 0));
      $value = '***CURRENT_TIME***' . sprintf('%+d', $value);
    }
    else {
      $tz = 'UTC';
      $dt = new \DateTime($this->value['value'], new \DateTimeZone($tz));
      $value = $dt->getTimestamp();
    }
    // This is safe because we are manually scrubbing the value.
    // It is necessary to do it this way because $value is a formula
    // when using an offset.
    $expression = "$field $this->operator $value";
    $this->query->addWhereExpression($this->options['group'], $expression);
  }

}
