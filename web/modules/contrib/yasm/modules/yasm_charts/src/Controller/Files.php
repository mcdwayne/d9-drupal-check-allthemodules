<?php

namespace Drupal\yasm_charts\Controller;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\yasm\Controller\Files as BaseController;
use Drupal\yasm\Services\DatatablesInterface;
use Drupal\yasm\Services\EntitiesStatisticsInterface;
use Drupal\yasm_charts\Services\YasmChartsBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Appends charts to base controller.
 */
class Files extends BaseController {

  /**
   * The yasm charts builder.
   *
   * @var \Drupal\yasm_charts\Services\YasmChartsBuilderInterface
   */
  protected $yasmChartsBuilder;

  /**
   * {@inheritdoc}
   */
  public function siteContent(Request $request) {
    $build = parent::siteContent($request);

    return $this->yasmChartsBuilder->discoverCharts($build, [
      'files_status' => [
        [
          'title' => $this->t('By number'),
          'skip_right' => 1,
          'type' => 'pie',
          'options' => ['height' => 250],
        ],
        [
          'title' => $this->t('By size'),
          'skip_left' => 2,
          'type' => 'pie',
          'options' => ['height' => 250],
        ],
      ],
      'files_stream' => [
        [
          'title' => $this->t('By number'),
          'skip_right' => 1,
          'type' => 'pie',
          'options' => ['height' => 250],
        ],
        [
          'title' => $this->t('By size'),
          'skip_left' => 2,
          'type' => 'pie',
          'options' => ['height' => 250],
        ],
      ],
      'files_type' => [
        [
          'title' => $this->t('By number'),
          'skip_left' => 2,
          'skip_right' => 1,
          'label_position' => 2,
          'type' => 'pie',
          'options' => ['height' => 250],
        ],
        [
          'title' => $this->t('By size'),
          'skip_left' => 3,
          'label_position' => 2,
          'type' => 'pie',
          'options' => ['height' => 250],
        ],
      ],
      'files_monthly' => [
        'label' => $this->t('New files'),
        'skip_left' => 0,
        'type' => 'column',
        'options' => ['height' => 500],
      ],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(ModuleHandlerInterface $module_handler, StreamWrapperManagerInterface $stream_wrapper_manager, DatatablesInterface $datatables, EntitiesStatisticsInterface $entities_statistics, YasmChartsBuilderInterface $yasmChartsBuilder) {
    parent::__construct($module_handler, $stream_wrapper_manager, $datatables, $entities_statistics);

    $this->yasmChartsBuilder = $yasmChartsBuilder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler'),
      $container->get('stream_wrapper_manager'),
      $container->get('yasm.datatables'),
      $container->get('yasm.entities_statistics'),
      $container->get('yasm_charts.builder')
    );
  }

}
