<?php

namespace Drupal\charts_c3\Plugin\chart;

use Drupal\charts\Plugin\chart\AbstractChart;
use Drupal\charts_c3\Settings\CThree\ChartType;
use Drupal\charts_c3\Settings\CThree\CThree;
use Drupal\charts_c3\Settings\CThree\ChartTitle;
use Drupal\charts_c3\Settings\CThree\ChartData;
use Drupal\charts_c3\Settings\CThree\ChartColor;
use Drupal\charts_c3\Settings\CThree\ChartAxis;

/**
 * Define a concrete class for a Chart.
 *
 * @Chart(
 *   id = "c3",
 *   name = @Translation("C3")
 * )
 */
class C3 extends AbstractChart {

  /**
   * Creates a JSON Object formatted for C3 Charts JavaScript to use.
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

    // Create new instance of CThree.
    $c3 = new CThree();

    $seriesCount = count($seriesData);
    $attachmentCount = count($attachmentDisplayOptions);
    $noAttachmentDisplays = $attachmentCount === 0;
    $types = [];

    for ($i = 1; $i <= $attachmentCount; $i++) {
      if ($attachmentDisplayOptions[$i - 1]['style']['options']['type'] == 'column') {
        $types[$seriesData[$i]['name']] = 'bar';
      }
      else {
        $types[$seriesData[$i]['name']] = $attachmentDisplayOptions[$i - 1]['style']['options']['type'];
      }
    }

    // Set the chart type.
    $c3Chart = new ChartType();
    $c3Chart->setType($options['type']);

    // Set the chart title.
    $c3ChartTitle = new ChartTitle();
    $c3ChartTitle->setText($options['title']);
    $c3->setTitle($c3ChartTitle);

    // Set up the chart data object.
    $chartData = new ChartData();
    $chartData->setType($options['type']);
    $c3->setData($chartData);
    $chartAxis = new ChartAxis();

    /**
     * For pie and donut chart types, depending on the number of data fields,
     * the charts will either use data fields or label fields for the
     * categories. If only one data field is selected, then the label field
     * will serve as the categories. If multiple data fields are selected,
     * they will become the categories.
     * */
    if ($options['type'] == 'pie' || $options['type'] == 'donut') {
      // Set the charts colors.
      $chartColor = new ChartColor();
      $seriesColors = [];
      if ($seriesCount > 1) {
        $c3Data = [];
        for ($i = 0; $i < $seriesCount; $i++) {
          $c3DataTemp = $seriesData[$i]['data'];
          array_unshift($c3DataTemp, $seriesData[$i]['name']);
          array_push($c3Data, $c3DataTemp);
          $seriesColor = $seriesData[$i]['color'];
          array_push($seriesColors, $seriesColor);
        }
      }
      else {
        $c3Data = [];
        for ($i = 0; $i < count($seriesData[0]['data']); $i++) {
          $c3DataTemp = $seriesData[0]['data'][$i];
          $c3SeriesDataTemp = array_merge([$categories[$i]], [$c3DataTemp]);
          array_push($c3Data, $c3SeriesDataTemp);
        }
      }
      $chartData->setColumns($c3Data);
      $chartColor->setPattern($seriesColors);
      $c3->setColor($chartColor);
    }
    else {
      // Set the charts colors.
      $chartColor = new ChartColor();
      $seriesColors = [];
      for ($i = 0; $i < $seriesCount; $i++) {
        $seriesColor = $seriesData[$i]['color'];
        array_push($seriesColors, $seriesColor);
      }
      $chartColor->setPattern($seriesColors);
      $c3->setColor($chartColor);

      // Set up the chart data object.
      $c3Data = [];
      for ($i = 0; $i < $seriesCount; $i++) {
        $c3DataTemp = $seriesData[$i]['data'];
        array_unshift($c3DataTemp, $seriesData[$i]['name']);
        array_push($c3Data, $c3DataTemp);
      }
      // C3 does not use bar, so column must be used.
      if ($options['type'] == 'bar') {
        $chartAxis->setRotated(TRUE);
        array_unshift($categories, 'x');
        array_push($c3Data, $categories);
        $chartData->setColumns($c3Data);
      }
      elseif ($options['type'] == 'column') {
        $chartData->setType('bar');
        $chartAxis->setRotated(FALSE);
        array_unshift($categories, 'x');
        array_push($c3Data, $categories);
        $chartData->setColumns($c3Data);
      }
      else {
        array_unshift($categories, 'x');
        array_push($c3Data, $categories);
        $chartData->setColumns($c3Data);
      }
      $c3->setAxis($chartAxis);
    }
    // Set the chart types.
    $chartData->types = $types;

    // Set labels to FALSE if has attachments.
    if ($noAttachmentDisplays > 0) {
      $chartData->setLabels(FALSE);
    }

    // Sets the primary y axis.
    $showAxis['show'] = TRUE;
    $showAxis['label'] = $options['yaxis_title'];
    $chartAxis->y = $showAxis;

    // Sets secondary axis from the first attachment only.
    if (!$noAttachmentDisplays && $attachmentDisplayOptions[0]['inherit_yaxis'] == 0) {
      $showSecAxis['show'] = TRUE;
      $showSecAxis['label'] = $attachmentDisplayOptions[0]['style']['options']['yaxis_title'];
      $chartAxis->y2 = $showSecAxis;
    }

    // Determines if chart is stacked.
    if (!empty($options['grouping'] && $options['grouping'] == TRUE)) {
      $seriesNames = [];
      for ($i = 0; $i < $seriesCount; $i++) {
        array_push($seriesNames, $seriesData[$i]['name']);
      }
      $chartData->setGroups([$seriesNames]);
    }

    $bindTo = '#' . $chartId;
    $c3->setBindTo($bindTo);
    $variables['chart_type'] = 'c3';
    $variables['content_attributes']['data-chart'][] = json_encode($c3);
    $variables['attributes']['id'][0] = $chartId;
    $variables['attributes']['class'][] = 'charts-c3';
  }

}
