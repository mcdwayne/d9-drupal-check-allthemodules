<?php

namespace Drupal\date_pager\Plugin\views\pager;

/**
 * The plugin to handle date pager.
 *
 * @ingroup views_pager_plugins
 *
 * @ViewsPager(
 *   id = "date",
 *   title = @Translation("Date Pager"),
 *   short_title = @Translation("Date"),
 *   help = @Translation("Page by Date and choose a granularity for a date field"),
 *   theme = "datepager",
 *   register_theme = TRUE
 * )
 */
class DatePager extends DateSqlBase {

  /**
   * {@inheritdoc}
   */
  public function render($input) {
    return [
      '#theme' => $this->themeFunctions(),
      '#parameters' => [
        'min' => $this->minDate,
        'max' => $this->maxDate,
        'current' => $this->activeDate,
        'format' => $this->activeDate->granularity,
        'current_granularity' => $this->activeDate->granularityId,
        'route_name' => !empty($this->view->live_preview) ? '<current>' : '<none>',
      ],
      '#options' => $this->options,
    ];
  }

}
