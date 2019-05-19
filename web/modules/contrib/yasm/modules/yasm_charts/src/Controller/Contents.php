<?php

namespace Drupal\yasm_charts\Controller;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\yasm\Controller\Contents as BaseController;
use Drupal\yasm\Services\DatatablesInterface;
use Drupal\yasm\Services\EntitiesStatisticsInterface;
use Drupal\yasm_charts\Services\YasmChartsBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Appends charts to base controller.
 */
class Contents extends BaseController {

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
    $build = $this->buildContent($build);

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function myContent(Request $request) {
    $build = parent::myContent($request);
    $build = $this->buildContent($build);

    return $build;
  }

  /**
   * Build array content.
   */
  private function buildContent($build) {
    return $this->yasmChartsBuilder->discoverCharts($build, [
      'node_types' => [
        'skip_top' => 1,
        'type' => 'pie',
        'options' => ['height' => 500],
      ],
      'nodes_created_monthly' => [
        'skip_top' => 1,
        'type' => 'line',
        'options' => ['height' => 500],
      ],
      'nodes_updated_monthly' => [
        'skip_top' => 1,
        'type' => 'line',
        'options' => ['height' => 500],
      ],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(AccountInterface $current_user, DateFormatterInterface $date_formatter, EntityTypeManagerInterface $entityTypeManager, MessengerInterface $messenger, ModuleHandlerInterface $module_handler, DatatablesInterface $datatables, EntitiesStatisticsInterface $entities_statistics, YasmChartsBuilderInterface $yasmChartsBuilder) {
    parent::__construct($current_user, $date_formatter, $entityTypeManager, $messenger, $module_handler, $datatables, $entities_statistics);

    $this->yasmChartsBuilder = $yasmChartsBuilder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('date.formatter'),
      $container->get('entity_type.manager'),
      $container->get('messenger'),
      $container->get('module_handler'),
      $container->get('yasm.datatables'),
      $container->get('yasm.entities_statistics'),
      $container->get('yasm_charts.builder')
    );
  }

}
