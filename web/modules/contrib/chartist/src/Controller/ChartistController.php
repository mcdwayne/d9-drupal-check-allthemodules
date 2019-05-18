<?php

namespace Drupal\chartist\Controller;

/**
 * Module controller class.
 */
class ChartistController {

  /**
   * Example page callback.
   */
  public function example() {
    $renderable = array();

    // Chartist line chart.
    $renderable['line_chart'] = array(
      '#theme' => 'chartist',
      '#title' => t('Line chart'),
      '#chart_type' => 'Line',
      '#data' => array(
        'series' => array(),
        'labels' => array(),
        'featured_points' => array(),
      ),
      '#settings' => array(
        'tooltip_schema' => '<b>[serie]</b><br />Argument: [x], Value: [y]',
        // Switch to TRUE if you like, I don't like the effect personally.
        'animate' => FALSE,
        // All the settings here are passed as the third
        // variable to the chartist constructor.
      ),
      '#classes' => array('ct-major-tenth'),
      '#wrapper_class' => 'example-line-chart',
      '#attached' => array(
        'css' => array(drupal_get_path('module', 'chartist') . '/example/example.css'),
      ),
    );

    // Prepare argument values.
    $npoints = 40;
    $start = -5;
    $end = 5;
    $step = ($end - $start) / $npoints;
    $arguments = array();
    for ($i = 0; $i < $npoints; $i++) {
      $arguments[] = $start + $i * $step;
    }

    // Prepare labels.
    for ($i = 0; $i < $npoints; $i++) {
      $renderable['line_chart']['#data']['labels'][] = $arguments[$i];
    }

    // Prepare values.
    $coefs = array(
      array(0, 0.2),
      array(-2, 0.5),
      array(1.5, 1),
    );
    for ($i = 0; $i < 3; $i++) {
      $renderable['line_chart']['#data']['series'][$i]['name'] = t('peak: !peak, standard deviation: !dev', array('!peak' => $coefs[$i][0], '!dev' => $coefs[$i][1]));
      for ($j = 0; $j < $npoints; $j++) {
        // Value.
        $renderable['line_chart']['#data']['series'][$i]['data'][] = $this->gaussDistribution($arguments[$j], $coefs[$i][0], $coefs[$i][1]);

        // Featured points.
        if ($arguments[$j] == $coefs[$i][0]) {
          // Series index, point index.
          $renderable['line_chart']['#data']['featured_points'][] = array($i, $j);
        }
      }
    }

    // Chartist bar chart.
    $renderable['bar_chart'] = array(
      '#theme' => 'chartist',
      '#title' => t('Bar chart'),
      '#chart_type' => 'Bar',
      '#data' => array(
        'series' => array(
          array(
            'name' => 'Serie 1',
            'data' => array(60000, 40000, 80000, 70000),
          ),
          array(
            'name' => 'Serie 2',
            'data' => array(40000, 30000, 70000, 65000),
          ),
          array(
            'name' => 'Serie 3',
            'data' => array(8000, 3000, 10000, 6000),
          ),
        ),
        'labels' => array(
          'First quarter of the year',
          'Second quarter of the year',
          'Third quarter of the year',
          'Fourth quarter of the year',
        ),
      ),
      '#settings' => array(
        'tooltip_schema' => '<b>[serie]</b><br />Value: [y]',
        'seriesBarDistance' => 10,
      ),
      '#classes' => array('ct-major-tenth'),
      '#wrapper_class' => 'example-bar-chart',
    );

    // Chartist pie chart.
    $renderable['pie_chart'] = array(
      '#theme' => 'chartist',
      '#title' => t('Pie chart'),
      '#chart_type' => 'Pie',
      '#data' => array(
        'series' => array(5, 3, 4),
        'labels' => array('label 1', 'label 2', 'label 3'),
      ),
      '#classes' => array('ct-major-tenth'),
      '#wrapper_class' => 'example-pie-chart',
    );

    // Attach example stylesheet.
    $renderable['#attached']['library'][] = 'chartist.example';
    return $renderable;
  }

  /**
   * Helper function to calculate Gauss distribution values.
   */
  private function gaussDistribution($x, $sigma2, $m) {
    return (1 / sqrt($m) * 2 * pi()) * exp((-pow($x - $sigma2, 2)) / 2 * $m);
  }

}
