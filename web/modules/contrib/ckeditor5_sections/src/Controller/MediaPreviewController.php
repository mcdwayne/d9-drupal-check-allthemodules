<?php

namespace Drupal\ckeditor5_sections\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\media\MediaInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

class MediaPreviewController extends ControllerBase {

  /**
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static ($container->get('renderer'), $container->get('entity.repository'));
  }

  /**
   * MediaPreviewController constructor.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entityRepository
   */
  public function __construct(RendererInterface $renderer, EntityRepositoryInterface $entityRepository) {
    $this->entityRepository = $entityRepository;
    $this->renderer = $renderer;
  }

  /**
   * Media preview callback.
   *
   * @param string $uuid
   *   UUID of the media entity.
   * @param string $display
   *   The display to use.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  function preview($uuid, $display) {
    $media = $this->entityRepository->loadEntityByUuid('media', $uuid);
    if (!$media) {
      return;
    }
    $build = $this->entityTypeManager()->getViewBuilder('media')->view($media, $display ?? 'default');
    $response = new Response();
    $response->setContent($this->renderer->render($build));
    return $response;
  }

}
