<?php

namespace Drupal\media_entity_file_redirect\Controller;

use Drupal\Core\Cache\CacheableRedirectResponse;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Render\RenderContext;
use Drupal\media\MediaInterface;
use Drupal\media\Plugin\media\Source\File;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class MediaEntityFileRedirectController.
 */
class MediaEntityFileRedirectController implements ContainerInjectionInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManager $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity_type.manager'));
  }

  /**
   * Returns a redirect to file source associated with passed in media entity.
   */
  public function redirectToFile(MediaInterface $media) {
    /** @var \Drupal\media\Entity\MediaType $mediaType */
    $mediaType = $this
      ->entityTypeManager
      ->getStorage('media_type')
      ->load($media->bundle());

    // Make sure this entity uses a file source and that the download route
    // is enabled for it.
    if ($mediaType && $mediaType->getSource() instanceof File && $mediaType->getThirdPartySetting('media_entity_file_redirect', 'enabled', FALSE)) {
      // Now load the file and return a redirect response to the file URL.
      $fid = $media->getSource()->getSourceFieldValue($media);
      if ($fid) {
        /** @var \Drupal\file\FileInterface $file */
        $file = $this->entityTypeManager->getStorage('file')->load($fid);
        if ($file) {

          // Since file_create_url generates bubbleable cache metadata, we need
          // to capture it in a render context so we can add it to our response.
          // Otherwise we'll get a LogicException.
          // See https://drupal.stackexchange.com/questions/273579
          $context = new RenderContext();
          $url = \Drupal::service('renderer')->executeInRenderContext($context, function () use ($file) {
            return file_create_url($file->getFileUri());
          });

          $response = new CacheableRedirectResponse($url);
          $response->addCacheableDependency($mediaType);
          $response->addCacheableDependency($media);
          $response->addCacheableDependency($file);
          if (!$context->isEmpty()) {
            $metadata = $context->pop();
            $response->addCacheableDependency($metadata);
          }

          return $response;
        }
      }
    }

    throw new NotFoundHttpException();
  }

}
