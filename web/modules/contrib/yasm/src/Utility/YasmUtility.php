<?php

namespace Drupal\yasm\Utility;

use Drupal\Component\Render\FormattableMarkup;

/**
 * Yasm build helper class.
 */
class YasmUtility {

  /**
   * Build yasm markup.
   */
  public static function markup($content, $picto = NULL, array $class = []) {
    $build = [
      '#markup' => self::picto($picto) . $content,
    ];
    if (!empty($class)) {
      return [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#attributes' => ['class' => $class],
        'child' => $build,
      ];
    }

    return $build;
  }

  /**
   * Build fontawesome picto.
   */
  public static function picto($picto = NULL) {
    if (!empty($picto)) {
      return new FormattableMarkup('<i class="@picto"></i> ', [
        '@picto' => $picto,
      ]);
    }

    return '';
  }

  /**
   * Build yasm table.
   */
  public static function table($header, $rows, $chart_key = '') {
    $build = [];
    if (!empty($chart_key)) {
      // Add a chart key to inform that this table is chartable.
      $build['#yasm_chart'] = $chart_key;
    }
    $build['yasm_table'] = [
      '#type' => 'table',
      '#attributes' => [
        'class' => ['datatable', 'display'],
      ],
      '#header' => $header,
      '#rows'   => $rows,
    ];

    return $build;
  }

  /**
   * Build yasm titles.
   */
  public static function title($title, $picto = NULL, $class = ['title']) {
    return [
      '#markup' => new FormattableMarkup('<h4 class="@class">' . self::picto($picto) . '@title</h4>', [
        '@title' => $title,
        '@class' => implode(' ', $class),
      ]),
    ];
  }

  /**
   * Build panel container.
   */
  public static function panel($content) {
    $build = [
      '#type' => 'container',
      '#attributes' => ['class' => ['panel', 'yasm-panel']],
      'child' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#attributes' => ['class' => ['panel__content']],
        '#value' => $content,
      ],
    ];

    if (is_string($content)) {
      $build['#value'] = $content;
    }
    else {
      $build['child'] = $content;
    }

    return $build;
  }

  /**
   * Build column container.
   */
  public static function column($content, $cols = NULL) {
    $cols = empty($cols) ? 4 : $cols;
    $col_class = [
      1 => ['layout-column', 'layout-column--full'],
      2 => ['layout-column', 'layout-column--half'],
      3 => ['layout-column', 'layout-column--one-third'],
      4 => ['layout-column', 'layout-column--quarter'],
    ];

    return [
      '#type' => 'container',
      '#attributes' => ['class' => $col_class[$cols]],
      'child' => $content,
    ];
  }

  /**
   * Build multiple columns with auto calculated cols.
   */
  public static function columns($cards, array $class = [], $max_cols = NULL) {
    $build = [];

    // Distribuite cards i columns (default maximum cols = 4).
    $max_cols = empty($max_cols) ? 4 : $max_cols;
    $cols = count($cards);
    $cols = ($cols > $max_cols) ? $max_cols : $cols;

    $columns = [];
    $column_index = 1;
    foreach ($cards as $card) {
      if (!empty($card)) {
        // If we have more cards than columns append cards to aviable column.
        if ($column_index > $cols) {
          $column_index = 1;
        }
        $columns[$column_index][] = self::panel($card);
        $column_index++;
      }
    }

    $build = [];
    foreach ($columns as $column) {
      $build[] = self::column($column, $cols);
    }

    return [
      '#type' => 'container',
      '#attributes' => ['class' => array_merge(['yasm-columns'], $class)],
      'child' => $build,
    ];
  }

  /**
   * Get last year timestamp months starting in the first day of every month.
   */
  public static function getLastMonths($year) {
    $max_date = is_numeric($year) ? strtotime('31-12-' . $year) : time();
    $min_date = strtotime('first day of this month', $max_date);

    $return = [];
    for ($i = 0; $i <= 11; $i++) {
      $return[] = [
        'label' => date('m-Y', $min_date),
        'min'   => $min_date,
        'max'   => $max_date,
      ];
      // Prepare values for next round.
      $max_date = $min_date;
      $min_date = strtotime('-1 month', $min_date);
    }

    // Array reverse because we want to return dates from minus to max.
    return array_reverse($return);
  }

  /**
   * Get interval filter from data array value.
   */
  public static function getIntervalFilter($key, $max, $min) {
    return [
      [
        'key'      => $key,
        'value'    => $max,
        'operator' => '<=',
      ],
      [
        'key'      => $key,
        'value'    => $min,
        'operator' => '>=',
      ],
    ];
  }

  /**
   * Get interval year filter.
   */
  public static function getYearFilter($key, $year) {
    $max = strtotime('31-12-' . $year);
    $min = strtotime('01-01-' . $year);

    return self::getIntervalFilter($key, $max, $min);
  }

  /**
   * Get year array build links from first year to current year.
   */
  public static function getYearLinks($first_year, $active_year) {
    // All link.
    $links = new FormattableMarkup('<li class="@class_li"><a href="@link" class="@class_a">@label</a></li>', [
      '@link' => '?year=all',
      '@class_li' => ('all' == $active_year) ? 'tabs__tab is-active' : 'tabs__tab',
      '@class_a' => ('all' == $active_year) ? 'link is-active' : 'link',
      '@label' => t('All'),
    ]);
    // Years links.
    $current_year = date('Y');
    for ($i = $first_year; $i <= $current_year; $i++) {
      $links .= new FormattableMarkup('<li class="@class_li"><a href="@link" class="@class_a">@label</a></li>', [
        '@link' => '?year=' . $i,
        '@class_li' => ($i == $active_year) ? 'tabs__tab is-active' : 'tabs__tab',
        '@class_a' => ($i == $active_year) ? 'link is-active' : 'link',
        '@label' => $i,
      ]);
    }

    return ['#markup' => '<nav class="yasm-tabs is-horizontal"><ul class="tabs secondary clearfix">' . $links . '</ul></nav>'];
  }

}
