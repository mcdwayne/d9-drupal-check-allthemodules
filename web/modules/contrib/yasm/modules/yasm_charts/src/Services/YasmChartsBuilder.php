<?php

namespace Drupal\yasm_charts\Services;

use Drupal\charts\Services\ChartsSettingsServiceInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Component\Utility\Bytes;
use Drupal\Component\Uuid\UuidInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Yasm charts build helper class.
 */
class YasmChartsBuilder implements YasmChartsBuilderInterface {

  use StringTranslationTrait;

  /**
   * The charts settings.
   *
   * @var \Drupal\charts\Services\ChartsSettingsServiceInterface
   */
  protected $chartSettings;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The UUID service.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $uuidService;

  /**
   * {@inheritdoc}
   */
  public function discoverCharts($build, $settings) {
    if (!empty($this->chartSettings['library'])) {
      foreach ($build as $key => $element) {
        if ('#yasm_chart' === $key) {
          if (!empty($build['yasm_table'])) {
            $chart = $this->applyChartSettings($build['#yasm_chart'], $build['yasm_table'], $settings);
            if (is_array($chart)) {
              $build['yasm_chart'] = $chart;
            }
          }
        }
        elseif (is_array($element)) {
          // Recursive call through array depths.
          $build[$key] = $this->discoverCharts($element, $settings);
        }
      }
    }
    else {
      $this->messenger->addError($this->t('To display charts you first need to set up a charts library in <a href="@link">charts settings</a>.', [
        '@link' => '/admin/config/content/charts',
      ]));
    }

    return $build;
  }

  /**
   * Build yasm chart.
   */
  private function buildChart($chart, $settings) {
    if (!empty($chart['#rows'])) {
      // Get chart settings.
      $settings['skip_left'] = isset($settings['skip_left']) ? $settings['skip_left'] : 1;
      $settings['skip_right'] = isset($settings['skip_right']) ? $settings['skip_right'] : 0;
      $settings['skip_top'] = isset($settings['skip_top']) ? $settings['skip_top'] : 0;
      $settings['label'] = isset($settings['label']) ? $settings['label'] : '';
      $settings['label_position'] = isset($settings['label_position']) ? $settings['label_position'] : 1;

      $settings['options'] += $this->chartSettings;
      if (isset($settings['title'])) {
        $settings['options']['title'] = $settings['title'];
      }
      elseif (isset($chart['#title'])) {
        $settings['options']['title'] = $chart['#title'];
      }
      $settings['options']['type'] = isset($settings['type']) ? $settings['type'] : 'line';

      // Build data series and labels.
      if ($seriesData = $this->getChartSeries($chart['#rows'], $settings)) {
        // Chart categories.
        $categories = [];
        if ($settings['options']['type'] == 'pie' && count($seriesData) === 1 && isset($seriesData[0]['name'])) {
          $categories = [$seriesData[0]['name']];
        }
        elseif (isset($chart['#header'])) {
          $categories = $this->getChartCategories($chart['#header'], $settings);
        }

        // Chart Unique ID.
        if (!isset($settings['id'])) {
          $settings['id'] = $this->uuidService->generate();
        }

        return [
          [
            '#theme' => 'yasm_chart',
            '#library' => (string) $this->chartSettings['library'],
            '#categories' => $categories,
            '#seriesData' => $seriesData,
            '#options' => $settings['options'],
            '#id' => 'chart-' . $settings['id'],
            '#override' => isset($settings['override']) ? $settings['override'] : [],
          ],
        ];
      }
    }

    return [];
  }

  /**
   * Build yasm charts.
   */
  private function buildCharts($chart, $settings) {
    if (isset($settings['type'])) {
      // We only have one chart with this key.
      return $this->buildChart($chart, $settings);
    }
    else {
      // We have multiple charts with this key.
      $build = [];
      foreach ($settings as $setting) {
        if (isset($setting['type'])) {
          $build[] = $this->buildChart($chart, $setting);
        }
      }

      return $build;
    }
  }

  /**
   * Get chart array series.
   */
  private function getChartSeries($rows, $settings) {
    $series = [];
    $i = 0;
    if ($settings['skip_top'] > 0) {
      $rows = array_slice($rows, $settings['skip_top']);
    }
    foreach ($rows as $row) {
      if (!empty($row)) {
        $row = (is_array($row) && isset($row['data'])) ? $row['data'] : $row;

        $label = $this->getChartLabel($row, $settings['label'], $settings['label_position']);
        $value = $this->skipArray($row, $settings['skip_left'], $settings['skip_right']);

        $items = [];
        foreach ($value as $col) {
          $item = (is_array($col) && isset($col['data'])) ? $col['data'] : $col;
          $item = (string) $item;
          // If the value is a size (5MB, 3GB, 15KB...) convert value to bytes.
          $items[] = $this->isSize($item) ? Bytes::toInt($item) : (int) $item;
        }

        $series[] = [
          'type'  => $settings['options']['type'],
          'name'  => (string) $label,
          'color' => $this->getChartColor($i),
          'data'  => $items,
        ];
        $i++;
      }
    }

    return $series;
  }

  /**
   * Get chart categories for X axis.
   */
  private function getChartCategories($labels, $settings) {
    $categories = [];
    if (is_array($labels) && !empty($labels)) {
      $categories = $this->skipArray($labels, $settings['skip_left'], $settings['skip_right']);
      $categories = array_values($categories);
    }

    return $categories;
  }

  /**
   * Get an hexadecimal color for charts.
   */
  private function getChartColor($i = 0) {
    $colors = [
      0 => '#205CB7',
      1 => '#815D51',
      2 => '#5EB15A',
      3 => '#9A38B2',
      4 => '#00BFD5',
      5 => '#009A8E',
      6 => '#FFC21C',
      7 => '#009CF2',
      8 => '#FF9B0F',
      9 => '#F84C3E',
      10 => '#99C455',
      11 => '#EC2C69',
    ];

    if (isset($colors[$i])) {
      $color = $colors[$i];
    }
    else {
      // Chart with more than 12 colors. Return one randon color.
      $color = $colors[rand(0, count($colors) - 1)];
    }

    return $color;
  }

  /**
   * Skip some elemenys of array.
   */
  private function skipArray($array, $skip_left = 1, $skip_right = 0) {
    if ($skip_left > 0) {
      $array = array_slice($array, $skip_left);
    }
    if ($skip_right > 0) {
      $array = array_slice($array, 0, count($array) - $skip_right);
    }

    return $array;
  }

  /**
   * Get chart label from skiped elements.
   */
  private function getChartLabel($row, $label, $label_position) {
    if (empty($label) && $label_position > 0) {
      $col = array_slice($row, $label_position - 1, 1);
      $col = reset($col);
      $col = (is_array($col) && isset($col['data'])) ? $col['data'] : $col;
      $label = (is_array($col)) ? reset($col) : $col;
      $label = (string) $label;
    }

    return $label;
  }

  /**
   * Appply chart settings and build the chart array.
   */
  private function applyChartSettings($chart_key, $table, $settings) {
    if (isset($settings[$chart_key]) && !empty($table['#rows'])) {
      return $this->buildCharts($table, $settings[$chart_key]);
    }

    return $chart_key;
  }

  /**
   * Check if string is a size string.
   */
  private function isSize($string) {
    $units = ['KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
    if (in_array(substr($string, -2), $units)) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(ChartsSettingsServiceInterface $chartSettings, MessengerInterface $messenger, UuidInterface $uuidService) {
    $this->chartSettings = $chartSettings->getChartsSettings();
    $this->messenger = $messenger;
    $this->uuidService = $uuidService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('charts.settings'),
      $container->get('messenger'),
      $container->get('uuid')
    );
  }

}
