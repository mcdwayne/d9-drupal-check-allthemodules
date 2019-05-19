<?php

namespace Drupal\siteinfo\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\siteinfo\Controller\SiteInformationController;

/**
 * Provides a 'Site Information' block.
 */
class SiteInformationBlock extends BlockBase {

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a DefaultController object.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(RendererInterface $renderer) {
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'access site information');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $siteinfo = SiteInformationController::siteInformation();
    $content = $this->renderer->render($siteinfo);
    return [
      '#type' => 'markup',
      '#markup' => $content,
    ];
  }

}
