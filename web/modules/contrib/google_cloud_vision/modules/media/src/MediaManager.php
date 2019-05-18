<?php

namespace Drupal\google_cloud_vision_media;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\file\FileInterface;
use Drupal\google_cloud_vision\Model\AnnotateFeatures;
use Drupal\google_cloud_vision\Service\AnnotateServiceInterface;
use Drupal\image\ImageStyleInterface;
use Drupal\media\MediaInterface;
use Drupal\media\MediaTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

/**
 * Class MediaThumbnailManager.
 *
 * @package Drupal\google_cloud_vision_media
 */
class MediaManager implements ContainerInjectionInterface, MediaManagerInterface {

  /**
   * Allowed image extensions.
   */
  private const EXTENSIONS = [
    'jpg',
    'jpeg',
    'png',
    'gif',
  ];

  /**
   * Queue Factory.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  private $queueFactory;

  /**
   * Media Type Storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  private $mediaTypeStorage;

  /**
   * Image Style Storage.
   *
   * @var \Drupal\image\ImageStyleStorageInterface
   */
  private $imageStyleStorage;

  /**
   * Annotate Service.
   *
   * @var \Drupal\google_cloud_vision\Service\AnnotateServiceInterface
   */
  private $annotateService;

  /**
   * MediaManager constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity Type Manager.
   * @param \Drupal\Core\Queue\QueueFactory $queueFactory
   *   Queue Factory.
   * @param \Drupal\google_cloud_vision\Service\AnnotateServiceInterface $annotateService
   *   Annotate Service.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, QueueFactory $queueFactory, AnnotateServiceInterface $annotateService) {
    $this->queueFactory = $queueFactory;
    $this->mediaTypeStorage = $entityTypeManager->getStorage('media_type');
    $this->imageStyleStorage = $entityTypeManager->getStorage('image_style');
    $this->annotateService = $annotateService;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public static function create(ContainerInterface $container) {
    return new static (
      $container->get('entity_type.manager'),
      $container->get('queue'),
      $container->get('google_cloud_vision.annotate')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function queueAnnotation(MediaInterface $media) {
    $queue = $this->queueFactory->get('google_cloud_vision_media');
    $queue->createItem($media->id());
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Symfony\Component\Filesystem\Exception\FileNotFoundException
   * @throws \InvalidArgumentException
   */
  public function annotate(MediaInterface $media) {
    $files = $this->getMediaFiles($media);
    $mediaType = $this->getMediaType($media);
    $imageStyleId = $mediaType->getThirdPartySetting('google_cloud_vision_media', 'image_style', NULL);
    $imageStyle = NULL;

    if (!empty($imageStyleId)) {
      /** @var \Drupal\image\ImageStyleInterface $imageStyle */
      $imageStyle = $this->imageStyleStorage->load($imageStyleId);
    }

    $features = new AnnotateFeatures();
    $features->setLabelDetection(TRUE);

    foreach ($files as $file) {
      if (!$this->validateFile($file)) {
        continue;
      }

      $this->annotateService->addImagePath($file->id(), $this->getFileUri($file, $imageStyle), $features);
    }

    return $this->annotateService->annotate()
      ->getAnnotations();
  }

  /**
   * {@inheritdoc}
   *
   * @throws \InvalidArgumentException
   */
  public function getMediaFiles(MediaInterface $media) {
    $mediaType = $this->getMediaType($media);
    $fieldName = $this->getSourceFieldName($mediaType);
    /** @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $fieldListItem */
    $fieldListItem = $media->get($fieldName);

    return $fieldListItem->referencedEntities();
  }

  /**
   * Get the Media Type.
   *
   * @param \Drupal\media\MediaInterface $media
   *   Media Entity.
   *
   * @return \Drupal\media\MediaTypeInterface
   *   Media Type Config Entity.
   */
  private function getMediaType(MediaInterface $media) {
    $mediaTypeId = $media->bundle();
    return $this->mediaTypeStorage->load($mediaTypeId);
  }

  /**
   * Get the Source field name.
   *
   * @param \Drupal\media\MediaTypeInterface $mediaType
   *   Media Type Config Entity.
   *
   * @return string
   *   Name of the file field.
   */
  private function getSourceFieldName(MediaTypeInterface $mediaType) {
    $source = $mediaType->getThirdPartySetting('google_cloud_vision_media', 'source', 'thumbnail');
    if ($source !== 'source') {
      return 'thumbnail';
    }

    $mediaSource = $mediaType->getSource();
    $fieldDefinition = $mediaSource->getSourceFieldDefinition($mediaType);
    return $fieldDefinition->getName();
  }

  /**
   * {@inheritdoc}
   */
  public function validateFile(FileInterface $file) {
    $iconsBaseUri = \Drupal::config('media.settings')->get('icon_base_uri');
    $defaultIcons = [
      $iconsBaseUri . '/audio.png',
      $iconsBaseUri . '/video.png',
      $iconsBaseUri . '/no-thumbnail.png',
      $iconsBaseUri . '/generic.png',
      $iconsBaseUri . '/video.png',
    ];

    $fileUri = $file->getFileUri();
    if (\in_array($fileUri, $defaultIcons, TRUE)) {
      return FALSE;
    }

    foreach ($defaultIcons as $iconUri) {
      if ($fileUri === $iconUri) {
        return FALSE;
      }
    }

    $extension = pathinfo($fileUri, PATHINFO_EXTENSION);
    if (!\in_array($extension, self::EXTENSIONS, TRUE)) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Symfony\Component\Filesystem\Exception\FileNotFoundException
   */
  public function getFileUri(FileInterface $file, ImageStyleInterface $imageStyle = NULL) {
    $fileUri = $file->getFileUri();

    if (NULL === $imageStyle) {
      return $fileUri;
    }

    $fileUri = $imageStyle->buildUri($fileUri);

    $status = file_exists($fileUri);
    if (!$status) {
      $imageStyle->createDerivative($file->getFileUri(), $fileUri);
      if (!file_exists($fileUri)) {
        throw new FileNotFoundException('Image style file is not found.');
      }
    }

    return $fileUri;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \InvalidArgumentException
   */
  public function mediaSourceHasChanged(MediaInterface $media) {
    $mediaType = $this->getMediaType($media);
    $fieldName = $this->getSourceFieldName($mediaType);

    if (!isset($media->original)) {
      return FALSE;
    }

    $langCodes = array_keys($media->getTranslationLanguages());
    foreach ($langCodes as $langCode) {
      if (!$this->mediaSourceTranslationHasChanged($media, $fieldName, $langCode)) {
        continue;
      }

      return TRUE;
    }

    return FALSE;
  }

  /**
   * Check if a translation value of the field is changed.
   *
   * @param \Drupal\media\MediaInterface $media
   *   Media Entity.
   * @param string $fieldName
   *   Name of the file field.
   * @param string $langCode
   *   Language code representation.
   *
   * @return bool
   *   True if the media source of given language has changed.
   *
   * @throws \InvalidArgumentException
   */
  protected function mediaSourceTranslationHasChanged(MediaInterface $media, $fieldName, $langCode) {
    $items = $media->getTranslation($langCode)
      ->get($fieldName)
      ->filterEmptyItems();
    $original_items = $media->original->getTranslation($langCode)
      ->get($fieldName)
      ->filterEmptyItems();
    // If the field items are not equal, we need to save.
    if ($items->equals($original_items)) {
      return FALSE;
    }

    return TRUE;
  }

}
