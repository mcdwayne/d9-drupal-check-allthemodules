<?php

namespace Drupal\media_entity_icon\Plugin\MediaEntity\Type;

use Drupal\Core\Config\Config;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\media_entity\MediaInterface;
use Drupal\media_entity\MediaTypeBase;

use Drupal\media_entity_icon\SvgManagerInterface;

/**
 * Provides media type plugin for SvgIcon.
 *
 * @MediaType(
 *   id = "svg_icon",
 *   label = @Translation("SvgIcon"),
 *   description = @Translation("Provides business logic for SVG icons.")
 * )
 */
class SvgIcon extends MediaTypeBase {

  /**
   * File system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * SVG manager service.
   *
   * @var \Drupal\media_entity_icon\SvgManagerInterface
   */
  protected $svgManager;

  /**
   * The directory where thumbnails are stored.
   *
   * @var string
   */
  protected $thumbnailDir = 'public://icon_thumbnails';

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, Config $config, FileSystemInterface $file_system, SvgManagerInterface $svg_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $entity_field_manager, $config);

    $this->fileSystem = $file_system;
    $this->svgManager = $svg_manager;
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
      $container->get('config.factory')->get('media_entity.settings'),
      $container->get('file_system'),
      $container->get('media_entity_icon.manager.svg')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function providedFields() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getField(MediaInterface $media, $name) {
    $value = FALSE;

    switch ($name) {
      case 'source':
      case 'source_uri':
        $source_field = $this->configuration['source_field'];
        if ($media->hasField($source_field)) {
          if ($media->get($source_field)->getFieldDefinition()->getType() === 'entity_reference') {
            $svg_entity = $media->get($source_field)->entity;
            /** @var SvgSprite $media_type */
            $media_type = $svg_entity->getType();
            if ('svg_sprite' === $media_type->getPluginId()) {
              $value = $media_type->getField($svg_entity, $name == 'source' ? 'path' : 'uri');
            }
          }
          else {
            $value = $media->get($source_field)->value;
          }
        }
        break;

      case 'source_realpath':
        $source_uri = $this->getField($media, 'source_uri');
        // External.
        if (strpos($source_uri, 'http') === 0) {
          $value = $source_uri;
        }
        // Local.
        else {
          $value = $this->fileSystem->realpath($source_uri);
        }
        break;

      case 'id':
        $id_field = $this->configuration['id_field'];
        if ($media->hasField($id_field)) {
          $value = $media->get($id_field)->value;
        }
        break;

      case 'thumbnail_id':
        $source_uri = $this->getField($media, 'source_uri');
        $icon_id = $this->getField($media, 'id');
        $value = basename($source_uri) . '--' . $icon_id;
        break;

    }

    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultThumbnail() {
    return $this->config->get('icon_base') . '/svg-icon.png';
  }

  /**
   * Construct local thumbnail URI.
   *
   * @param \Drupal\media_entity\MediaInterface $media
   *   Media entity.
   *
   * @return string
   *   Thumbnail URI of the media.
   */
  public function getThumbnailUri(MediaInterface $media) {
    return $this->thumbnailDir . '/' . $media->uuid() . '.png';
  }

  /**
   * Create thumbnail.
   *
   * @param \Drupal\media_entity\MediaInterface $media
   *   Media entity.
   * @param bool $overwrite
   *   Whether the thumbnail should be overwritten if one is found.
   *
   * @return string|bool
   *   Thumbnail URI of the media or false.
   */
  public function createThumbnail(MediaInterface $media, $overwrite = FALSE) {
    $thumbnail_uri = $this->getThumbnailUri($media);
    $output = NULL;

    if (!$overwrite && file_exists($thumbnail_uri)) {
      $output = $thumbnail_uri;
    }
    else {
      $svg2png_path = \Drupal::config('media_entity_icon.settings')
        ->get('svg2png_path');
      if (empty($svg2png_path)) {
        return FALSE;
      }

      $thumbnail_uri = $this->getThumbnailUri($media);
      $thumbnail_path = $this->fileSystem->realpath($thumbnail_uri);
      file_prepare_directory($this->thumbnailDir, FILE_CREATE_DIRECTORY);

      // Try to convert the icon as SVG.
      $icon_id = $this->getField($media, 'id');
      $source_realpath = $this->getField($media, 'source_realpath');
      $svg_data = $this->svgManager->extractIconAsSvg($source_realpath, $icon_id);
      if (!$svg_data) {
        return $this->getDefaultThumbnail();
      }

      // Convert the SVG as a PNG.
      $tmp = $this->fileSystem->tempnam('temporary://', 'icon_');
      $svg_tmp = $this->fileSystem->realpath($tmp . '.svg');
      if (file_put_contents($svg_tmp, $svg_data) === FALSE) {
        drupal_set_message(t('The SVG file could not be prepared.'), 'error');
        return FALSE;
      }

      // Convert via svg2png.
      $png_tmp = $this->fileSystem->realpath($tmp . '.png');
      $cmd = $svg2png_path
        . ' ' . $svg_tmp
        . ' --output=' . $png_tmp;
      $thumbnail_width = \Drupal::config('media_entity_icon.settings')
        ->get('thumbnail_width');
      if (!empty($thumbnail_width)) {
        $cmd .= ' --width=' . $thumbnail_width;
      }
      try {
        exec($cmd);
        if (file_unmanaged_move($png_tmp, $thumbnail_path, FILE_EXISTS_REPLACE)) {
          $output = $thumbnail_uri;
        }
      }
      catch (\Exception $e) {
        $output = FALSE;
      }

      file_unmanaged_delete($svg_tmp);
    }

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function thumbnail(MediaInterface $media) {
    if ($thumbnail_uri = $this->createThumbnail($media)) {
      return $thumbnail_uri;
    }

    return $this->getDefaultThumbnail();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\media_entity\MediaBundleInterface $bundle */
    $bundle = $form_state
      ->getFormObject()
      ->getEntity();
    $allowed_field_types = [
      'string' => 'string',
      'list_string' => 'list_string',
      'entity_reference' => 'entity_reference',
    ];
    $options = [];
    foreach ($this->entityFieldManager->getFieldDefinitions('media', $bundle->id()) as $field_name => $field) {
      if (isset($allowed_field_types[$field->getType()]) && !$field->getFieldStorageDefinition()->isBaseField()) {
        $options[$field_name] = $field->getLabel();
      }
    }

    $form['source_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Field with SVG sprite'),
      '#description' => $this->t('Field on media entity that stores a reference to the SVG sprite. You can create a bundle without selecting a value for this dropdown initially. This dropdown can be populated after adding fields to the bundle.'),
      '#default_value' => empty($this->configuration['source_field']) ? NULL : $this->configuration['source_field'],
      '#options' => $options,
    ];

    $form['id_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Field with ID information'),
      '#description' => $this->t('Field on media entity that stores the symbol ID of the icon. You can create a bundle without selecting a value for this dropdown initially. This dropdown can be populated after adding fields to the bundle.'),
      '#default_value' => empty($this->configuration['id_field']) ? NULL : $this->configuration['id_field'],
      '#options' => $options,
    ];

    return $form;
  }

}
