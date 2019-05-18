<?php

namespace Drupal\dpl;

use Drupal\consumers\Entity\Consumer;
use Drupal\Core\Controller\ControllerBase;
use Drupal\dpl\Entity\DecoupledPreviewLink;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the controller for the preview page.
 */
class Controller extends ControllerBase {

  /**
   * The consumer preview links.
   *
   * @var \Drupal\dpl\DecoupledPreviewLinks
   */
  protected $decoupledPreviewLinks;

  /**
   * Controller constructor.
   *
   * @param \Drupal\dpl\DecoupledPreviewLinks $decoupledPreviewLinks
   *   The consumer preview links.
   */
  public function __construct(DecoupledPreviewLinks $decoupledPreviewLinks) {
    $this->decoupledPreviewLinks = $decoupledPreviewLinks;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('dpl.preview_links'));
  }

  /**
   * Controller for the preview page.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return array
   *   The render array.
   */
  public function preview(NodeInterface $node, DecoupledPreviewLink $decoupled_preview_link, Request $request) {
    $url = $request->query->get('url');
    return [
      '#title' => $this->t('@label for <em>@entityLabel</em>', [
        '@label' => $decoupled_preview_link->label(),
        '@entityLabel' => $node->label(),
      ]),
      '#theme' => 'dpl_preview',
      '#attached' => [
        'library' => [
          'dpl/preview',
        ],
      ],
      '#url' => $url,
      '#open_external_label' => $decoupled_preview_link->getOpenExternalLabel(),
      '#entity_url' => $request->query->get('entity_url'),
      '#sizes' => $decoupled_preview_link->toPreviewLinkInstance()->getBrowserSizes(),
      '#default_size' => $decoupled_preview_link->toPreviewLinkInstance()->getDefaultSize(),
    ];
  }

}
