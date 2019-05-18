<?php

namespace Drupal\media_webdam\Plugin\MediaEntity\Type;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\FileInterface;
use Drupal\image\Entity\ImageStyle;
use Drupal\media_entity\MediaInterface;
use Drupal\media_entity\MediaTypeBase;
use Drupal\media_webdam\WebdamInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\file\Entity\File;

/**
 * Provides media type plugin for Webdam Images.
 *
 * @MediaType(
 *   id = "webdam_asset",
 *   label = @Translation("Webdam asset"),
 *   description = @Translation("Provides business logic and metadata for assets stored on Webdam.")
 * )
 */
class WebdamAsset extends MediaTypeBase {

  /**
   * A configured webdam API object.
   *
   * @var \Drupal\media_webdam\Webdam $webdam
   */
  protected $webdam;

  /**
   * The asset that we're going to render details for.
   *
   * @var \cweagans\webdam\Entity\Asset
   */
  protected $asset = NULL;

  /**
   * The file entity.
   *
   * @var \Drupal\file\Entity\File
   */
  protected $file = NULL;

  /**
   * WebdamAsset constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, ConfigFactoryInterface $config_factory, WebdamInterface $webdam) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $entity_field_manager, $config_factory->get('media_entity.settings'));
    $this->webdam = $webdam;
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
      $container->get('config.factory'),
      $container->get('media_webdam.webdam')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function providedFields() {
    // @TODO: Determine if other properties need to be added here.
    // @TODO: Determine how to support custom metadata.
    $fields = [
      'file' => $this->t('File'),
      'type_id' => $this->t('Type ID'),
      'filename' => $this->t('Filename'),
      'filesize' => $this->t('Filesize'),
      'width' => $this->t('Width'),
      'height' => $this->t('Height'),
      'description' => $this->t('Description'),
      'filetype' => $this->t('Filetype'),
      'colorspace' => $this->t('Color space'),
      'version' => $this->t('Version'),
      'datecreated' => $this->t('Date created'),
      'datemodified' => $this->t('Date modified'),
      'datecaptured' => $this->t('Date captured'),
      'folderID' => $this->t('Folder ID'),
      'status' => $this->t('Status'),
    ];
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $options = [];
    $bundle = $form_state->getFormObject()->getEntity();
    $allowed_field_types = ['integer'];
    foreach ($this->entityFieldManager->getFieldDefinitions('media', $bundle->id()) as $field_name => $field) {
      if (in_array($field->getType(), $allowed_field_types) && !$field->getFieldStorageDefinition()->isBaseField()) {
        $options[$field_name] = $field->getLabel();
      }
    }

    $form['source_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Field with source information'),
      '#description' => $this->t('Field on media entity that stores the Webdam asset ID. You can create a bundle without selecting a value for this dropdown initially. This dropdown can be populated after adding fields to the bundle.'),
      '#default_value' => empty($this->configuration['source_field']) ? NULL : $this->configuration['source_field'],
      '#options' => $options,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getField(MediaInterface $media, $name) {
    $assetID = NULL;
    if (isset($this->configuration['source_field'])) {
      $source_field = $this->configuration['source_field'];

      if ($media->hasField($source_field)) {
        $property_name = $media->{$source_field}->first()->mainPropertyName();
        $assetID = $media->{$source_field}->{$property_name};
      }
    }
    // If we don't have an asset ID, there's not much we can do.
    if (is_null($assetID)) {
      return FALSE;
    }
    // If the webdam asset has not been loaded.
    if (!$this->asset) {
      // Load the asset.
      $this->asset = $this->webdam->getAsset($assetID);
    }
    switch ($name) {
      case 'type_id':
        return $this->asset->type_id;

      case 'filename':
        return $this->asset->filename;

      case 'filesize':
        return $this->asset->filesize;

      case 'width':
        return $this->asset->width;

      case 'height':
        return $this->asset->height;

      case 'description':
        return $this->asset->description;

      case 'filetype':
        return $this->asset->filetype;

      case 'colorspace':
        return $this->asset->colorspace;

      case 'version':
        return $this->asset->version;

      case 'datecreated':
        return $this->asset->date_created_unix;

      case 'datemodified':
        return $this->asset->date_modified_unix;

      case 'datecaptured':
        return $this->asset->datecapturedUnix;

      case 'folderID':
        return $this->asset->folder->id;

      case 'file':
        return $this->file ? $this->file->id() : NULL;

      case 'status':
        return intval($this->asset->status == 'active');
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function thumbnail(MediaInterface $media) {
    // Load the bundle for this asset.
    $bundle = $this->entityTypeManager->getStorage('media_bundle')->load($media->bundle());
    // Load the field definitions for this bundle.
    $field_definitions = $this->entityFieldManager->getFieldDefinitions($media->getEntityTypeId(), $media->bundle());
    // If a source field is set for this bundle.
    if (isset($this->configuration['source_field'])) {
      // Set the name of the source field.
      $source_field = $this->configuration['source_field'];
      // If the media entity has the source field.
      if ($media->hasField($source_field)) {
        // Set the property name for the source field.
        $property_name = $media->{$source_field}->first()->mainPropertyName();
        // Get the webdam asset ID value from the source field.
        $assetID = $media->{$source_field}->{$property_name};
      }
    }
    // If we don't have an asset ID, there's not much we can do.
    if (is_null($assetID)) {
      return FALSE;
    }
    // Load the asset.
    $asset = $this->webdam->getAsset($assetID);
    // Download the webdam asset file as a string.
    $file_contents = $this->webdam->downloadAsset($asset->id);
    // Set the path for webdam assets.
    // If the bundle has a field mapped for the file.
    if ($file_field = $bundle->field_map['file']) {
      // Get the storage scheme for the file field.
      $scheme = $field_definitions[$file_field]->getItemDefinition()->getSetting('uri_scheme');
    }
    else {
      // Otherwise default to public storage.
      $scheme = 'public';
    }
    // Set the path prefix for the file that is about to be downloaded from
    // webdam and saved in to Drupal.
    $path = $scheme . '://webdam_assets/';
    // Prepare webdam directory for writing and only proceed if successful.
    if (file_prepare_directory($path, FILE_CREATE_DIRECTORY)) {
      // Save the file into Drupal.
      $file = file_save_data($file_contents, $path . $asset->id . '.' . $asset->filetype, FILE_EXISTS_REPLACE);
      // If the file was saved.
      if ($file instanceof FileInterface || $file instanceof File) {
        $this->file = $file;
        // Get the mimetype of the file.
        $mimetype = $file->getMimeType();
        // Split the mimetype into 2 parts (primary/secondary)
        $mimetype = explode('/', $mimetype);
        // If the primary mimetype is not an image.
        if ($mimetype[0] != 'image') {
          // Try to get the filetype icon using primary and secondary mimetype.
          $thumbnail = $this->config->get('icon_base') . "/{$mimetype[0]}-{$mimetype[1]}.png";
          // If icon is not found.
          if (!is_file($thumbnail)) {
            // Try to get the filetype icon using only the secondary mimetype.
            $thumbnail = $this->config->get('icon_base') . "/{$mimetype[1]}.png";
            // If icon is still not found.
            if (!is_file($thumbnail)) {
              // Use a generic document icon.
              $thumbnail = $this->config->get('icon_base') . '/document.png';
            }
          }
        }
        else {
          // Load the image.
          $image = \Drupal::service('image.factory')->get($file->getFileUri());
          /** @var \Drupal\Core\Image\Image $image */
          // If the image is valid.
          if ($image->isValid()) {
            // Load all image styles.
            $styles = ImageStyle::loadMultiple();
            // For each image style.
            foreach ($styles as $style) {
              /** @var ImageStyle $style */
              // Flush and regenerate the styled image.
              $style->flush($file->getFileUri());
            }
          }
          // Use the URI of the image.
          $thumbnail = $file->getFileUri();
        }
        // Return the file URI.
        return $thumbnail;
      }
    }
    // If the file field is not mapped, use the default webdam icon.
    return drupal_get_path('module', 'media_webdam') . '/img/webdam.png';
  }

}
