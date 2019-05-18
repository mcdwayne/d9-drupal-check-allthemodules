<?php

namespace Drupal\homebox\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Core\Menu\LocalActionManagerInterface;
use Drupal\Core\Plugin\Context\LazyContextRepository;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\homebox\Entity\HomeboxLayoutInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class HomeboxLayoutController.
 *
 *  Returns responses for Homebox Layout routes.
 */
class HomeboxLayoutController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Manager block plugin.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * The local action manager.
   *
   * @var \Drupal\Core\Menu\LocalActionManagerInterface
   */
  protected $localActionManager;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The context repository.
   *
   * @var \Drupal\Core\Plugin\Context\LazyContextRepository
   */
  protected $contextRepository;

  /**
   * The link generator.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface
   */
  protected $linkGenerator;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /* @var \Drupal\Core\Datetime\DateFormatterInterface $date_formatter */
    $date_formatter = $container->get('date.formatter');
    /* @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $container->get('renderer');
    /* @var \Drupal\Core\Block\BlockManagerInterface $block_manager */
    $block_manager = $container->get('plugin.manager.block');
    /* @var \Drupal\Core\Menu\LocalActionManagerInterface $local_action_manager */
    $local_action_manager = $container->get('plugin.manager.menu.local_action');
    /* @var \Drupal\Core\Routing\RouteMatchInterface $route_match */
    $route_match = $container->get('current_route_match');
    /* @var \Drupal\Core\Plugin\Context\LazyContextRepository $context_repository */
    $context_repository = $container->get('context.repository');
    /** @var \Drupal\Core\Utility\LinkGeneratorInterface $link_generator */
    $link_generator = $container->get('link_generator');
    return new static($date_formatter, $renderer, $block_manager, $local_action_manager, $route_match, $context_repository, $link_generator);
  }

  /**
   * HomeboxLayoutController constructor.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   Date formatter service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   Renderer service.
   * @param \Drupal\Core\Block\BlockManagerInterface $block_manager
   *   Manager block plugin.
   * @param \Drupal\Core\Menu\LocalActionManagerInterface $local_action_manager
   *   The local action manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\Core\Plugin\Context\LazyContextRepository $context_repository
   *   The context repository.
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $link_generator
   *   The link generator service.
   */
  public function __construct(DateFormatterInterface $date_formatter, RendererInterface $renderer, BlockManagerInterface $block_manager, LocalActionManagerInterface $local_action_manager, RouteMatchInterface $route_match, LazyContextRepository $context_repository, LinkGeneratorInterface $link_generator) {
    $this->dateFormatter = $date_formatter;
    $this->renderer = $renderer;
    $this->blockManager = $block_manager;
    $this->localActionManager = $local_action_manager;
    $this->routeMatch = $route_match;
    $this->contextRepository = $context_repository;
    $this->linkGenerator = $link_generator;
  }

  /**
   * Displays a Homebox Layout  revision.
   *
   * @param int $homebox_layout_revision
   *   The Homebox Layout  revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function revisionShow($homebox_layout_revision) {
    $homebox_layout = $this->entityTypeManager()->getStorage('homebox_layout')->loadRevision($homebox_layout_revision);
    $view_builder = $this->entityTypeManager()->getViewBuilder('homebox_layout');

    return $view_builder->view($homebox_layout);
  }

  /**
   * Page title callback for a Homebox Layout  revision.
   *
   * @param int $homebox_layout_revision
   *   The Homebox Layout  revision ID.
   *
   * @return string
   *   The page title.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function revisionPageTitle($homebox_layout_revision) {
    /* @var HomeboxLayoutInterface $homebox_layout */
    $homebox_layout = $this->entityTypeManager()->getStorage('homebox_layout')->loadRevision($homebox_layout_revision);
    return $this->t('Revision of %title from %date', ['%title' => $homebox_layout->label(), '%date' => $this->dateFormatter->format($homebox_layout->getRevisionCreationTime())]);
  }

  /**
   * Shows a list of blocks that can be added to a theme's layout.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param string $homebox
   *   Homebox id.
   *
   * @return array
   *   A render array as expected by the renderer.
   */
  public function listBlocks(Request $request, $homebox) {
    // Only add blocks which work without any available context.
    $definitions = $this->blockManager->getDefinitions();
    // Order by category, and then by admin label.
    $definitions = $this->blockManager->getSortedDefinitions($definitions);

    // Since modals do not render any other part of the page, we need to render
    // them manually as part of this listing.
    if ($request->query->get(MainContentViewSubscriber::WRAPPER_FORMAT) === 'drupal_modal') {
      $build['local_actions'] = $this->buildLocalActions();
    }

    $headers = [
      ['data' => $this->t('Block')],
      ['data' => $this->t('Category')],
      ['data' => $this->t('Operations')],
    ];

    $region = $request->query->get('region');
    $weight = $request->query->get('weight');
    $rows = [];
    foreach ($definitions as $plugin_id => $plugin_definition) {
      $row = [];
      $row['title']['data'] = [
        '#type' => 'inline_template',
        '#template' => '<div class="block-filter-text-source">{{ label }}</div>',
        '#context' => [
          'label' => $plugin_definition['admin_label'],
        ],
      ];
      $row['category']['data'] = $plugin_definition['category'];
      $links['add'] = [
        'title' => $this->t('Place block'),
        'url' => Url::fromRoute(
          'homebox.settings_form',
          ['homebox' => $homebox],
          ['query' => ['block-placement' => $plugin_id]]
        ),
      ];
      if ($region) {
        $links['add']['query']['region'] = $region;
      }
      if (isset($weight)) {
        $links['add']['query']['weight'] = $weight;
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
      '#placeholder' => $this->t('Filter by block name'),
      '#attributes' => [
        'class' => ['block-filter-text'],
        'data-element' => '.block-add-table',
        'title' => $this->t('Enter a part of the block name to filter by.'),
      ],
    ];

    $build['blocks'] = [
      '#type' => 'table',
      '#header' => $headers,
      '#rows' => $rows,
      '#empty' => $this->t('No blocks available.'),
      '#attributes' => [
        'class' => ['block-add-table'],
      ],
    ];

    return $build;
  }

  /**
   * Builds the local actions for this listing.
   *
   * @return array
   *   An array of local actions for this listing.
   */
  protected function buildLocalActions() {
    $build = $this->localActionManager->getActionsForRoute($this->routeMatch->getRouteName());
    // Without this workaround, the action links will be rendered as <li> with
    // no wrapping <ul> element.
    if (!empty($build)) {
      $build['#prefix'] = '<ul class="action-links">';
      $build['#suffix'] = '</ul>';
    }
    return $build;
  }

  /**
   * Generates an overview table of older revisions of a Homebox Layout .
   *
   * @param \Drupal\homebox\Entity\HomeboxLayoutInterface $homebox_layout
   *   A Homebox Layout  object.
   *
   * @return array
   *   An array as expected by drupal_render().
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function revisionOverview(HomeboxLayoutInterface $homebox_layout) {
    $account = $this->currentUser();
    $langcode = $homebox_layout->language()->getId();
    $langname = $homebox_layout->language()->getName();
    $languages = $homebox_layout->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    /* @var \Drupal\homebox\HomeboxLayoutStorageInterface $homebox_layout_storage */
    $homebox_layout_storage = $this->entityTypeManager()->getStorage('homebox_layout');

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $homebox_layout->label()]) : $this->t('Revisions for %title', ['%title' => $homebox_layout->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $revert_permission = $account->hasPermission('administer homebox');
    $delete_permission = $account->hasPermission('administer homebox');

    $rows = [];

    $vids = $homebox_layout_storage->revisionIds($homebox_layout);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\homebox\Entity\HomeboxLayoutInterface $revision */
      $revision = $homebox_layout_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode)/* && $revision->getTranslation($langcode)->isRevisionTranslationAffected()*/) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = $this->dateFormatter->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $homebox_layout->getRevisionId()) {
          $link = $this->linkGenerator->generate($date, new Url('entity.homebox_layout.revision', ['homebox_layout' => $homebox_layout->id(), 'homebox_layout_revision' => $vid]));
        }
        else {
          $link = $homebox_layout->toLink($date)->toString();
        }

        $row = [];
        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => $link,
              'username' => $this->renderer->renderPlain($username),
              'message' => ['#markup' => $revision->getRevisionLogMessage(), '#allowed_tags' => Xss::getHtmlTagList()],
            ],
          ],
        ];
        $row[] = $column;

        if ($latest_revision) {
          $row[] = [
            'data' => [
              '#prefix' => '<em>',
              '#markup' => $this->t('Current revision'),
              '#suffix' => '</em>',
            ],
          ];
          foreach ($row as &$current) {
            $current['class'] = ['revision-current'];
          }
          $latest_revision = FALSE;
        }
        else {
          $links = [];
          if ($revert_permission) {
            $links['revert'] = [
              'title' => $this->t('Revert'),
              'url' => $has_translations ?
              Url::fromRoute('entity.homebox_layout.translation_revert', ['homebox_layout' => $homebox_layout->id(), 'homebox_layout_revision' => $vid, 'langcode' => $langcode]) :
              Url::fromRoute('entity.homebox_layout.revision_revert', ['homebox_layout' => $homebox_layout->id(), 'homebox_layout_revision' => $vid]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.homebox_layout.revision_delete', ['homebox_layout' => $homebox_layout->id(), 'homebox_layout_revision' => $vid]),
            ];
          }

          $row[] = [
            'data' => [
              '#type' => 'operations',
              '#links' => $links,
            ],
          ];
        }

        $rows[] = $row;
      }
    }

    $build['homebox_layout_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
