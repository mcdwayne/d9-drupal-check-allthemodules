<?php

namespace Drupal\lightbox_campaigns\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller routines for the Lightbox Campaigns module.
 */
class LightboxCampaignsController extends ControllerBase {

  /**
   * Entity type manager interface.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Renderer interface.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('renderer')
    );
  }

  /**
   * Constructs a new LightboxCampaignsController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager interface.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   Renderer interface.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, RendererInterface $renderer) {
    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  protected function getModuleName() {
    return 'lightbox_campaigns';
  }

  /**
   * Callback for the content of a single lightbox campaign entity.
   *
   * This returns rendered HTML directly so the Featherweight JS library can
   * create the lightbox on the fly. This prevents the lightbox content from
   * loading when it is not needed (and potentially causing page cache issues).
   *
   * @see lightbox_campaigns_page_attachments()
   */
  public function lightboxContentCallback($lightbox_campaign) {
    $render_array = $this->entityTypeManager
      ->getViewBuilder('lightbox_campaign')
      ->view($lightbox_campaign);
    return new Response($this->renderer->renderRoot($render_array));
  }

}
