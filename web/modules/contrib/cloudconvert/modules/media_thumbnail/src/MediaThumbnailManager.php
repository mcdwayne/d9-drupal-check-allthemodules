<?php

namespace Drupal\cloudconvert_media_thumbnail;

use Drupal\cloudconvert\CloudConvertProcessor;
use Drupal\cloudconvert\Entity\CloudConvertTaskInterface;
use Drupal\cloudconvert\Parameters;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\media\MediaInterface;
use Drupal\media\MediaTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class MediaThumbnailManager.
 *
 * @package Drupal\cloudconvert
 */
class MediaThumbnailManager implements ContainerInjectionInterface, MediaThumbnailManagerInterface {

  /**
   * Cloud Convert Processor.
   *
   * @var \Drupal\cloudconvert\CloudConvertProcessor
   */
  protected $cloudConvertProcessor;

  /**
   * Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Queue Factory.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * Media Type Storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $mediaTypeStorage;

  /**
   * Output format to convert to.
   *
   * @var array
   */
  protected $outputFormats = ['jpg', 'png'];

  /**
   * MediaThumbnailManager constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity Type Manager.
   * @param \Drupal\cloudconvert\CloudConvertProcessor $cloudConvertProcessor
   *   Cloud Convert Processor.
   * @param \Drupal\Core\Queue\QueueFactory $queueFactory
   *   Queue Factory.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, CloudConvertProcessor $cloudConvertProcessor, QueueFactory $queueFactory) {
    $this->entityTypeManager = $entityTypeManager;
    $this->cloudConvertProcessor = $cloudConvertProcessor;
    $this->queueFactory = $queueFactory;
    $this->mediaTypeStorage = $entityTypeManager->getStorage('media_type');
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
      $container->get('cloudconvert.processor'),
      $container->get('queue')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @throws \GuzzleHttp\Exception\RequestException
   * @throws \Drupal\cloudconvert\Exceptions\ApiTemporaryUnavailableException
   * @throws \Drupal\cloudconvert\Exceptions\ApiException
   * @throws \Drupal\cloudconvert\Exceptions\ApiConversionFailedException
   * @throws \Drupal\cloudconvert\Exceptions\ApiBadRequestException
   * @throws \InvalidArgumentException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\cloudconvert\Exceptions\InvalidParameterException
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function queueThumbnailTask(MediaInterface $media, $emptyOnly = FALSE) {
    /** @var \Drupal\file\FileInterface $file */
    $file = $this->getMediaFile($media);

    if (!$this->isThumbnailGenerationNeeded($media)) {
      return;
    }

    $cloudConvertTaskTypeStorage = $this->entityTypeManager->getStorage('cloudconvert_task_type');
    /** @var \Drupal\cloudconvert\Entity\CloudConvertTaskTypeInterface $cloudConvertTaskType */
    $cloudConvertTaskType = $cloudConvertTaskTypeStorage->load('media_thumbnail');
    $cloudConvertTask = $this->cloudConvertProcessor->createTask($cloudConvertTaskType, $file);
    $cloudConvertTask->set('field_media', $media);

    $cloudConvertTask->save();

    $inputFormat = pathinfo($file->getFilename(), PATHINFO_EXTENSION);
    $modeOptions = $this->getModeOptions($cloudConvertTask);

    if (empty($modeOptions)) {
      return;
    }

    $parameters = new Parameters([
        'inputformat' => $inputFormat,
        'converteroptions' => [
          'page_range' => '1-1',
          'thumbnail_size' => '1920x',
          'resize' => '1920x',
          'strip_metatags' => TRUE,
          'density' => '75',
        ],
      ] + $modeOptions);

    $this->cloudConvertProcessor->createStartQueueItem($cloudConvertTask, $parameters);
  }

  /**
   * Get the Media File.
   *
   * @param \Drupal\media\MediaInterface $media
   *   Media Entity.
   *
   * @return \Drupal\file\FileInterface
   *   File Entity.
   *
   * @throws \InvalidArgumentException
   */
  private function getMediaFile(MediaInterface $media) {
    $mediaType = $this->getMediaType($media);
    $fieldName = $this->getSourceFieldName($mediaType);
    /** @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $field */
    $field = $media->get($fieldName);

    /** @var \Drupal\file\FileInterface[] $files */
    $files = $field->referencedEntities();

    return reset($files);
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
   *   Field Name.
   */
  private function getSourceFieldName(MediaTypeInterface $mediaType) {
    $mediaSource = $mediaType->getSource();
    $fieldDefinition = $mediaSource->getSourceFieldDefinition($mediaType);
    return $fieldDefinition->getName();
  }

  /**
   * Validate if thumbnail generation is needed.
   *
   * @param \Drupal\media\MediaInterface $media
   *   Media Entity.
   *
   * @return bool
   *   TRUE if thumbnail generation is needed.
   *
   * @throws \InvalidArgumentException
   */
  public function isThumbnailGenerationNeeded(MediaInterface $media) {
    $mediaType = $this->getMediaType($media);
    $mediaSource = $mediaType->getSource();
    return $mediaSource->getPluginDefinition()['id'] !== 'image' && !$this->isThumbnailAlreadyCreated($media);
  }

  /**
   * Validate if the thumbnail is already created.
   *
   * @param \Drupal\media\MediaInterface $media
   *   Media Entity.
   *
   * @return bool
   *   TRUE if the thumbnail is already created.
   *
   * @throws \InvalidArgumentException
   */
  private function isThumbnailAlreadyCreated(MediaInterface $media) {
    $iconsBaseUri = \Drupal::config('media.settings')->get('icon_base_uri');
    $defaultIcons = [
      $iconsBaseUri . '/audio.png',
      $iconsBaseUri . '/video.png',
      $iconsBaseUri . '/no-thumbnail.png',
      $iconsBaseUri . '/generic.png',
      $iconsBaseUri . '/video.png',
    ];

    /** @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $thumbnailField */
    $thumbnailField = $media->get('thumbnail');
    $files = $thumbnailField->referencedEntities();
    if (empty($files)) {
      return FALSE;
    }

    /** @var \Drupal\file\FileInterface $thumbnail */
    $thumbnail = reset($files);
    $fileUri = $thumbnail->getFileUri();

    foreach ($defaultIcons as $iconUri) {
      if ($fileUri === $iconUri) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Get the mode options for the process.
   *
   * @param \Drupal\cloudconvert\Entity\CloudConvertTaskInterface $cloudConvertTask
   *   Cloud Convert Task.
   *
   * @return []
   *   The Cloud Convert Mode Options.
   *
   * @throws \RuntimeException
   * @throws \GuzzleHttp\Exception\RequestException
   * @throws \Drupal\cloudconvert\Exceptions\ApiTemporaryUnavailableException
   * @throws \Drupal\cloudconvert\Exceptions\ApiConversionFailedException
   * @throws \Drupal\cloudconvert\Exceptions\ApiBadRequestException
   * @throws \Drupal\cloudconvert\Exceptions\ApiException
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  protected function getModeOptions(CloudConvertTaskInterface $cloudConvertTask) {
    $file = $cloudConvertTask->getOriginalFile();
    $inputFormat = pathinfo($file->getFilename(), PATHINFO_EXTENSION);

    $result = $this->cloudConvertProcessor->getCloudConvertApi()
      ->get('/conversiontypes', [
        'inputformat' => $inputFormat,
      ]);

    if (empty($result)) {
      return [];
    }

    foreach ($result as $conversionOption) {
      if (\in_array($conversionOption['outputformat'], $this->outputFormats)) {
        return [
          'mode' => 'convert',
          'outputformat' => $conversionOption['outputformat'],
        ];
      }
      $options = array_keys($conversionOption['converteroptions']);
      if (\in_array('thumbnail_format', $options, FALSE)) {
        return TRUE;
      }
    }

    return [];
  }

  /**
   * {@inheritdoc}
   *
   * @throws \RuntimeException
   * @throws \GuzzleHttp\Exception\RequestException
   * @throws \Drupal\cloudconvert\Exceptions\ApiTemporaryUnavailableException
   * @throws \Drupal\cloudconvert\Exceptions\ApiException
   * @throws \Drupal\cloudconvert\Exceptions\ApiConversionFailedException
   * @throws \Drupal\cloudconvert\Exceptions\ApiBadRequestException
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function validateImageConversion($inputFormat, $outputFormat) {
    $result = $this->cloudConvertProcessor->getCloudConvertApi()
      ->get('/conversiontypes', [
        'inputformat' => $inputFormat,
        'outputformat' => $outputFormat,
      ]);

    return !empty($result);
  }

  /**
   * {@inheritdoc}
   *
   * @throws \RuntimeException
   * @throws \GuzzleHttp\Exception\RequestException
   * @throws \Drupal\cloudconvert\Exceptions\ApiTemporaryUnavailableException
   * @throws \Drupal\cloudconvert\Exceptions\ApiException
   * @throws \Drupal\cloudconvert\Exceptions\ApiConversionFailedException
   * @throws \Drupal\cloudconvert\Exceptions\ApiBadRequestException
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function validateThumbnailConversion($inputFormat) {
    $result = $this->cloudConvertProcessor->getCloudConvertApi()
      ->get('/conversiontypes', [
        'inputformat' => $inputFormat,
      ]);

    if (empty($result)) {
      return FALSE;
    }

    foreach ($result as $conversionOption) {
      $options = array_keys($conversionOption['converteroptions']);
      if (\in_array('thumbnail_format', $options, FALSE)) {
        return TRUE;
      }
    }

    return FALSE;
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

    $langcodes = array_keys($media->getTranslationLanguages());
    if ($langcodes === array_keys($media->original->getTranslationLanguages())) {
      return FALSE;
    }

    foreach ($langcodes as $langcode) {
      if (!$this->mediaSourceTranslationHasChanged($media, $fieldName, $langcode)) {
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
   *   Field Name.
   * @param string $langCode
   *   Language Code.
   *
   * @return bool
   *   TRUE if media source translation has changed.
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
