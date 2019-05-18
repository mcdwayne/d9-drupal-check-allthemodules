<?php

namespace Drupal\media_entity_image_exif\Plugin\media\Source;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormStateInterface;
use Drupal\media\MediaInterface;
use Drupal\media\Plugin\media\Source\Image;

/**
 * Image entity media source, with EXIF-handling capabilities.
 *
 * @see \Drupal\media\Plugin\media\Source\Image
 */
class ImageWithExif extends Image {

  /**
   * The exif data.
   *
   * @var array
   */
  protected $exif;

  /**
   * {@inheritdoc}
   */
  public function getMetadataAttributes() {
    $attributes = parent::getMetadataAttributes();

    $attributes += [
      static::METADATA_ATTRIBUTE_WIDTH => $this->t('Width'),
      static::METADATA_ATTRIBUTE_HEIGHT => $this->t('Height'),
    ];

    if (!empty($this->configuration['gather_exif'])) {
      $attributes += [
        'model' => $this->t('Camera model'),
        'created' => $this->t('Image creation datetime'),
        'iso' => $this->t('Iso'),
        'exposure' => $this->t('Exposure time'),
        'aperture' => $this->t('Aperture value'),
        'focal_length' => $this->t('Focal length'),
      ];
    }

    return $attributes;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['gather_exif'] = [
      '#type' => 'select',
      '#title' => $this->t('Whether to gather exif data.'),
      '#description' => $this->t('Gather exif data using exif_read_data().'),
      '#default_value' => empty($this->configuration['gather_exif']) || !function_exists('exif_read_data') ? 0 : $this->configuration['gather_exif'],
      '#options' => [
        0 => $this->t('No'),
        1 => $this->t('Yes'),
      ],
      '#disabled' => (function_exists('exif_read_data')) ? FALSE : TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadata(MediaInterface $media, $name) {
    // Get the file and image data.
    /** @var \Drupal\file\FileInterface $file */
    $file = $media->get($this->configuration['source_field'])->entity;
    // If the source field is not required, it may be empty.
    if (!$file) {
      return parent::getMetadata($media, $name);
    }

    $uri = $file->getFileUri();
    $image = $this->imageFactory->get($uri);
    switch ($name) {
      case static::METADATA_ATTRIBUTE_WIDTH:
        return $image->getWidth() ?: NULL;

      case static::METADATA_ATTRIBUTE_HEIGHT:
        return $image->getHeight() ?: NULL;

      case 'thumbnail_uri':
        return $uri;
    }

    if (!empty($this->configuration['gather_exif']) && function_exists('exif_read_data')) {
      switch ($name) {
        case 'model':
          return $this->getExifField($uri, 'Model');

        case 'created':
          $date = new DrupalDateTime($this->getExifField($uri, 'DateTimeOriginal'));
          return $date->getTimestamp();

        case 'iso':
          return $this->getExifField($uri, 'ISOSpeedRatings');

        case 'exposure':
          return $this->getExifField($uri, 'ExposureTime');

        case 'aperture':
          return $this->getExifField($uri, 'FNumber');

        case 'focal_length':
          return $this->getExifField($uri, 'FocalLength');
      }
    }

    return parent::getMetadata($media, $name);
  }

  /**
   * Get exif field value.
   *
   * @param string $uri
   *   The uri for the file that we are getting the Exif.
   * @param string $field
   *   The name of the exif field.
   *
   * @return string|bool
   *   The value for the requested field or FALSE if is not set.
   */
  protected function getExifField($uri, $field) {
    if (empty($this->exif)) {
      $this->exif = $this->getExif($uri);
    }
    return !empty($this->exif[$field]) ? $this->exif[$field] : FALSE;
  }

  /**
   * Read EXIF.
   *
   * @param string $uri
   *   The uri for the file that we are getting the Exif.
   *
   * @return array|bool
   *   An associative array where the array indexes are the header names and
   *   the array values are the values associated with those headers or FALSE
   *   if the data can't be read.
   */
  protected function getExif($uri) {
    return exif_read_data($uri, 'EXIF');
  }

}
