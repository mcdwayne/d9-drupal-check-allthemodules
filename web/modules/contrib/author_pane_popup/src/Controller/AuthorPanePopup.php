<?php

namespace Drupal\author_pane_popup\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Path\AliasManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * AuthorPanePopup Class defines ajax callback function.
 */
class AuthorPanePopup extends ControllerBase {

  /**
   * The path alias manager.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('path.alias_manager')
    );
  }

  /**
   * Constructs a AliasManagerInterface object.
   *
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   *   The path alias manager.
   */
  public function __construct(AliasManagerInterface $alias_manager) {
    $this->aliasManager = $alias_manager;
  }

  /**
   * Callback function for ajax request.
   */
  public function getUserPane() {
    $alias = $this->aliasManager->getPathByAlias($_POST['url']);
    $alias = explode('/', $alias);
    $author_pane_popup_views = views_embed_view('author_pane_popup', 'default', $alias[2]);
    return $author_pane_popup_views;
  }

}
