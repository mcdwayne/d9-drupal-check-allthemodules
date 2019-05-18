<?php

namespace Drupal\global_gateway_ui\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Locale\CountryManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\global_gateway\DisabledRegionsProcessor;
use Drupal\global_gateway\Mapper\MapperPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class GlobalGatewayRegions.
 *
 * @package Drupal\global_gateway_ui\Controller
 */
class GlobalGatewayRegions extends ControllerBase {
  use StringTranslationTrait;

  /**
   * Country manager.
   *
   * @var \Drupal\Core\Locale\CountryManagerInterface
   */
  protected $countryManager;
  /**
   * Mapping manager.
   *
   * @var \Drupal\global_gateway\Mapper\MapperPluginManager
   */
  protected $mapperManager;
  /**
   * Disabled regions processor.
   *
   * @var \Drupal\global_gateway\DisabledRegionsProcessor
   */
  protected $disabledRegionsProcessor;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('country_manager'),
      $container->get('plugin.manager.global_gateway.mapper'),
      $container->get('global_gateway.disabled_regions.processor')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(
    CountryManagerInterface $countryManager,
    MapperPluginManager $mapperManager,
    DisabledRegionsProcessor $processor
  ) {
    $this->countryManager = $countryManager;
    $this->mapperManager = $mapperManager;
    $this->disabledRegionsProcessor = $processor;
  }

  /**
   * Builds regions overview page with search filter.
   */
  public function getRegionsPage(RouteMatchInterface $route_match) {

    $build['filters'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['table-filter', 'js-show'],
      ],
      '#weight' => -10,
    ];

    $build['filters']['text'] = [
      '#type' => 'search',
      '#title' => $this->t('Search'),
      '#size' => 30,
      '#placeholder' => 'Search',
      '#attributes' => [
        'class' => ['table-filter-text'],
        'data-table' => '.region-list',
        'autocomplete' => 'off',
        'title' => 'Filter by name',
      ],
    ];

    $build['#attached']['library'][] = 'system/drupal.system.modules';

    $disabled = $this->disabledRegionsProcessor->getDisabled();
    foreach ($this->countryManager->getList() as $region_code => $country) {
      $is_disabled = in_array(strtolower($region_code), $disabled);
      $operations = [
        'data' => [
          '#type' => 'operations',
          '#links' => [
            'mappings' => [
              'title' => $this->t('View'),
              'url' => new Url('global_gateway_ui.region', ['region_code' => strtolower($region_code)]),
            ],
          ],
        ],
      ];

      // Add "Enable"/"Disable" operations.
      if ($is_disabled) {
        $operations['data']['#links'][] = [
          'title' => $this->t('Enable'),
          'url' => new Url(
            'global_gateway_ui.region.enable',
            ['region_code' => strtolower($region_code)]
          ),
        ];
      }
      else {
        $operations['data']['#links'][] = [
          'title' => $this->t('Disable'),
          'url' => new Url(
            'global_gateway_ui.region.disable',
            ['region_code' => strtolower($region_code)]
          ),
        ];
      }

      $rows[] = [
        'region' => [
          'data' => $country . (!$is_disabled ? '' : ' (' . $this->t('disabled') . ')'),
          'class' => 'table-filter-text-source',
        ],
        'ops' => $operations,
      ];
    }

    $build['regions'] = [
      '#theme' => 'table',
      '#header' => [$this->t('Region'), $this->t('Operations')],
      '#rows' => $rows,
      '#attributes' => ['class' => ['region-list']],
    ];

    return $build;
  }

  /**
   * Builds region overview page.
   */
  public function getRegionPage(RouteMatchInterface $route_match, $region_code) {
    $mappers = $this->mapperManager->getInstances();

    $rows = [];
    $build = [];

    foreach ($mappers as $mapper) {
      $mapper->setRegion($region_code);
      $rows[] = [
        $mapper->getLabel(),
        $mapper->getOverviewByRegion(),
        $mapper->getOperationsLinks(),
      ];
    }

    if (!empty($rows)) {
      $build['rows'] = [
        '#theme' => 'table',
        '#header' => [
          $this->t('Locals'),
          $this->t('Overview'),
          $this->t('Operations'),
        ],
        '#rows' => $rows,
      ];
    }
    else {
      $build = [
        '#markup' => $this->t('There are no available global gateway mapping modules enabled.'),
      ];
    }

    return $build;
  }

  /**
   * Build a title for global_gateway admin routes.
   */
  public function getTitle(RouteMatchInterface $route_match, $region_code) {
    $list = $this->countryManager->getList();
    $region_code = strtoupper($region_code);

    return isset($list[$region_code]) ? $list[$region_code] : FALSE;
  }

}
