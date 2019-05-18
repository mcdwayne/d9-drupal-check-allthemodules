<?php

namespace Drupal\og_sm_content\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\node\NodeInterface;
use Drupal\og\OgAccessInterface;
use Drupal\og_sm\SiteTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Site content routes.
 */
class SiteContentController extends ControllerBase {

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The site type manager service.
   *
   * @var \Drupal\og_sm\SiteTypeManagerInterface
   */
  protected $siteTypeManager;

  /**
   * The OG access service.
   *
   * @var \Drupal\og\OgAccessInterface
   */
  protected $ogAccess;

  /**
   * Constructs a NodeController object.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\og_sm\SiteTypeManagerInterface $site_type_manager
   *   The site type manager service.
   * @param \Drupal\og\OgAccessInterface $og_access
   *   The OG access service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(RendererInterface $renderer, SiteTypeManagerInterface $site_type_manager, OgAccessInterface $og_access, EntityTypeManagerInterface $entity_type_manager) {
    $this->renderer = $renderer;
    $this->siteTypeManager = $site_type_manager;
    $this->ogAccess = $og_access;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer'),
      $container->get('og_sm.site_type_manager'),
      $container->get('og.access'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Displays add content links for available site content types.
   *
   * Redirects to /group/{entity_type_id}/{node}/content/add/[type] if only one
   * content type is available.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The site node.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   A render array for a list of the node types that can be added; however,
   *   if there is only one node type defined for the site, the function
   *   will return a RedirectResponse to the node add page for that one node
   *   type.
   */
  public function addPage(NodeInterface $node) {
    $build = [
      '#theme' => 'node_add_list__og_sm_site',
      '#cache' => [
        'tags' => $this->entityTypeManager->getDefinition('node_type')->getListCacheTags() + ['config:node_type_list'],
      ],
    ];

    $content = [];
    // Only use node types the user has access to.
    foreach ($this->siteTypeManager->getContentTypes() as $type) {
      $access = $this->ogAccess->userAccess($node, "create {$type->id()} content");
      if ($access->isAllowed()) {
        $content[$type->id()] = $type;
      }
      $this->renderer->addCacheableDependency($build, $access);
    }

    // Bypass the node/add listing if only one content type is available.
    if (count($content) == 1) {
      $type = array_shift($content);
      return $this->redirect('og_sm.site_content.add', [
        'node' => $node->id(),
        'node_type' => $type->id(),
      ]);
    }

    $build['#content'] = $content;

    return $build;
  }

}
