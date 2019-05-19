<?php

/**
 * @file
 * Contains \Drupal\xhprof\Controller\XHProfController.
 */

namespace Drupal\xhprof\Controller;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\xhprof\ProfilerInterface;
use Drupal\xhprof\XHProfLib\Report\ReportConstants;
use Drupal\xhprof\XHProfLib\Report\ReportEngine;
use Drupal\xhprof\XHProfLib\Report\ReportInterface;
use Drupal\xhprof\XHProfLib\Run;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class XHProfController
 */
class XHProfController extends ControllerBase {

  /**
   * @var \Drupal\xhprof\ProfilerInterface
   */
  private $profiler;

  /**
   * @var \Drupal\xhprof\XHProfLib\Report\ReportEngine
   */
  private $reportEngine;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('xhprof.profiler'),
      $container->get('xhprof.report_engine')
    );
  }

  /**
   * @param \Drupal\xhprof\ProfilerInterface $profiler
   * @param \Drupal\xhprof\XHProfLib\Report\ReportEngine $reportEngine
   */
  public function __construct(ProfilerInterface $profiler, ReportEngine $reportEngine) {
    $this->profiler = $profiler;
    $this->reportEngine = $reportEngine;
  }

  /**
   *
   */
  public function runsAction() {
    $runs = $run = $this->profiler->getStorage()->getRuns();

    // Table attributes
    $attributes = array('id' => 'xhprof-runs-table');

    // Table header
    $header = array();
    $header[] = array('data' => t('View'));
    $header[] = array('data' => t('Path'), 'field' => 'path');
    $header[] = array('data' => t('Date'), 'field' => 'date', 'sort' => 'desc');

    // Table rows
    $rows = array();
    foreach ($runs as $run) {
      $row = array();
      $row[] = $this->l($run['run_id'], new Url('xhprof.run', array('run' => $run['run_id'])));
      $row[] = isset($run['path']) ? $run['path'] : '';
      $row[] = format_date($run['date'], 'small');
      $rows[] = $row;
    }

    $build['table'] = array(
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => $attributes
    );

    return $build;
  }

  /**
   * @param \Drupal\xhprof\XHProfLib\Run $run
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return string
   */
  public function runAction(Run $run, Request $request) {
    $length = $request->get('length', 100);
    $sort = $request->get('sort', 'wt');

    $report = $this->reportEngine->getReport(NULL, NULL, $run, NULL, NULL, $sort, NULL, NULL);

    $build['#title'] = $this->t('XHProf view report for %id', array('%id' => $run->getId()));

    $descriptions = ReportConstants::getDescriptions();

    $build['summary'] = array(
      'title' => array(
        '#type' => 'inline_template',
        '#template' => '<h3>Summary</h3>',
      ),
      'table' => array(
        '#theme' => 'table',
        '#header' => array(),
        '#rows' => $this->getSummaryRows($report, $descriptions),
      )
    );

    $build['length'] = array(
      '#type' => 'inline_template',
      '#template' => ($length == -1) ? '<h3>Displaying all functions, sorted by {{ sort }}.</h3>' : '<h3>Displaying top {{ length }} functions, sorted by {{ sort }}. [{{ all }}]</h3>',
      '#context' => array(
        'length' => $length,
        'all' => $this->l($this->t('show all'), new Url('xhprof.run', array(
          'run' => $run->getId(),
          'length' => -1
        ))),
        'sort' => Xss::filter($descriptions[$sort], array()),
      ),
    );

    $build['table'] = array(
      '#theme' => 'table',
      '#header' => $this->getRunHeader($report, $descriptions, $run->getId()),
      '#rows' => $this->getRunRows($report, $length),
      '#attributes' => array('class' => array('responsive')),
      '#attached' => array(
        'library' => array(
          'xhprof/xhprof',
        ),
      ),
    );

    return $build;
  }

  /**
   * @param \Drupal\xhprof\XHProfLib\Run $run1
   * @param \Drupal\xhprof\XHProfLib\Run $run2
   *
   * @return string
   */
  public function diffAction(Run $run1, Run $run2) {
    return ''; //xhprof_display_run(array($run1, $run2), $symbol = NULL);
  }

  /**
   * @param \Drupal\xhprof\XHProfLib\Run $run
   * @param $symbol
   *
   * @return string
   */
  public function symbolAction(Run $run, $symbol) {
    return ''; //xhprof_display_run(array($run_id), $symbol);
  }

  /**
   * @param string $class
   *
   * @return string
   */
  private function abbrClass($class) {
    $parts = explode('\\', $class);
    $short = array_pop($parts);

    if (strlen($short) >= 40) {
      $short = substr($short, 0, 30) . " … " . substr($short, -5);
    }

    return new FormattableMarkup('<abbr title="@class">@short</abbr>', [
      '@class' => $class,
      '@short' => $short
    ]);
  }

  /**
   * @param \Drupal\xhprof\XHProfLib\Report\ReportInterface $report
   * @param array $descriptions
   *
   * @return array
   */
  private function getRunHeader(ReportInterface $report, $descriptions, $run_id) {
    $headers = array('fn', 'ct', 'ct_perc');

    $metrics = $report->getMetrics();

    foreach ($metrics as $metric) {
      $headers[] = $metric;
      $headers[] = $metric . '_perc';
      $headers[] = 'excl_' . $metric;
      $headers[] = 'excl_' . $metric . '_perc';
    }

    $sortable = ReportConstants::getSortableColumns();
    foreach ($headers as &$header) {
      if (isset($sortable[$header])) {
        $header = [
          'data' => Link::createFromRoute($descriptions[$header], 'xhprof.run', ['run' => $run_id], [
            'query' => [
              'sort' => $header,
            ],
          ])->toRenderable(),
        ];
      }
      else {
        $header = new FormattableMarkup($descriptions[$header], []);
      }
    }

    return $headers;
  }

  /**
   * @param \Drupal\xhprof\XHProfLib\Report\ReportInterface $report
   * @param int $length
   *
   * @return array
   */
  private function getRunRows(ReportInterface $report, $length) {
    $symbols = $report->getSymbols($length);

    foreach ($symbols as &$symbol) {
      $symbol[0] = $this->abbrClass($symbol[0]);
    }

    return $symbols;
  }

  /**
   * @param \Drupal\xhprof\XHProfLib\Report\ReportInterface $report
   * @param array $descriptions
   *
   * @return array
   */
  private function getSummaryRows(ReportInterface $report, $descriptions) {
    $summaryRows = array();
    $possibileMetrics = $report->getPossibleMetrics();
    foreach ($report->getSummary() as $metric => $value) {
      $key = 'Total ' . Xss::filter($descriptions[$metric], array());
      $unit = isset($possibileMetrics[$metric]) ? $possibileMetrics[$metric][1] : '';

      $value = new FormattableMarkup('@value @unit', [
        '@value' => $value,
        '@unit' => $unit
      ]);

      $summaryRows[] = array(
        $key,
        $value,
      );
    }

    return $summaryRows;
  }
}
