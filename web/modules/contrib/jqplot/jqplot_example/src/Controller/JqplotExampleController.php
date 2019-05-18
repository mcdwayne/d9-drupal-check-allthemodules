<?php

/**
 * @file
 * Contains \Drupal\jqplot_example\Controller\jqplotExampleController.
 */

namespace Drupal\jqplot_example\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller for jqPlot Example pages.
 *
 * @ingroup jqPlot
 */
class JqplotExampleController extends ControllerBase {

  /**
   * JqPlot Example info page.
   *
   * @return array $build
   *   A renderable array.
   */
  public function info() {
    global $base_path;
    $output = "";

    // Start building the content.
    $build = array();
    $output .= '<p><b>jqPlot is a plotting and charting plugin</b>.If Chart get display then libraries get install correctly or You will need to download jquery.jqplot from https://bitbucket.org/cleonello/jqplot/downloads/ and extract the jquery.jqplot files to the "[root]/libraries/" directory and rename to jquery.jqplot(e.g:[root]/libraries/jquery.jqplot/)</p>';
    $output .= '<ol>';
    $output .= '<li>' . $this->t('<a href="!lineChart">Line Chart</a>', array('!lineChart' => $base_path . 'jqplot_example/line_charts')) . '</li>';
    $output .= '<li>' . $this->t('<a href="!BarChart">Bar Chart</a>', array('!BarChart' => $base_path . 'jqplot_example/bar_charts')) . '</li>';
    $output .= '<li>' . $this->t('<a href="!PieChart">Pie Chart</a>', array('!PirChart' => $base_path . 'jqplot_example/pie_charts')) . '</li>';
    $output .= '<li>' . $this->t('<a href="!DynamiChart">Dynamic Charts</a>', array('!DynamiChart' => $base_path . 'jqplot_example/dynamic_charts')) . '</li>';
    $output .= '</ol>';

    $build['content'] = array(
      '#markup' => $output,
    );

    return $build;
  }

  /**
   * Line Chart Example page.
   *
   * @return array $build
   *   A renderable array of line chart and info about libraries files.
   */
  public function lineCharts() {

    // Start building the content.
    $build = array();
    // Showing code formating warning in $build array(),
    // so created separate variable.
    $top_content = <<<INFOMARKUP
        <ol>
          <li>The following plot uses a number of options to set the title, add axis labels, and shows how to use the canvasAxisLabelRenderer plugin to provide rotated axis labels.</li>
            <li>Charts on this page may depend on the following plugins:<br>
              <p>['#attached']['library'][] = 'jqplot/jqplot.canvasAxisLabelRenderer.min'</p>
              <p>['#attached']['library'][] = 'jqplot/jqplot.canvasTextRenderer.min'</p>
            </li>
        </ol></br>
INFOMARKUP;
    $build['top_content'] = array(
      '#markup' => $top_content,
    );

    // Main container DIV. We give it a unique ID so that the JavaScript can
    // find it using jQuery.
    $build['content'] = array(
      '#markup' => '<div id="chart-line" class="jqplot-target"></div>',
    );

    // Attach library containing css and js files.
    $build['#attached']['library'][] = 'jqplot/jqplot.canvasAxisLabelRenderer.min';
    $build['#attached']['library'][] = 'jqplot/jqplot.canvasTextRenderer.min';
    $build['#attached']['library'][] = 'jqplot_example/jqplot.example';
    return $build;
  }

  /**
   * BarCharts Example page.
   *
   * @return array $build
   *   A renderable array of Bar chart and info about libraries files.
   */
  public function barCharts() {

    // Start building the content.
    $build = array();
    // Showing code formating warning in $build array(),
    // so created separate variable.
    $top_content = <<<INFOMARKUP
        <ol>
          <li>Below is a default bar plot. Bars will highlight on mouseover. Events are triggered when you mouseover a bar and also when you click on a bar. Here We capture the 'jqplotDataClick' event and display the clicked series index, point index and data values. When series data is assigned as a 1-dimensional array as in this example, jqPlot automatically converts it into a 2-dimensional array for plotting. So a series defined as [2, 6, 7, 10] will become [[1,2], [2,6], [3,7], [4,10]].</li>
          <li>Charts on this page may depend on the following plugins:<br>
            <p>['#attached']['library'][] = 'jqplot/jqplot.barRenderer.min'</p>
            <p>['#attached']['library'][] = 'jqplot/jqplot.categoryAxisRenderer.min'</p>
            <p>['#attached']['library'][] = 'jqplot/jqplot.pointLabels.min'</p>
        </li>
        </ol></br>
INFOMARKUP;
    $build['top_content'] = array(
      '#markup' => $top_content,
    );

    // Main container DIV. We give it a unique ID so that the JavaScript can
    // find it using jQuery.
    $build['content'] = array(
      '#markup' => '<div><span>You Clicked: </span><span id="info-bar">Nothing yet</span></div><div id="chart-bar" class="jqplot-target"></div>',
    );

    // Attach library containing css and js files.
    $build['#attached']['library'][] = 'jqplot/jqplot.barRenderer.min';
    $build['#attached']['library'][] = 'jqplot/jqplot.categoryAxisRenderer.min';
    $build['#attached']['library'][] = 'jqplot/jqplot.pointLabels.min';
    $build['#attached']['library'][] = 'jqplot_example/jqplot.example';
    return $build;
  }

  /**
   * Pie Chart Example Page.
   *
   * @return array
   *   A renderable array of Pie chart and info about libraries files.
   */
  public function pieCharts() {

    // Start building the content.
    $build = array();
    // Showing code formating warning in $build array(),
    // so created separate variable.
    $top_content = <<<INFOMARKUP
        <ol>
          <li>Below is a default pie plot. Pie slices highlight when you mouse over.</li>
          <li>Charts on this page may depend on the following plugins:<br>
            <p>['#attached']['library'][] = 'jqplot/jqplot.pieRenderer.min'</p>
          </li>
        </ol></br>
INFOMARKUP;

    $build['top_content'] = array(
      '#markup' => $top_content,
    );

    // Main container DIV. We give it a unique ID so that the JavaScript can
    // find it using jQuery.
    $build['content'] = array(
      '#markup' => '<div id="chart-pie" class="jqplot-target"></div>',
    );

    // Attach library containing css and js files.
    $build['#attached']['library'][] = 'jqplot/jqplot.pieRenderer.min';
    $build['#attached']['library'][] = 'jqplot_example/jqplot.example';

    return $build;
  }

  /**
   * Dynamic Pie Chart Example Page.
   *
   * @return array $build
   *   A renderable array of Total nid created in content type using  Pie chart
   *    and info about libraries files.
   */
  public function dynamicCharts() {

    // Start building the content.
    $build = array();
    $nids = array();
    $build['top_content'] = 'Below is a default pie plot.';
    $content_types_data = \Drupal\node\Entity\NodeType::loadMultiple();
    foreach ($content_types_data as $key => $value) {
      $content_types[$value->get('type')] = $value->get('name');
    }
    $count = 0;
    $content_created = FALSE;
    $no_content = '';
    $content_type_nids = array();
    foreach ($content_types as $type => $type_name) {
      $query = \Drupal::entityQuery('node')
        ->condition('type', $type)
        ->condition('status', 1);

      if ($nids = $query->execute()) {
        $content_type_nids[$count][] = $type_name;
        $content_type_nids[$count][] = count($nids);
        $content_created = TRUE;
      }
      else {
        $content_type_nids[$count][] = $type_name;
        $content_type_nids[$count][] = 0;
      }
      $count++;
    }

    // Set message if no nids found.
    if (!$content_created) {
      $no_content = 'Content not found! Please Add content to see Dynamic Pie chart.';
    }

    // Showing code formating warning in $build array(),
    // so created separate variable.
    $top_content = <<<INFOMARKUP
        <ol>
          <li>Below is a Dynamic pie plot. Pie chart displaying total numbers of nids from content types. Pie slices highlight when you mouse over.</li>
          <li>To render Pie chart, need to add content from backEnd. e.g Add node under Article content type, Basic Page content type etc.</li>
          <li>Charts on this page may depend on the following plugins:<br>
            <p>['#attached']['library'][] = 'jqplot/jqplot.pieRenderer.min'</p>
          </li>
        </ol></br>
INFOMARKUP;

    $build['top_content'] = array(
      '#markup' => $top_content,
    );
    $build['no_content'] = array(
      '#markup' => (!empty($no_content) ? "<div class='messages messages--error'>" . $no_content . "</div>" : NULL),
    );

    // Main container DIV. We give it a unique ID so that the JavaScript can
    // find it using jQuery.
    $build['content'] = array(
      '#markup' => '<div id="chart-dynamic" class="jqplot-target"></div>',
    );

    // Attach library containing css and js files.
    $build['#attached']['drupalSettings']['dynamicPieChart'] = $content_type_nids;
    $build['#attached']['library'][] = 'jqplot/jqplot.pieRenderer.min';
    $build['#attached']['library'][] = 'jqplot_example/jqplot.example';

    return $build;
  }

}
