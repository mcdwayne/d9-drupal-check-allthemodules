<?php

namespace Drupal\hidden_tab\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Menu\LocalActionManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\hidden_tab\Plugable\Komponent\HiddenTabKomponentPluginManager;
use Drupal\hidden_tab\Utility;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a list of komponents to be added to the layout.
 *
 * Each page has a layout (defined by a template), and each layout contains
 * regions. Each region may contain komponents. This class list available
 * komponents to user, so that she can put them into regions.
 *
 * <b>THIS MODULE</b> is adopted from core's <em>block</em> module.
 *
 * @see \Drupal\block\Controller\BlockLibraryController
 * @see \Drupal\hidden_tab\Controller\PlacementAddController
 */
class KomponentLibraryController extends ControllerBase {

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   *
   * @see \Drupal\hidden_tab\Controller\KomponentLibraryController::buildLocalActions()
   */
  protected $routeMatch;

  /**
   * The local action manager.
   *
   * @var \Drupal\Core\Menu\LocalActionManagerInterface
   *
   * @see \Drupal\hidden_tab\Controller\KomponentLibraryController::buildLocalActions()
   */
  protected $localActionManager;

  /**
   * To find komponents.
   *
   * @var \Drupal\hidden_tab\Plugable\Komponent\HiddenTabKomponentPluginManager
   */
  protected $komponentMan;

  /***
   * {@inheritdoc}
   */
  public function __construct(RouteMatchInterface $route_match,
                              LocalActionManagerInterface $local_action_manager,
                              HiddenTabKomponentPluginManager $komponent_man) {
    $this->routeMatch = $route_match;
    $this->localActionManager = $local_action_manager;
    $this->komponentMan = $komponent_man;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @noinspection PhpParamsInspection */
    return new static(
      $container->get('current_route_match'),
      $container->get('plugin.manager.menu.local_action'),
      $container->get('plugin.manager.hidden_tab_komponent')
    );
  }

  /**
   * Shows a list of components that can be added to a layout.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return array
   *   A render array as expected by the renderer.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function listKomponents(Request $request): array {
    /** @var \Drupal\hidden_tab\Plugable\Komponent\HiddenTabKomponentInterface $komponent_plugin */
    $headers = [
      ['data' => $this->t('Komponent')],
      ['data' => $this->t('Description')],
      ['data' => $this->t('Operations')],
    ];

    $region = $request->query->get('region');
    $weight = $request->query->get('weight');
    $page = $request->query->get('page');
    $rows = [];
    foreach ($this->komponentMan->plugins() as $komponent_plugin) {
      $row = [];
      $row['category']['data'] = $komponent_plugin->komponentTypeLabel();
      $row['title']['data'] = [
        '#type' => 'inline_template',
        '#template' => '<div class="block-filter-text-source">{{ label }}</div>',
        '#context' => [
          'label' => $komponent_plugin->description(),
        ],
      ];
      $links['add'] = [
        'title' => $this->t('Place component'),
        'url' => Url::fromRoute('hidden_tab.admin_add', [
          'target_hidden_tab_page' => $page,
          'region' => $region,
          'komponent_type' => $komponent_plugin->id(),
          'weight' => $weight ? $weight : 0,
          'lredirect' => Utility::lRedirect(),
        ], [
          'query' => Utility::lRedirect(),
        ]),
        'attributes' => [
          'class' => ['use-ajax'],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => Json::encode([
            'width' => 700,
          ]),
        ],
      ];
      if ($region) {
        $links['add']['query']['region'] = $region;
      }
      if (isset($weight)) {
        $links['add']['query']['weight'] = $weight;
      }
      if (isset($page)) {
        $links['add']['query']['target_hidden_tab_page'] = $page;
      }
      $row['operations']['data'] = [
        '#type' => 'operations',
        '#links' => $links,
      ];
      $rows[] = $row;
    }

    $build['#attached']['library'][] = 'block/drupal.block.admin';

    $build['filter'] = [
      '#type' => 'search',
      '#title' => $this->t('Filter'),
      '#title_display' => 'invisible',
      '#size' => 30,
      '#placeholder' => $this->t('Filter by name'),
      '#attributes' => [
        'class' => ['block-filter-text'],
        'data-element' => '.block-add-table',
        'title' => $this->t('Enter a part of the name to filter by.'),
      ],
    ];

    $build['blocks'] = [
      '#type' => 'table',
      '#header' => $headers,
      '#rows' => $rows,
      '#empty' => $this->t('No component available.'),
      '#attributes' => [
        'class' => ['block-add-table'],
      ],
    ];

    return $build;
  }

  /**
   * Builds the local actions for this listing.
   *
   * As done in block module.
   *
   * @return array
   *   An array of local actions for this listing.
   */
  protected function buildLocalActions(): array {
    $build = $this->localActionManager->getActionsForRoute($this->routeMatch->getRouteName());
    // Without this workaround, the action links will be rendered as <li> with
    // no wrapping <ul> element.
    if (!empty($build)) {
      $build['#prefix'] = '<ul class="action-links">';
      $build['#suffix'] = '</ul>';
    }
    return $build;
  }

}
