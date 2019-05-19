<?php

namespace Drupal\ultimenu\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\ultimenu\UltimenuManagerInterface;
use Drupal\ultimenu\Ajax\UltimenuHtmlCommand;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides controller for Ultimenu region route.
 */
class UltimenuController extends ControllerBase {

  /**
   * The context handler.
   *
   * @var \Drupal\Core\Plugin\Context\ContextHandlerInterface
   */
  protected $contextHandler;

  /**
   * The context repository interface.
   *
   * @var \Drupal\Core\Plugin\Context\ContextRepositoryInterface
   */
  protected $contextRepository;

  /**
   * The menu link manager interface.
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface
   */
  protected $menuLinkManager;

  /**
   * The Ultimenu manager service.
   *
   * @var \Drupal\ultimenu\UltimenuManagerInterface
   */
  protected $ultimenuManager;

  /**
   * The default front page.
   *
   * @var string
   */
  protected $frontPage;

  /**
   * The current page path.
   *
   * @var string
   */
  protected $currentPath;

  /**
   * Constructs a new DefaultController object.
   */
  public function __construct(ContextHandlerInterface $context_handler, ContextRepositoryInterface $context_repository, MenuLinkManagerInterface $menu_link_manager, UltimenuManagerInterface $ultimenu_manager) {
    $this->contextHandler = $context_handler;
    $this->contextRepository = $context_repository;
    $this->menuLinkManager = $menu_link_manager;
    $this->ultimenuManager = $ultimenu_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('context.handler'),
      $container->get('context.repository'),
      $container->get('plugin.manager.menu.link'),
      $container->get('ultimenu.manager')
    );
  }

  /**
   * Loads and renders a region via AJAX.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   *
   * @return array
   *   Return the requested region based on the given region parameters.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *
   * @see http://symfony.com/doc/current/routing.html#required-and-optional-placeholders
   */
  public function load(Request $request) {
    $mlid = $request->query->get('mlid');
    $sub = $request->query->get('sub');

    if (isset($mlid) && $regions = $this->ultimenuManager->getSetting('regions')) {

      // Creates the menu link instance.
      $link = $this->menuLinkManager->createInstance($mlid);
      if (!$link || !$link->isEnabled()) {
        throw new NotFoundHttpException();
      }

      // Creates Ultimenu region response.
      $response = new AjaxResponse();
      $rid = $this->ultimenuManager->getTool()->getRegionKey($link);

      // Only proceeds if we have a valid/ enabled region.
      if (!empty($regions[$rid])) {
        $menu_name = $link->getMenuName();
        $plugin_id = 'ultimenu_block:ultimenu-' . $menu_name;
        $block = $this->ultimenuManager->getEntityTypeManager()->getStorage('block')->loadByProperties(['plugin' => $plugin_id]);
        $block = reset($block);
        $config = $block->get('settings');
        $config['current_path'] = $this->getCurrentPath($request);
        $config['has_submenu'] = $sub;
        $config['menu_name'] = $menu_name;
        $config['mlid'] = $mlid;
        $config['title'] = $this->ultimenuManager->getTool()->getTitle($link);

        // Creates render context.
        $context = new RenderContext();

        // Builds the flyout.
        $render = $this->ultimenuManager->getRenderer()->executeInRenderContext($context, function () use ($rid, $config) {
          return $this->ultimenuManager->buildFlyout($rid, $config);
        });

        // Prevents empty render from screwing up the response. Hence the
        // region is provided, but none of the blocks is given:
        // The render array has not yet been rendered, hence not all
        // attachments have been collected yet.
        if ($render) {
          // Add metadata.
          if (!$context->isEmpty()) {
            $bubbleable_metadata = $context->pop();
            BubbleableMetadata::createFromRenderArray($render)
              ->merge($bubbleable_metadata)
              ->applyTo($render);
          }

          $response->addCommand(new UltimenuHtmlCommand('[data-ultiajax-region="' . $rid . '"]', $render, NULL, 'region'));
        }
        return $response;
      }
      throw new AccessDeniedHttpException();
    }
    throw new NotFoundHttpException();
  }

  /**
   * Gets the current front page path.
   *
   * @return string
   *   The front page path.
   */
  protected function getFrontPagePath() {
    // Lazy-load front page config.
    if (!isset($this->frontPage)) {
      $this->frontPage = $this->ultimenuManager->getConfig('system.site')
        ->get('page.front');
    }
    return $this->frontPage;
  }

  /**
   * Gets the current page path.
   *
   * @return string
   *   The current page path.
   */
  protected function getCurrentPath(Request $request) {
    if (!isset($this->currentPath)) {
      $referer = $request->headers->get('Referer', '');
      $url = parse_url($referer);
      $this->currentPath = $url['path'] == '/' ? $this->getFrontPagePath() : $url['path'];
    }
    return $this->currentPath;
  }

}
