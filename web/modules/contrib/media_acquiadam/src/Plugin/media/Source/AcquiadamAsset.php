<?php

namespace Drupal\media_acquiadam\Plugin\media\Source;

use cweagans\webdam\Entity\Asset;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Utility\Token;
use Drupal\file\FileInterface;
use Drupal\image\Entity\ImageStyle;
use Drupal\media\MediaInterface;
use Drupal\media\MediaSourceBase;
use Drupal\media_acquiadam\AcquiadamInterface;
use Drupal\media_acquiadam\AssetDataInterface;
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides media type plugin for Acquia DAM assets.
 *
 * @MediaSource(
 *   id = "acquiadam_asset",
 *   label = @Translation("Acquia DAM asset"),
 *   description = @Translation("Provides business logic and metadata for
 *   assets stored on Acquia DAM."), allowed_field_types = {"integer"},
 * )
 */
class AcquiadamAsset extends MediaSourceBase {

  /**
   * A configured API object.
   *
   * @var \Drupal\media_acquiadam\AcquiadamInterface|\Drupal\media_acquiadam\Client
   *   $acquiadam
   */
  protected $acquiadam;

  /**
   * Array of DAM XMP fields keyed by field (prefixed with "xmp_").
   *
   * @var array
   */
  protected $acquiadamXmpFields;

  /**
   * The asset that we're going to render details for.
   *
   * @var \cweagans\webdam\Entity\Asset
   */
  protected $asset = NULL;

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * The asset data service.
   *
   * @var \Drupal\media_acquiadam\AssetData $asset_data
   */
  protected $asset_data;

  /**
   * Media: Acquia DAM config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig $config
   */
  protected $config;

  /**
   * AcquiadamAsset constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_manager
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\Core\Utility\Token $token
   * @param \Drupal\media_acquiadam\AcquiadamInterface $acquiadam
   * @param \Drupal\media_acquiadam\AssetDataInterface $asset_data
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, FieldTypePluginManagerInterface $field_type_manager, ConfigFactoryInterface $config_factory, Token $token, AcquiadamInterface $acquiadam, AssetDataInterface $asset_data) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $entity_field_manager, $field_type_manager, $config_factory);

    $this->token = $token;
    $this->acquiadam = $acquiadam;
    $this->acquiadamXmpFields = $this->acquiadam->getActiveXmpFields();
    $this->asset_data = $asset_data;
    $this->config = $config_factory->get('media_acquiadam.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('plugin.manager.field.field_type'),
      $container->get('config.factory'),
      $container->get('token'),
      $container->get('media_acquiadam.acquiadam'),
      $container->get('media_acquiadam.asset_data')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'source_field' => 'field_acquiadam_asset_id',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // Fieldset with configuration options not needed.
    hide($form);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $submitted_config = array_intersect_key($form_state->getValues(), $this->configuration);
    foreach ($submitted_config as $config_key => $config_value) {
      $this->configuration[$config_key] = $config_value;
    }

    // For consistency, always use the default source_field field name.
    $default_field_name = $this->defaultConfiguration()['source_field'];
    // Check if it already exists so it can be used as a shared field.
    $storage = $this->entityTypeManager->getStorage('field_storage_config');
    $existing_source_field = $storage->load('media.' . $default_field_name);

    // Set or create the source field.
    if ($existing_source_field) {
      // If the default field already exists, return the default field name.
      $this->configuration['source_field'] = $default_field_name;
    }
    else {
      // Default source field name does not exist, so create a new one.
      $field_storage = $this->createSourceFieldStorage();
      $field_storage->save();
      $this->configuration['source_field'] = $field_storage->getName();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function createSourceFieldStorage() {
    $default_field_name = $this->defaultConfiguration()['source_field'];

    // Create the field.
    return $this->entityTypeManager
      ->getStorage('field_storage_config')
      ->create([
        'entity_type' => 'media',
        'field_name' => $default_field_name,
        'type' => reset($this->pluginDefinition['allowed_field_types']),
      ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadataAttributes() {
    $fields = [
      'colorspace' => $this->t('Color space'),
      'datecaptured' => $this->t('Date captured'),
      'datecreated' => $this->t('Date created'),
      'datemodified' => $this->t('Date modified'),
      'description' => $this->t('Description'),
      'file' => $this->t('File'),
      'filename' => $this->t('Filename'),
      'filesize' => $this->t('Filesize'),
      'filetype' => $this->t('Filetype'),
      'folderID' => $this->t('Folder ID'),
      'height' => $this->t('Height'),
      'status' => $this->t('Active state'),
      'type' => $this->t('Type'),
      'id' => $this->t('Asset ID'),
      'version' => $this->t('Version'),
      'width' => $this->t('Width'),
    ];

    // Add additional XMP fields to fields array.
    foreach ($this->acquiadamXmpFields as $xmp_id => $xmp_field) {
      $fields[$xmp_id] = $xmp_field['label'];
    }

    return $fields;
  }

  /**
   * Get the asset ID for the given media entity.
   *
   * @param \Drupal\media\MediaInterface $media
   *   The media entity to pull the asset ID from.
   *
   * @return integer|bool
   *   The asset ID or FALSE on failure.
   */
  public function getAssetID(MediaInterface $media) {
    if (isset($this->configuration['source_field'])) {
      $source_field = $this->configuration['source_field'];

      if ($media->hasField($source_field)) {
        $property_name = $media->{$source_field}->first()->mainPropertyName();
        if (!empty($media->{$source_field}->{$property_name})) {
          return $media->{$source_field}->{$property_name};
        }
      }
    }
    return FALSE;
  }

  /**
   * Retrieve an asset from Acquia DAM.
   *
   * @param integer $assetID
   *   The ID of the asset to retrieve.
   * @param bool $includeXMP
   *   TRUE to include XMP metadata.
   *
   * @return bool|\cweagans\webdam\Entity\Asset
   */
  public function getAsset($assetID, $includeXMP = FALSE) {
    // Temporarily cache loaded assets to handle multiple save calls in a
    // single request.
    $assets = &\drupal_static('AcquiaDAMAsset::getAsset', []);
    try {
      $needs_first_get = !isset($assets[$assetID]);
      // @BUG: XMP-less assets may bypass static caching.
      // Technically if the asset doesn't have xmp_metadata (and always
      // returns an empty value) this will bypass the cache version each call.
      $needs_xmp_get = $includeXMP && empty($assets[$assetID]->xmp_metadata);
      if ($needs_first_get || $needs_xmp_get) {
        $assets[$assetID] = $this->acquiadam->getAsset($assetID, $includeXMP);
      }
    } catch (ClientException $x) {
      // We want specific handling for 404 errors so we can provide a more
      // relateable error message.
      if (404 == $x->getCode()) {
        \Drupal::logger('media_acquiadam')
          ->warning('Received a missing asset response when trying to load asset @assetID. Was the asset deleted in Acquia DAM?', ['@assetID' => $assetID]);

        // In the event of a 404 we assume the asset has been deleted within
        // Acquia DAM and need to save that state for excluding it from cron
        // syncs in the future.
        $this->asset_data->set($assetID, 'remote_deleted', TRUE);
      }
      else {
        watchdog_exception('media_acquiadam', $x);
      }
    } catch (\Exception $x) {
      watchdog_exception('media_acquiadam', $x);
    } finally {
      if (!isset($assets[$assetID])) {
        $assets[$assetID] = FALSE;
      }
    }

    return $assets[$assetID];
  }

  /**
   * Get the asset from a media entity.
   *
   * @param \Drupal\media\MediaInterface $media
   *   The media entity to get an asset from.
   *
   * @return bool|\cweagans\webdam\Entity\Asset
   *   The asset or FALSE on failure.
   */
  public function getAssetFromEntity(MediaInterface $media) {
    $assetID = $this->getAssetID($media);
    if (!empty($assetID)) {
      return $this->getAsset($assetID, TRUE);
    }
    return FALSE;
  }

  /**
   * Gets the metadata for the given entity.
   *
   * @param \Drupal\media\MediaInterface $media
   *   The media entity to get metadata from.
   * @param string $name
   *   The metadata item to get the value of.
   *
   * @return mixed|null
   *   The metadata value or NULL if unset.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function getMetadata(MediaInterface $media, $name) {

    if (empty($this->asset)) {
      $asset = $this->getAssetFromEntity($media);
      if (empty($asset)) {
        return NULL;
      }
      $this->asset = $asset;
    }

    // Return values of XMP metadata.
    if (array_key_exists($name, $this->acquiadamXmpFields)) {
      // Strip 'xmp_' prefix to retrieve matching asset xmp metadata.
      $xmp_field = substr($name, 4);
      return isset($this->asset->xmp_metadata[$xmp_field]['value']) ?
        $this->asset->xmp_metadata[$xmp_field]['value'] :
        NULL;
    }

    switch ($name) {
      case 'default_name':
        return parent::getMetadata($media, 'default_name');

      case 'thumbnail_uri':
        return $this->thumbnail($media);

      case 'folderID':
        return isset($this->asset->folder->id) ? $this->asset->folder->id : NULL;

      case 'file':
        $file = $this->createOrGetFile($media);
        if (!empty($file) && $file instanceof FileInterface) {
          return $file->id();
        }
        return NULL;

      case 'status':
        return isset($this->asset->status) ? intval($this->asset->status == 'active') : NULL;

      default:
        // The key should be the local property name and the value should be the
        // DAM provided property name.
        $property_name_mapping = [
          'colorspace' => 'colorspace',
          'datecaptured' => 'datecapturedUnix',
          'datecreated' => 'date_created_unix',
          'datemodified' => 'date_modified_unix',
          'description' => 'description',
          'filename' => 'filename',
          'filesize' => 'filesize',
          'filetype' => 'filetype',
          'height' => 'height',
          'id' => 'id',
          'type' => 'type',
          'version' => 'version',
          'width' => 'width',
        ];
        if (in_array($name, $property_name_mapping)) {
          $property_name = $property_name_mapping[$name];
          return isset($this->asset->{$property_name}) ?
            $this->asset->{$property_name} :
            NULL;
        }
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function thumbnail(MediaInterface $media) {
    $asset = $this->getAssetFromEntity($media);
    if (empty($asset)) {
      return $this->getFallbackThumbnail();
    }

    $fake_name = sprintf('%s://nothing.%s', \file_default_scheme(), $asset->filetype);
    $mimetype = \Drupal::service('file.mime_type.guesser')->guess($fake_name);
    list($discrete_type, $subtype) = explode('/', $mimetype, 2);
    $is_image = 'image' == $discrete_type;

    $file = $this->createOrGetFile($media);
    if (empty($file) || !$file instanceof FileInterface) {
      return $this->getFallbackThumbnail();
    }

    $thumbnail = $is_image ?
      $this->getImageThumbnail($file) :
      $this->getGenericIcon([$discrete_type, $subtype]);

    return !empty($thumbnail) ?
      $thumbnail :
      $this->getFallbackThumbnail();
  }

  /**
   * Get an image path from a file.
   *
   * @param \Drupal\file\FileInterface $file
   *   The image file to get the image path for.
   *
   * @return bool|string
   *   The image path to use or FALSE on failure.
   */
  protected function getImageThumbnail(FileInterface $file) {
    /** @var \Drupal\Core\Image\Image $image */
    $image = \Drupal::service('image.factory')->get($file->getFileUri());

    if ($image->isValid()) {
      // Pre-create all image styles.
      $styles = ImageStyle::loadMultiple();
      foreach ($styles as $style) {
        /** @var \Drupal\image\Entity\ImageStyle $style */
        $style->flush($file->getFileUri());
      }
      return $file->getFileUri();
    }

    return FALSE;
  }

  /**
   * Gets a generic file icon based on mimetype.
   *
   * @param array $mimetype
   *   An array of a discrete type and a subtype.
   *
   * @return bool|string
   *   A path to a generic filetype icon or FALSE on failure.
   */
  protected function getGenericIcon(array $mimetype) {
    $icon_base = $this->configFactory->get('media.settings')
      ->get('icon_base_uri');

    $generic_paths = [
      sprintf('%s/%s-%s.png', $icon_base, $mimetype[0], $mimetype[1]),
      sprintf('%s/%s.png', $icon_base, $mimetype[1]),
      sprintf('%s/generic.png', $icon_base),
    ];
    foreach ($generic_paths as $generic_path) {
      if (is_file($generic_path)) {
        return $generic_path;
      }
    }
    return FALSE;
  }

  /**
   * Gets the destination path for Acquia DAM assets.
   *
   * @param \Drupal\media\MediaInterface $media
   *   The media entity to get file field information from.
   *
   * @return string
   *   The final folder to store the asset locally.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function getAssetFileDestination(MediaInterface $media) {
    $scheme = \file_default_scheme();
    $file_directory = 'acquiadam_assets';

    // We need to pull the File field settings off of the media bundle and use
    // its path information.
    $file_field = $this->getMediaFileField($media);
    if (!empty($file_field)) {
      // Load the field definitions for this bundle.
      $field_definitions = $this->entityFieldManager->getFieldDefinitions($media->getEntityTypeId(), $media->bundle());
      // Get the storage scheme for the file field.
      $scheme = $field_definitions[$file_field]->getItemDefinition()
        ->getSetting('uri_scheme');
      // Get the file directory for the file field.
      $file_directory = $field_definitions[$file_field]->getItemDefinition()
        ->getSetting('file_directory');
      // Replace the token for file directory.
      if (!empty($file_directory)) {
        $file_directory = $this->token->replace($file_directory);
      }
    }

    return sprintf('%s://%s', $scheme, $file_directory);
  }

  /**
   * Gets the file field being used to store the asset.
   *
   * @param \Drupal\media\MediaInterface $media
   *   The media entity to get the mapped file field.
   *
   * @return bool|string
   *   The name of the file field on the media bundle or FALSE on failure.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function getMediaFileField(MediaInterface $media) {
    /** @var \Drupal\media\Entity\MediaType $bundle */
    $bundle = $this->entityTypeManager->getStorage('media_type')
      ->load($media->bundle());
    $field_map = $bundle->getFieldMap();
    return empty($field_map['file']) ? FALSE : $field_map['file'];
  }

  /**
   * @param \Drupal\media\MediaInterface $media
   *   The media entity to get the existing file ID from.
   *
   * @return bool|int
   *   The existing file ID or FALSE if one was not found.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  protected function getExistingFileID(MediaInterface $media) {
    $file_field = $this->getMediaFileField($media);
    if ($media->hasField($file_field)) {
      /** @var \Drupal\file\Plugin\Field\FieldType\FileItem $file */
      $file = $media->get($file_field)->first();
      if (!empty($file->target_id)) {
        return $file->target_id;
      }
    }
    return FALSE;
  }

  /**
   * Creates a new file for an asset.
   *
   * @param \Drupal\media\MediaInterface $media
   *   The parent media entity.
   * @param int $replace
   *   FILE_EXISTS_REPLACE or FILE_EXISTS_RENAME to replace existing or create
   *   new files.
   *
   * @return bool|\Drupal\file\FileInterface
   *   The created file or FALSE on failure.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function createNewFile(MediaInterface $media, $replace = \FILE_EXISTS_RENAME) {
    $asset = $this->getAssetFromEntity($media);
    if (empty($asset)) {
      return FALSE;
    }

    // Ensure we can write to our destination directory.
    $destination_folder = $this->getAssetFileDestination($media);
    $destination_name = $asset->filename;
    $destination_path = sprintf('%s/%s', $destination_folder, $destination_name);
    if (!file_prepare_directory($destination_folder, FILE_CREATE_DIRECTORY)) {
      return FALSE;
    }

    // If the module was configured to enforce an image size limit then we
    // need to grab the nearest matching pre-created size.
    $fake_name = sprintf('%s://nothing.%s', \file_default_scheme(), $asset->filetype);
    $mimetype = \Drupal::service('file.mime_type.guesser')->guess($fake_name);
    list($discrete_type, $subtype) = explode('/', $mimetype, 2);
    $is_image = 'image' == $discrete_type;

    $size_limit = $this->config->get('size_limit');
    if (!empty($size_limit) && '-1' !== $size_limit && $is_image) {
      $largest_tn = $this->getThumbnailUrlBySize($asset, $size_limit);
      $file = \system_retrieve_file($largest_tn, $destination_path, TRUE, $replace);
    }
    else {
      $file_contents = $this->acquiadam->downloadAsset($asset->id);
      $file = \file_save_data($file_contents, $destination_path, $replace);
    }

    $is_valid = !empty($file) && $file instanceof FileInterface;

    return $is_valid ? $file : FALSE;
  }

  /**
   * Returns an associated file or creates a new one.
   *
   * @param \Drupal\media\MediaInterface $media
   *   The media entity to get a file for.
   *
   * @return bool|\Drupal\file\FileInterface
   *   A file entity or FALSE on failure.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  protected function createOrGetFile(MediaInterface $media) {

    // If we're getting an updated version of the asset we need to grab a new
    // version of the file.
    $asset = $this->getAssetFromEntity($media);
    $current_version = intval($this->asset_data->get($asset->id, 'version'));
    $new_version = intval($asset->version);
    $is_updated_version = $new_version > 1 && $new_version != $current_version;
    if ($is_updated_version) {
      // Track the new version for future reference.
      $this->asset_data->set($asset->id, 'version', $new_version);
    }

    $file = FALSE;
    // If there is already a file on the media entity then we should use that.
    $fid = $this->getExistingFileID($media);
    if (!empty($fid)) {
      $file = $this->entityTypeManager->getStorage('file')->load($fid);
    }

    if (empty($file) || $is_updated_version) {
      $replace = $is_updated_version ?
        \FILE_EXISTS_REPLACE :
        \FILE_EXISTS_RENAME;
      $file = $this->createNewFile($media, $replace);
    }

    return $file;
  }

  /**
   * Get a fallback image to use for the thumbnail.
   *
   * @return string|FALSE
   *   The Drupal image path to use or FALSE.
   */
  protected function getFallbackThumbnail() {

    /** @var \Drupal\Core\Config\Config $config */
    $config = \Drupal::configFactory()
      ->getEditable('media_acquiadam.settings');

    $fallback = $config->get('fallback_thumbnail');
    if (empty($fallback)) {
      // There was no configured fallback image, so we should use the one
      // bundled with the module. Drupal core prevents generating image styles
      // from module directories, so we need to copy our placeholder to the
      // files directory first.
      $source = drupal_get_path('module', 'media_acquiadam') . '/img/webdam.png';

      // @TODO: Technically this will default to any image named webdam.png, not
      // necessarily the one we put there.
      $fallback = sprintf('%s://webdam.png', file_default_scheme());
      if (!file_exists($fallback)) {
        $fallback = file_unmanaged_copy($source, $fallback);
        if (!empty($fallback)) {
          $config->set('fallback_thumbnail', $fallback)->save();
        }
      }
    }

    return $fallback;
  }

  /**
   * Get the URL to the DAM-provided thumbnail if possible.
   *
   * @param Asset $asset
   *   The asset to get the thumbnail size from.
   * @param int $thumbnailSize
   *   Find the closest thumbnail size without going over when multiple
   *   thumbnails are available.
   *
   * @return string|false
   *   The preview URL or FALSE if none available.
   */
  public function getThumbnailUrlBySize(Asset $asset, $thumbnailSize = 1280) {

    if (!empty($asset->thumbnailurls[0]->url)) {
      // Copy thumbnail array to variable to avoid a notice about indirect
      // access.
      $thumbnails = $asset->thumbnailurls;
      // Default to first regardless of size.
      $biggest_matching = $thumbnails[0]->url;
      foreach ($thumbnails as $tn) {
        if (!empty($tn->url) && $thumbnailSize >= $tn->size) {
          // Certain types do not have a 1280 size available despite returning
          // an URL. We either have to hard code mime types as they crop up, or
          // check if the URL is accessible on our own. Other URL sizes do not
          // appear to have this issue.
          if (1280 == $tn->size) {
            $response = \Drupal::httpClient()->head($tn->url);
            if (403 == $response->getStatusCode()) {
              continue;
            }
          }
          $biggest_matching = $tn->url;
        }
      }
      return $biggest_matching;
    }
    return FALSE;
  }

}
