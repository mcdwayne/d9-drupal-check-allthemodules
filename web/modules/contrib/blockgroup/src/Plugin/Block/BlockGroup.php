<?php

namespace Drupal\blockgroup\Plugin\Block;

use Drupal\block\Entity\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityRepository;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Block\MainContentBlockPluginInterface;

/**
 * Provides a 'BlockGroup' block.
 *
 * @Block(
 *  id = "block_group",
 *  admin_label = @Translation("Block group"),
 *  deriver = "Drupal\blockgroup\Plugin\Derivative\BlockGroups"
 * )
 */
class BlockGroup extends BlockBase implements ContainerFactoryPluginInterface, MainContentBlockPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * The entity renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The render array representing the main page content.
   *
   * @var array
   */
  protected $mainContent;

  /**
   * {@inheritdoc}
   */
  public function setMainContent(array $main_content) {
    $this->mainContent = $main_content;
  }

  /**
   * Constructs a new BlockContentBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   The theme manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, ThemeManagerInterface $theme_manager, RendererInterface $renderer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->themeManager = $theme_manager;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('theme.manager'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function build() {
    $blockGroupStorage = $this->entityTypeManager->getStorage('block_group_content');

    $renderedBlocks = [];
    $derivativeId = $this->getDerivativeId();
    /** @var \Drupal\blockgroup\BlockGroupContentInterface $blockGroup */
    $blockGroup = $blockGroupStorage->load($derivativeId);
    /** @var \Drupal\block\BlockInterface[] $blocks */
    $blocks = $this->entityTypeManager
      ->getStorage('block')
      ->loadByProperties([
        'theme'  => $this->themeManager->getActiveTheme()->getName(),
        'region' => $blockGroup->id(),
      ]);
    uasort($blocks, 'Drupal\block\Entity\Block::sort');
    foreach ($blocks as $block) {
      // Special condition for Main block.
      if ($block->getPluginId() == 'system_main_block') {
        $renderedBlocks[$block->id()] = $this->mainContent;
      }
      // Special condition for Title block.
      elseif ($block->getPluginId() == 'page_title_block') {
        $request = \Drupal::request();
        $routeMatch = \Drupal::routeMatch();
        $title = \Drupal::service('title_resolver')
          ->getTitle($request, $routeMatch->getRouteObject());
        $renderedBlocks[$block->id()] = [
          '#type'  => 'page_title',
          '#title' => $title,
          '#weight' => $block->getWeight(),
        ];
      }
      // Any other block.
      else {
        /** @var \Drupal\Core\Access\AccessResultInterface $accessResult */
        $accessResult = $block->access('view', NULL, TRUE);
        if ($accessResult->isAllowed()) {
          $renderedBlocks[$block->id()] = $this->entityTypeManager
            ->getViewBuilder('block')
            ->view($block);
        }
        $this->renderer->addCacheableDependency($renderedBlocks, $accessResult);
      }
      $this->renderer->addCacheableDependency($renderedBlocks, $block);
    }
    $this->renderer->addCacheableDependency($renderedBlocks, $blockGroup);

    $blockDefinition = $this->entityTypeManager->getDefinition('block');
    $listCacheability = new CacheableMetadata();
    $listCacheability->addCacheTags($blockDefinition->getListCacheTags());
    $listCacheability->addCacheContexts($blockDefinition->getListCacheContexts());
    $this->renderer->addCacheableDependency($renderedBlocks, $listCacheability);

    return $renderedBlocks;
  }

}
