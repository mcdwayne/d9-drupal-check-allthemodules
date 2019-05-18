<?php

namespace Drupal\cloudconvert_media_thumbnail\EventSubscriber;

use Drupal\cloudconvert\Event\CloudConvertFinishEvent;
use Drupal\Component\Render\PlainTextOutput;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\file\FileInterface;
use Drupal\media\MediaInterface;
use Drupal\Core\Utility\Token;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class MediaThumbnailSubscriber.
 *
 * @package Drupal\cloudconvert\EventSubscriber
 */
class MediaThumbnailSubscriber implements EventSubscriberInterface, ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Token.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * MediaThumbnailSubscriber constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity Type Manager.
   * @param \Drupal\Core\Utility\Token $token
   *   Token.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, Token $token) {
    $this->entityTypeManager = $entityTypeManager;
    $this->token = $token;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[CloudConvertFinishEvent::FINISH][] = ['finishMediaTask'];
    return $events;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('token')
    );
  }

  /**
   * The finish media task event handler to set the thumbnail.
   *
   * @param \Drupal\cloudconvert\Event\CloudConvertFinishEvent $event
   *   Cloud Convert Finish Event.
   *
   * @throws \InvalidArgumentException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function finishMediaTask(CloudConvertFinishEvent $event) {
    $cloudConvertTask = $event->getCloudConvertTask();

    if ($cloudConvertTask->bundle() !== 'media_thumbnail') {
      return;
    }

    /** @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $mediaField */
    $mediaField = $cloudConvertTask->get('field_media');
    /** @var \Drupal\media\MediaInterface[] $mediaItems */
    $mediaItems = $mediaField->referencedEntities();
    $media = reset($mediaItems);
    $fileStorage = $this->entityTypeManager->getStorage('file');

    $fileInfo = pathinfo($event->getResult());
    $thumbnailDirectory = $this->getThumbnailDirectory($media);
    $thumbnailUri = file_unmanaged_move($event->getResult(), $thumbnailDirectory . '/' . $fileInfo['basename']);

    /** @var \Drupal\file\FileInterface $file */
    $file = $fileStorage->create(['uri' => $thumbnailUri]);
    if ($owner = $media->getOwner()) {
      $file->setOwner($owner);
    }
    $file->setPermanent();
    $file->save();

    $this->setThumbnail($media, $file);
  }

  /**
   * Get the thumbnail directory.
   *
   * @param \Drupal\media\MediaInterface $media
   *   Media Entity.
   *
   * @return string
   *   Directory location.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  private function getThumbnailDirectory(MediaInterface $media) {
    $mediaTypeId = $media->bundle();
    $mediaTypeStorage = $this->entityTypeManager->getStorage('media_type');
    /** @var \Drupal\media\MediaTypeInterface $mediaType */
    $mediaType = $mediaTypeStorage->load($mediaTypeId);
    $fieldName = $mediaType->getSource()->getConfiguration()['source_field'];
    $fieldDefinition = $media->getFieldDefinition($fieldName);
    $targetFieldSettings = $fieldDefinition->getSettings();

    $baseThumbnailDirectory = trim($targetFieldSettings['file_directory'], '/') . '/thumbnail';
    $baseThumbnailDirectory = PlainTextOutput::renderFromHtml($this->token->replace($baseThumbnailDirectory));

    $thumbnailDirectory = $targetFieldSettings['uri_scheme'] . '://' . $baseThumbnailDirectory;
    file_prepare_directory($thumbnailDirectory, FILE_CREATE_DIRECTORY);
    return $thumbnailDirectory;
  }

  /**
   * Set the new file as the thumbnail.
   *
   * @param \Drupal\media\MediaInterface $media
   *   Media Entity.
   * @param \Drupal\file\FileInterface $file
   *   File Entity.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function setThumbnail(MediaInterface $media, FileInterface $file) {
    $media->set('thumbnail', $file);

    $mediaSource = $media->getSource();
    $plugin_definition = $mediaSource->getPluginDefinition();
    if (!empty($plugin_definition['thumbnail_alt_metadata_attribute'])) {
      $media->thumbnail->alt = $mediaSource->getMetadata($media, $plugin_definition['thumbnail_alt_metadata_attribute']);
    }
    else {
      $media->thumbnail->alt = $this->t('Thumbnail', [], ['langcode' => $media->langcode->value]);
    }

    if (!empty($plugin_definition['thumbnail_title_metadata_attribute'])) {
      $media->thumbnail->title = $mediaSource->getMetadata($media, $plugin_definition['thumbnail_title_metadata_attribute']);
    }
    else {
      $media->thumbnail->title = $media->label();
    }
    $media->save();
  }

}
