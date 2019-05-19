<?php

namespace Drupal\yasm_charts\Controller;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\yasm\Controller\Users as BaseController;
use Drupal\yasm\Services\DatatablesInterface;
use Drupal\yasm\Services\EntitiesStatisticsInterface;
use Drupal\yasm_charts\Services\YasmChartsBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Appends charts to base controller.
 */
class Users extends BaseController {

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
      'users_role' => [
        'skip_right' => 1,
        'type' => 'pie',
        'options' => ['height' => 500],
      ],
      'users_status' => [
        'skip_right' => 1,
        'type' => 'pie',
        'options' => ['height' => 500],
      ],
      'users_access' => [
        'skip_left' => 2,
        'type' => 'column',
        'options' => ['height' => 500],
      ],
      'users_monthly' => [
        'label' => $this->t('New users'),
        'skip_left' => 0,
        'type' => 'column',
        'options' => ['height' => 500],
      ],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(DateFormatterInterface $dateFormatter, EntityTypeManagerInterface $entityTypeManager, MessengerInterface $messenger, ModuleHandlerInterface $module_handler, DatatablesInterface $datatables, EntitiesStatisticsInterface $entities_statistics, YasmChartsBuilderInterface $yasmChartsBuilder) {
    parent::__construct($dateFormatter, $entityTypeManager, $messenger, $module_handler, $datatables, $entities_statistics);

    $this->yasmChartsBuilder = $yasmChartsBuilder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
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
