<?php

namespace Drupal\toolshed\Utility;

use Drupal\Core\Url;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;

/**
 * Helper class for getting the URI or info of file from media or file entities.
 */
class FileHelper {

  /**
   * Create a FileHelper from an entity field.
   *
   * This method will try to capture a file object referenced by an
   * entity field. The method is able to get the file from a Media entity
   * if the field is referencing a media entity.
   *
   * @param Drupal\Core\Entity\ContentEntityInterface $entity
   *   A fieldable entity containing the file to get the URL for as a field.
   * @param string $fieldName
   *   Name of the field to try to extract the File object from.
   * @param string $viewMode
   *   The view mode to use when rendering the entity. This can help determine
   *   if any image styles or other settings that might affect the final URL.
   * @param int $delta
   *   The field delta to extract the file from. Assumes the first field.
   *
   * @return Drupal\toolshed\Utility\FileHelper|null
   *   An instance of the FileHelper.
   */
  public static function fromEntityField(ContentEntityInterface $entity, $fieldName, $viewMode = 'default', $delta = 0) {
    $entityType = $entity->getEntityType()->id();
    $bundle = $entity->bundle();

    try {
      $field = $entity->get($fieldName);

      if (!$field->get($delta) || !$field instanceof EntityReferenceFieldItemListInterface) {
        return;
      }

      $fieldDef = $field->getFieldDefinition();
      $targetType = $fieldDef->getFieldStorageDefinition()->get('settings')['target_type'];
      $values = $field[$delta]->getValue();
      $targetEnt = \Drupal::entityTypeManager()
        ->getStorage($targetType)
        ->load($values['target_id']);

      // Entity is not available, then there is nothing to do.
      if (!$targetEnt) {
        return;
      }

      $entDisplayHandler = \Drupal::entityTypeManager()->getStorage('entity_view_display');
      $entDisplay = $entDisplayHandler->load("{$entityType}.{$bundle}.{$viewMode}")
        ?: $entDisplayHandler->load("{$entityType}.{$bundle}.default");

      $fieldDisplay = $entDisplay->getComponent($fieldName);
      $displaySettings = empty($fieldDisplay['settings']) ? [] : $fieldDisplay['settings'];

      if ($targetEnt instanceof File) {
        return new static($targetEnt, $displaySettings, $values);
      }
      elseif ($targetEnt->getEntityType()->id() === 'media') {
        $mediaViewMode = !empty($displaySettings['view_mode']) ? $displaySettings['view_mode'] : 'default';

        $srcField = static::getMediaField($targetEnt);
        return empty($srcField) ? NULL : static::fromEntityField($targetEnt, $srcField, $mediaViewMode);
      }
    }
    catch (Exception $e) {
      \Drupal::logger("toolshed")->error('Unable fetch file information for %entity_type %bundle: @message', [
        '%entity_type' => $entityType,
        '%bundle' => $bundle,
        '@message' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Create a file helper from either a File or Media entity.
   *
   * @param Drupal\Core\Entity\ContentEntityInterface $entity
   *   The file or media entity to create a new instance of the FileHelper
   *   from. The instance (if it can be created) will be for the real file.
   * @param string $viewMode
   *   View mode to use when determining the display of the file.
   *
   * @return Drupal\toolshed\Utility\FileHelper|null
   *   An instance of the FileHelper.
   */
  public static function fromEntity(ContentEntityInterface $entity, $viewMode = 'default') {
    if ($entity instanceof File) {
      return new static($entity, []);
    }
    elseif ($entity->getEntityType()->id() === 'media') {
      $srcField = static::getMediaField($entity);
      return empty($srcField) ? NULL : static::fromEntityField($entity, $srcField, $viewMode);
    }

    // Unsupported entity type.
    \Drupal::logger("toolshed")->error('Unable fetch file information for %entity_type %bundle.', [
      '%entity_type' => $entity->getEntityType()->id(),
      '%bundle' => $entity->bundle(),
    ]);
  }

  /**
   * Get the field which contains the media file, for a media entity.
   *
   * Every media entity type can utilize a different field for its file, this
   * method helps to determine which field was used.
   *
   * @param Drupal\Core\Entity\ContentEntityInterface $media
   *   The media entity being to test for the field information. We don't
   *   use the \Drupal\media_entity\Entity\Media class directly because it
   *   may not exists if the media entity module is not install (allowes it
   *   to be not required).
   *
   * @return string|false
   *   The machine name of the media that contains the media file. Returns
   *   FALSE if the field can't be determined for this media entity type.
   */
  protected static function getMediaField(ContentEntityInterface $media) {
    static $fields;

    $bundle = $media->bundle();
    if (!isset($fields[$bundle])) {
      // If not a media entity, we need to exit, and return NULL.
      if (class_exists('\Drupal\media\Entity\Media') && is_a($media, '\Drupal\media\Entity\Media', TRUE)) {
        // Drupal Core Media module.
        $fields[$bundle] = $media->getSource()->getConfiguration()['source_field'];
      }
      elseif (class_exists('\Drupal\media_entity\Entity\Media') && is_a($media, '\Drupal\media_entity\Entity\Media', TRUE)) {
        // Media Entity contrib module.
        $mediaType = $media->getType();
        $configsProp = new \ReflectionProperty(get_class($mediaType), 'configuration');

        $configsProp->setAccessible(TRUE);
        $configs = $configsProp->getValue($mediaType);
        $fields[$bundle] = @$configs['source_field'] ?: FALSE;
      }
      else {
        return NULL;
      }
    }

    return $fields[$bundle];
  }

  /**
   * The file to generate the URL for.
   *
   * @var Drupal\file\Entity\File
   */
  protected $file;

  /**
   * Display settings for the file.
   *
   * This can contain `image_style` or other settings that may control
   * the style of URL that gets generated.
   *
   * @var array
   */
  protected $displaySettings;

  /**
   * Additional data to use with the file entity.
   *
   * @var array
   */
  protected $data;

  /**
   * Create a new instance of the FileHelper with the provided file.
   *
   * @param Drupal\file\Entity\File $file
   *   File entity to track and create the URL for.
   * @param array $display
   *   Display settings for this file entity. Mostly we will be looking
   *   for an image style or view mode.
   * @param array $data
   *   Additional field data that may have been stored with this file, such
   *   as image files which have the alt and width / height information.
   */
  public function __construct(File $file, array $display = [], array $data = []) {
    $this->file = $file;
    $this->displaySettings = $display;
    $this->data = $data;
  }

  /**
   * Retrieve file specific data.
   *
   * @return array
   *   Array of additional data that was stored with this file. Usually comes
   *   from the field it was extracted from, such as the alt or width / height
   *   information for image files.
   */
  public function getData() {
    return $this->data;
  }

  /**
   * Get the file name of the referenced file.
   *
   * @return string
   *   The name of the underlying file.
   */
  public function getFilename() {
    return $this->file->getFilename();
  }

  /**
   * Get the URI of the file.
   *
   * @return string
   *   Get the URI to the file.
   */
  public function getUri() {
    return $this->file->getFileUri();
  }

  /**
   * Get the file mime for the file.
   *
   * @return string
   *   The file mime for the file, or an empty string if it can't be determined.
   */
  public function getMime() {
    return $this->file->filemime->isEmpty() ? '' : $this->file->getMimeType();
  }

  /**
   * Get the size of the file in bytes.
   *
   * @return int
   *   The size of the file in bytes.
   */
  public function getSize() {
    return $this->file->getSize();
  }

  /**
   * Build the URL of the file, paying attention to display settings.
   *
   * @param array $settings
   *   Any rendering overrides to provide for rendering this URL.
   *   The most common things to override would be either 'absolute'
   *   for 'image_style'.
   *
   * @return string
   *   The URL of the file, and will include any view or field settings
   *   applied. This is mostly for `image_style` at this point.
   */
  public function buildUrl(array $settings = []) {
    $settings += $this->displaySettings + ['absolute' => TRUE];

    if (!empty($settings['image_style'])) {
      $imageStyle = $this->displaySettings['image_style'];
      $imageStyleObj = ImageStyle::Load($imageStyle) ?: FALSE;

      if ($imageStyleObj) {
        return $imageStyleObj->buildUrl($this->getUri());
      }
    }

    // No image style, just return the path directly to the file.
    return $this->buildRawUrl();
  }

  /**
   * Create a URL to the file directly, without applying any display or views.
   *
   * @param bool $absolute
   *   TRUE if the resulting URL should be made absolute.
   *
   * @return string
   *   The URL path to the file set in this URL helper.
   */
  public function buildRawUrl($absolute = TRUE) {
    $urlOpts = ['absolute' => $absolute];
    $uri = file_create_url($this->getUri());

    // Get the URL directly to this file.
    return Url::fromUri($uri, $urlOpts)->toString();
  }

}
