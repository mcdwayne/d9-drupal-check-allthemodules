<?php

namespace Drupal\ga_node_reports\Controller;

use Drupal\charts\Services\ChartsSettingsService;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Uuid\Php;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\ga_node_reports\Form\GaNodeReportStatistics;

/**
 * Charts Api Example.
 */
class GaNodeReportsCharts extends ControllerBase implements ContainerInjectionInterface {

  protected $chartSettings;
  protected $messenger;
  protected $uuidService;
  protected $routeMatch;

  /**
   * Construct.
   *
   * @param \Drupal\charts\Services\ChartsSettingsService $chartSettings
   *   Service ChartsSettings.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Service MessengerInterface.
   * @param \Drupal\Component\Uuid\Php $uuidService
   *   Service uuid.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The The route match service.
   */
  public function __construct(ChartsSettingsService $chartSettings, MessengerInterface $messenger, Php $uuidService, RouteMatchInterface $routeMatch) {
    $this->chartSettings = $chartSettings->getChartsSettings();
    $this->messenger = $messenger;
    $this->uuidService = $uuidService;
    $this->routeMatch = $routeMatch;
  }

  /**
   * Return charts.
   *
   * @return array
   *   Array to render.
   */
  public function render() {

    return [
      $this->filterForm(),
      $this->viewssessions(),
      $this->locationsource(),
      $this->trafficsource(),
      $this->browsersource(),
    ];
  }

  /**
   * Display a Views and Sessions.
   *
   * @return array
   *   Array to render.
   */
  public function viewssessions() {

    // Customize options here.
    $options = [
      'type' => $this->chartSettings['type'],
      'title' => $this->t('Pageviews and Sessions'),
      'yaxis_min' => '',
      'yaxis_max' => '',
      'three_dimensional' => FALSE,
      'title_position' => 'out',
      'legend_position' => 'right',
      'data_labels' => $this->chartSettings['data_labels'],
      'tooltips' => $this->chartSettings['tooltips'],
      'colors'   => $this->chartSettings['colors'],
      'min'   => $this->chartSettings['min'],
      'max'   => $this->chartSettings['max'],
      'yaxis_prefix'   => $this->chartSettings['yaxis_prefix'],
      'yaxis_suffix'   => $this->chartSettings['yaxis_suffix'],
      'data_markers'   => $this->chartSettings['data_markers'],
      'red_from'   => $this->chartSettings['red_from'],
      'red_to'   => $this->chartSettings['red_to'],
      'yellow_from'   => $this->chartSettings['yellow_from'],
      'yellow_to'   => $this->chartSettings['yellow_to'],
      'green_from'   => $this->chartSettings['green_from'],
      'green_to'   => $this->chartSettings['green_to'],
    ];

    $nid = $this->routeMatch->getParameter('node');
    // Get the node URL.
    $url = '/node/' . $nid;

    // Constructing the Analytics feed parameter.
    $start_date = isset($_GET['start_date']) ? strtotime($_GET['start_date']) : strtotime('-6 days');
    $end_date = isset($_GET['end_date']) ? strtotime($_GET['end_date']) : strtotime('now');

    // Pageviews Analytics report.
    $params = [
      'dimensions' => ['ga:date'],
      'metrics' => ['ga:pageviews', 'ga:sessions'],
      'start_date' => $start_date,
      'end_date' => $end_date,
      'sort_metric' => ['ga:date'],
      'filters' => 'ga:pagePath=~' . $url,
    ];
    $data = google_analytics_reports_api_report_data($params);
    $results_array = (array) $data->results;

    // Chart details building.
    $chart_date = $chart_page = $chart_session = [];

    foreach ($results_array['rows'] as $records) {
      $chart_date[] = date('d-m-y', $records['date']);
      $chart_page[] = $records['pageviews'];
      $chart_session[] = $records['sessions'];
    }

    // Sample data format.
    $categories = $chart_date;

    $seriesData[] = [
      'name' => $this->t('Sessions'),
      'color' => '#0d233a',
      'type' => $this->chartSettings['type'],
      'data' => array_map('intval', $chart_session),
    ];

    $seriesData[] = [
      'name' => $this->t('PageViews'),
      'color' => '#8bbc21',
      'type' => $this->chartSettings['type'],
      'data' => array_map('intval', $chart_page),
    ];

    // Creates a UUID for the chart ID.
    $chartId = 'chart-' . $this->uuidService->generate();

    $build = [
      '#theme' => 'ga_node_reports',
      '#library' => 'google',
      '#categories' => $categories,
      '#seriesData' => $seriesData,
      '#options' => $options,
      '#id' => $chartId,
      '#override' => [],
    ];

    return $build;
  }

  /**
   * Display a Traffic Source.
   *
   * @return array
   *   Array to render.
   */
  public function trafficsource() {

    // Customize options here.
    $options = [
      'type' => 'pie',
      'title' => $this->t('Traffic source'),
      'yaxis_min' => '',
      'yaxis_max' => '',
      'three_dimensional' => TRUE,
      'title_position' => 'out',
      'legend_position' => 'right',
      'data_labels' => $this->chartSettings['data_labels'],
      'tooltips' => $this->chartSettings['tooltips'],
      'colors'   => $this->chartSettings['colors'],
      'min'   => $this->chartSettings['min'],
      'max'   => $this->chartSettings['max'],
      'yaxis_prefix'   => $this->chartSettings['yaxis_prefix'],
      'yaxis_suffix'   => $this->chartSettings['yaxis_suffix'],
      'data_markers'   => $this->chartSettings['data_markers'],
      'red_from'   => $this->chartSettings['red_from'],
      'red_to'   => $this->chartSettings['red_to'],
      'yellow_from'   => $this->chartSettings['yellow_from'],
      'yellow_to'   => $this->chartSettings['yellow_to'],
      'green_from'   => $this->chartSettings['green_from'],
      'green_to'   => $this->chartSettings['green_to'],
    ];

    $nid = $this->routeMatch->getParameter('node');
    // Get the node URL.
    $url = '/node/' . $nid;

    // Constructing the Analytics feed parameter.
    $start_date = isset($_GET['start_date']) ? strtotime($_GET['start_date']) : strtotime('-6 days');
    $end_date = isset($_GET['end_date']) ? strtotime($_GET['end_date']) : strtotime('now');

    // Traffic Source Analytics report.
    $params = [
      'dimensions' => ['ga:source'],
      'metrics' => ['ga:pageviews'],
      'start_date' => $start_date,
      'end_date' => $end_date,
      'sort_metric' => ['ga:source'],
      'filters' => 'ga:pagePath=~' . $url,
    ];
    $data = google_analytics_reports_api_report_data($params);
    $results_array = (array) $data->results;

    // Chart details building.
    $chart_page = $chart_source = [];

    foreach ($results_array['rows'] as $records) {
      $chart_source[] = $records['source'];
      $chart_page[] = $records['pageviews'];
    }

    // Sample data format.
    $categories = $chart_source;

    $seriesData[] = [
      'name' => 'Traffic Source',
      'color' => '#8bbc21',
      'type' => 'column',
      'data' => array_map('intval', $chart_page),
    ];

    // Creates a UUID for the chart ID.
    $chartId = 'chart-' . $this->uuidService->generate();

    $build = [
      '#theme' => 'ga_node_reports',
      '#library' => 'google',
      '#categories' => $categories,
      '#seriesData' => $seriesData,
      '#options' => $options,
      '#id' => $chartId,
      '#override' => [],
    ];

    return $build;
  }

  /**
   * Display a Location Source.
   *
   * @return array
   *   Array to render.
   */
  public function locationsource() {

    // Customize options here.
    $options = [
      'type' => 'geo',
      'title' => $this->t('Location source'),
      'yaxis_min' => '',
      'yaxis_max' => '',
      'three_dimensional' => FALSE,
      'title_position' => 'out',
      'legend_position' => 'right',
      'data_labels' => $this->chartSettings['data_labels'],
      'tooltips' => $this->chartSettings['tooltips'],
      'colors'   => $this->chartSettings['colors'],
      'min'   => $this->chartSettings['min'],
      'max'   => $this->chartSettings['max'],
      'yaxis_prefix'   => $this->chartSettings['yaxis_prefix'],
      'yaxis_suffix'   => $this->chartSettings['yaxis_suffix'],
      'data_markers'   => $this->chartSettings['data_markers'],
      'red_from'   => $this->chartSettings['red_from'],
      'red_to'   => $this->chartSettings['red_to'],
      'yellow_from'   => $this->chartSettings['yellow_from'],
      'yellow_to'   => $this->chartSettings['yellow_to'],
      'green_from'   => $this->chartSettings['green_from'],
      'green_to'   => $this->chartSettings['green_to'],
    ];

    $nid = $this->routeMatch->getParameter('node');
    // Get the node URL.
    $url = '/node/' . $nid;

    // Constructing the Analytics feed parameter.
    $start_date = isset($_GET['start_date']) ? strtotime($_GET['start_date']) : strtotime('-6 days');
    $end_date = isset($_GET['end_date']) ? strtotime($_GET['end_date']) : strtotime('now');

    // Location source Analytics report.
    $params = [
      'dimensions' => ['ga:country'],
      'metrics' => ['ga:pageviews'],
      'start_date' => $start_date,
      'end_date' => $end_date,
      'sort_metric' => ['ga:country'],
      'filters' => 'ga:pagePath=~' . $url,
    ];
    $data = google_analytics_reports_api_report_data($params);
    $results_array = (array) $data->results;

    // Chart details building.
    $chart_page = $chart_country = [];

    foreach ($results_array['rows'] as $records) {
      $chart_country[] = $records['country'];
      $chart_page[] = $records['pageviews'];
    }

    // Sample data format.
    $categories = $chart_country;

    $seriesData[] = [
      'name' => $this->t('PageViews'),
      'color' => '#8bbc21',
      'type' => 'column',
      'data' => array_map('intval', $chart_page),
    ];

    // Creates a UUID for the chart ID.
    $chartId = 'chart-' . $this->uuidService->generate();

    $build = [
      '#theme' => 'ga_node_reports',
      '#library' => 'google',
      '#categories' => $categories,
      '#seriesData' => $seriesData,
      '#options' => $options,
      '#id' => $chartId,
      '#override' => [],
    ];

    return $build;
  }

  /**
   * Display a Browser Source.
   *
   * @return array
   *   Array to render.
   */
  public function browsersource() {

    // Customize options here.
    $options = [
      'type' => 'donut',
      'title' => $this->t('Browser source'),
      'yaxis_min' => '',
      'yaxis_max' => '',
      'three_dimensional' => FALSE,
      'title_position' => 'out',
      'legend_position' => 'right',
      'data_labels' => $this->chartSettings['data_labels'],
      'tooltips' => $this->chartSettings['tooltips'],
      'colors'   => $this->chartSettings['colors'],
      'min'   => $this->chartSettings['min'],
      'max'   => $this->chartSettings['max'],
      'yaxis_prefix'   => $this->chartSettings['yaxis_prefix'],
      'yaxis_suffix'   => $this->chartSettings['yaxis_suffix'],
      'data_markers'   => $this->chartSettings['data_markers'],
      'red_from'   => $this->chartSettings['red_from'],
      'red_to'   => $this->chartSettings['red_to'],
      'yellow_from'   => $this->chartSettings['yellow_from'],
      'yellow_to'   => $this->chartSettings['yellow_to'],
      'green_from'   => $this->chartSettings['green_from'],
      'green_to'   => $this->chartSettings['green_to'],
    ];

    $nid = $this->routeMatch->getParameter('node');
    // Get the node URL.
    $url = '/node/' . $nid;

    // Constructing the Analytics feed parameter.
    $start_date = isset($_GET['start_date']) ? strtotime($_GET['start_date']) : strtotime('-6 days');
    $end_date = isset($_GET['end_date']) ? strtotime($_GET['end_date']) : strtotime('now');

    // Location source Analytics report.
    $params = [
      'dimensions' => ['ga:browser'],
      'metrics' => ['ga:pageviews'],
      'start_date' => $start_date,
      'end_date' => $end_date,
      'sort_metric' => ['ga:browser'],
      'filters' => 'ga:pagePath=~' . $url,
    ];
    $data = google_analytics_reports_api_report_data($params);
    $results_array = (array) $data->results;

    // Chart details building.
    $chart_page = $chart_browser = [];

    foreach ($results_array['rows'] as $records) {
      $chart_browser[] = $records['browser'];
      $chart_page[] = $records['pageviews'];
    }

    // Sample data format.
    $categories = $chart_browser;

    $seriesData[] = [
      'name' => 'Browser Source',
      'color' => '#8bbc21',
      'type' => 'column',
      'data' => array_map('intval', $chart_page),
    ];

    // Creates a UUID for the chart ID.
    $chartId = 'chart-' . $this->uuidService->generate();

    $build = [
      '#theme' => 'ga_node_reports',
      '#library' => 'google',
      '#categories' => $categories,
      '#seriesData' => $seriesData,
      '#options' => $options,
      '#id' => $chartId,
      '#override' => [],
    ];

    return $build;
  }

  /**
   * Return filter form.
   *
   * @return array
   *   Array to render.
   */
  public function filterForm() {

    $form = $this->formBuilder()->getForm(GaNodeReportStatistics::class);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('charts.settings'),
      $container->get('messenger'),
      $container->get('uuid'),
      $container->get('current_route_match')
    );
  }

}
