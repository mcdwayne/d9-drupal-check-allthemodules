<?php

namespace Drupal\yasm_charts\Controller;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\yasm\Controller\Groups as BaseController;
use Drupal\yasm\Services\DatatablesInterface;
use Drupal\yasm\Services\GroupsStatisticsInterface;
use Drupal\yasm_charts\Services\YasmChartsBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Appends charts to base controller.
 */
class Groups extends BaseController {

  /**
   * The yasm charts builder.
   *
   * @var \Drupal\yasm_charts\Services\YasmChartsBuilderInterface
   */
  protected $yasmChartsBuilder;

  /**
   * {@inheritdoc}
   */
  public function siteContent() {
    $build = parent::siteContent();

    return $this->yasmChartsBuilder->discoverCharts($build, [
      'groups_contents' => [
        'skip_left' => 2,
        'skip_right' => 4,
        'label_position' => 2,
        'type' => 'pie',
        'options' => ['height' => 500],
      ],
      'groups_members' => [
        'skip_left' => 2,
        'skip_right' => 1,
        'label_position' => 2,
        'type' => 'pie',
        'options' => ['height' => 500],
      ],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function myContent() {
    $build = parent::myContent();

    return $this->yasmChartsBuilder->discoverCharts($build, [
      'my_groups_contents' => [
        'skip_top' => 1,
        'type' => 'pie',
        'options' => ['height' => 350],
      ],
      'my_groups_roles' => [
        'type' => 'column',
        'options' => ['height' => 350],
      ],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(AccountInterface $current_user, EntityTypeManagerInterface $entityTypeManager, MessengerInterface $messenger, ModuleHandlerInterface $module_handler, DatatablesInterface $datatables, GroupsStatisticsInterface $groups_statistics, YasmChartsBuilderInterface $yasmChartsBuilder) {
    parent::__construct($current_user, $entityTypeManager, $messenger, $module_handler, $datatables, $groups_statistics);

    $this->yasmChartsBuilder = $yasmChartsBuilder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('messenger'),
      $container->get('module_handler'),
      $container->get('yasm.datatables'),
      $container->get('yasm.groups_statistics'),
      $container->get('yasm_charts.builder')
    );
  }

}
