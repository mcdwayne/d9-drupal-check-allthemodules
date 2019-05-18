<?php

namespace Drupal\charts_highcharts\Plugin\chart;

use Drupal\charts\Plugin\chart\AbstractChart;
use Drupal\charts_highcharts\Settings\Highcharts\Chart;
use Drupal\charts_highcharts\Settings\Highcharts\ChartTitle;
use Drupal\charts_highcharts\Settings\Highcharts\ExportingOptions;
use Drupal\charts_highcharts\Settings\Highcharts\PlotOptionsStacking;
use Drupal\charts_highcharts\Settings\Highcharts\ThreeDimensionalOptions;
use Drupal\charts_highcharts\Settings\Highcharts\Xaxis;
use Drupal\charts_highcharts\Settings\Highcharts\XaxisTitle;
use Drupal\charts_highcharts\Settings\Highcharts\ChartLabel;
use Drupal\charts_highcharts\Settings\Highcharts\YaxisLabel;
use Drupal\charts_highcharts\Settings\Highcharts\Yaxis;
use Drupal\charts_highcharts\Settings\Highcharts\YaxisTitle;
use Drupal\charts_highcharts\Settings\Highcharts\PlotOptions;
use Drupal\charts_highcharts\Settings\Highcharts\PlotOptionsSeries;
use Drupal\charts_highcharts\Settings\Highcharts\PlotOptionsSeriesDataLabels;
use Drupal\charts_highcharts\Settings\Highcharts\Tooltip;
use Drupal\charts_highcharts\Settings\Highcharts\ChartCredits;
use Drupal\charts_highcharts\Settings\Highcharts\ChartLegend;
use Drupal\charts_highcharts\Settings\Highcharts\HighchartsOptions;

/**
 * Defines a concrete class for a Highcharts.
 *
 * @Chart(
 *   id = "highcharts",
 *   name = @Translation("Highcharts")
 * )
 */
class Highcharts extends AbstractChart {

  /**
   * Creates a JSON Object formatted for Highcharts JavaScript to use.
   *
   * @param mixed $options
   *   Options.
   * @param mixed $categories
   *   Categories.
   * @param mixed $seriesData
   *   Series data.
   * @param mixed $attachmentDisplayOptions
   *   Attachment display options.
   * @param mixed $variables
   *   Variables.
   * @param mixed $chartId
   *   Chart Id.
   */
  public function buildVariables($options, $categories = [], $seriesData = [], $attachmentDisplayOptions = [], &$variables, $chartId) {
    $noAttachmentDisplays = count($attachmentDisplayOptions) === 0;

    $chart = new Chart();
    $typeOptions = $options['type'];
    // @todo: make this so that it happens if any display uses donut.
    if ($typeOptions == 'donut') {
      $typeOptions = 'pie';
      // Remove donut from seriesData.
      foreach ($seriesData as &$value) {
        $value = str_replace('donut', 'pie', $value);
      }
      // Add innerSize to differentiate between donut and pie.
      foreach ($seriesData as &$value) {
        if ($typeOptions == 'pie') {
          $innerSize['showInLegend'] = 'true';
          $innerSize['innerSize'] = '40%';
          $chartPlacement = array_search($value, $seriesData);
          $seriesData[$chartPlacement] = array_merge($innerSize, $seriesData[$chartPlacement]);
        }
      }
    }
    $chart->setType($typeOptions);

    // Determines if chart is three-dimensional.
    if (!empty($options['three_dimensional'])) {
      $threeDimensionOptions = new ThreeDimensionalOptions();

      $chart->setOptions3D($threeDimensionOptions);
    }

    // Set chart width.
    if (isset($options['width'])) {
      $chart->setWidth($options['width']);
    }

    // Set chart height.
    if (isset($options['height'])) {
      $chart->setHeight($options['height']);
    }

    // Set chart title.
    $chartTitle = new ChartTitle();
    if (isset($options['title'])) {
      $chartTitle->setText($options['title']);
    }

    // Set background color.
    if (isset($options['background'])) {
      $chart->setBackgroundColor($options['background']);
    }

    // Set polar plotting.
    if (isset($options['polar'])) {
      $chart->setPolar($options['polar']);
    }

    // Set title position.
    if (isset($options['title_position'])) {
      if ($options['title_position'] == 'in') {
        $chartTitle->setVerticalAlign('middle');
      }
      else {
        $chartTitle->setVerticalOffset(20);
      }
    }

    $chartXaxis = new Xaxis();
    $chartLabels = new ChartLabel();

    // Set x-axis label rotation.
    if (isset($options['xaxis_labels_rotation'])) {
      $chartLabels->setRotation($options['xaxis_labels_rotation']);
    }

    // If donut or pie and only one data point with multiple fields in use.
    if (($options['type'] == 'pie' || $options['type'] == 'donut') && (count($seriesData[0]['data']) == 1)) {
      unset($categories);
      $categories = [];
      for ($i = 0; $i < count($seriesData); $i++) {
        array_push($categories, $seriesData[$i]['name']);
      }
    }
    $chartXaxis->setCategories($categories);

    // Set x-axis title.
    $xAxisTitle = new XaxisTitle();
    if (isset($options['xaxis_title'])) {
      $xAxisTitle->setText($options['xaxis_title']);
    }
    $chartXaxis->setTitle($xAxisTitle);
    $chartXaxis->setLabels($chartLabels);
    $yaxisLabels = new YaxisLabel();
    $chartYaxis = new Yaxis();
    $yAxes = [];
    $yAxisTitle = new YaxisTitle();
    $yAxisTitle->setText($options['yaxis_title']);
    if (is_numeric($options['yaxis_min'])) {
      $chartYaxis->min = $options['yaxis_min'];
    }
    if (is_numeric($options['yaxis_max'])) {
      $chartYaxis->max = $options['yaxis_max'];
    }

    $chartYaxis->setLabels($yaxisLabels);
    $chartYaxis->setTitle($yAxisTitle);
    array_push($yAxes, $chartYaxis);

    // Chart libraries tend to support only one secondary axis.
    if (!$noAttachmentDisplays && $attachmentDisplayOptions[0]['inherit_yaxis'] == 0) {
      $chartYaxisSecondary = new Yaxis();
      $yAxisTitleSecondary = new YaxisTitle();
      $yAxisTitleSecondary->setText($attachmentDisplayOptions[0]['style']['options']['yaxis_title']);
      $chartYaxisSecondary->setTitle($yAxisTitleSecondary);
      $chartYaxisSecondary->setLabels($yaxisLabels);
      $chartYaxisSecondary->opposite = 'true';
      if (!empty($attachmentDisplayOptions[0]['style']['options']['yaxis_min'])) {
        $chartYaxisSecondary->min = $attachmentDisplayOptions[0]['style']['options']['yaxis_min'];
      }
      if (!empty($attachmentDisplayOptions[0]['style']['options']['yaxis_max'])) {
        $chartYaxisSecondary->max = $attachmentDisplayOptions[0]['style']['options']['yaxis_max'];
      }
      array_push($yAxes, $chartYaxisSecondary);
    }

    // Set plot options.
    $plotOptions = new PlotOptions();
    $plotOptionsStacking = new PlotOptionsStacking();
    $plotOptionsSeries = new PlotOptionsSeries();
    $plotOptionsSeriesDataLabels = new PlotOptionsSeriesDataLabels();
    // Set plot options if stacked chart.
    if (!empty($options['grouping'])) {
      $plotOptions->setPlotSeries($plotOptionsStacking);
      $plotOptionsStacking->setDataLabels($plotOptionsSeriesDataLabels);
    }
    else {
      $plotOptions->setPlotSeries($plotOptionsSeries);
      $plotOptionsSeries->setDataLabels($plotOptionsSeriesDataLabels);
    }
    if (isset($options['data_labels'])) {
      $plotOptionsSeriesDataLabels->setEnabled($options['data_labels']);
    }

    // Set Tooltip.
    $chartTooltip = new Tooltip();
    if (isset($options['tooltips'])) {
      $chartTooltip->setEnabled($options['tooltips']);
    }
    $chartCredits = new ChartCredits();

    // Set charts legend.
    $chartLegend = new ChartLegend();
    if (empty($options['legend_position'])) {
      $chartLegend->setEnabled(FALSE);
    }
    elseif (in_array($options['legend_position'], ['left', 'right'])) {
      $chartLegend->setAlign($options['legend_position']);
      $chartLegend->setVerticalAlign('top');
      $chartLegend->setY(80);
      if ($options['legend_position'] == 'left') {
        $chartLegend->setX(0);
      }
    }
    else {
      $chartLegend->setVerticalAlign($options['legend_position']);
      $chartLegend->setAlign('center');
      $chartLegend->setX(0);
      $chartLegend->setY(30);
      $chartLegend->setFloating(FALSE);
    }

    // Set exporting options.
    $exporting = new ExportingOptions();

    $highchart = new HighchartsOptions();
    $highchart->setChart($chart);
    $highchart->setTitle($chartTitle);
    $highchart->setAxisX($chartXaxis);
    $highchart->setAxisY($chartYaxis);
    $highchart->setTooltip($chartTooltip);
    $highchart->setPlotOptions($plotOptions);
    $highchart->setCredits($chartCredits);
    $highchart->setLegend($chartLegend);
    // Usually just set the series with seriesData.
    if (($options['type'] == 'pie' || $options['type'] == 'donut') && (count($seriesData[0]['data']) == 1)) {
      for ($i = 0; $i < count($seriesData); $i++) {
        $seriesData[$i]['y'] = $seriesData[$i]['data'][0];
        unset($seriesData[$i]['data']);
      }
      $chartData = ['data' => $seriesData];
      $highchart->setSeries([$chartData]);
    }
    else {
      $highchart->setSeries($seriesData);
    }
    $highchart->setExporting($exporting);
    $variables['chart_type'] = 'highcharts';
    $variables['content_attributes']['data-chart'][] = json_encode($highchart);
    $variables['attributes']['id'][0] = $chartId;
    $variables['attributes']['class'][] = 'charts-highchart';
  }

}
