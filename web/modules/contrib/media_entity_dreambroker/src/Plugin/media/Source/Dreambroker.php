<?php

namespace Drupal\media_entity_dreambroker\Plugin\media\Source;

use Drupal\media\MediaInterface;
use Drupal\media\MediaSourceBase;
use Drupal\media\MediaTypeInterface;
use Drupal\media\MediaSourceFieldConstraintsInterface;

/**
 * Provides a media source plugin for Dream Broker resources.
 *
 * @MediaSource(
 *   id = "dreambroker",
 *   label = @Translation("Dream Broker"),
 *   description = @Translation("Provides business logic and metadata for Dream Broker."),
 *   allowed_field_types = {"string", "string_long", "link"},
 *   default_thumbnail_filename = "dreambroker.png"
 * )
 */
class Dreambroker extends MediaSourceBase implements MediaSourceFieldConstraintsInterface {

  /**
   * List of validation regular expressions.
   *
   * @var array
   */
  public static $validationRegexp = [
    '#(?:https?:\/\/)?(?:www\.)?(?:dreambroker\.com\/(?:channel\/(?<channelid>[a-z0-9]{8})\/(?<videoid>[a-z0-9]{8})))#' => 'id',
  ];

  /**
   * {@inheritdoc}
   */
  public function getMetadataAttributes() {
    $fields = [
      'channelid' => $this->t('Dream Broker channel id'),
      'videoid' => $this->t('Dream Broker video id'),
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadata(MediaInterface $media, $attribute_name) {
    $matches = $this->matchRegexp($media);

    if (!$matches['channelid'] && !$matches['videoid']) {
      return FALSE;
    }

    if (!empty($matches[$attribute_name])) {
      return $matches[$attribute_name];
    }

    // Special case to download a thumbnail locally.
    if ($attribute_name == 'thumbnail_uri') {
      $directory = $this->configFactory->get('media_entity_dreambroker.settings')->get('local_images');
      if (!file_exists($directory)) {
        file_prepare_directory($directory, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS);
      }

      $local_uri = $this->configFactory->get('media_entity_dreambroker.settings')->get('local_images') . '/' . $matches['videoid'] . '.' . 'png';
      if (file_exists($local_uri)) {
        return $local_uri;
      }
      else {
        $url = 'https://dreambroker.com/channel/' . $matches['channelid'] . '/' . $matches['videoid'] . '/get/poster.png';
        try {
          $image_data = file_get_contents($url);
        }
        catch (\Exception $exception) {
          $image_data = FALSE;
        }

        if ($image_data) {
          return file_unmanaged_save_data($image_data, $local_uri, FILE_EXISTS_REPLACE);
        }
      }
    }

    return parent::getMetadata($media, $attribute_name);
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceFieldConstraints() {
    return ['DreambrokerEmbedCode' => []];
  }

  /**
   * {@inheritdoc}
   */
  public function createSourceField(MediaTypeInterface $type) {
    return parent::createSourceField($type)->set('label', 'Dream Broker Url');
  }

  /**
   * Runs preg_match on embed code/URL.
   *
   * @param \Drupal\media\MediaInterface $media
   *   Media object.
   *
   * @return array|bool
   *   Array of preg matches or FALSE if no match.
   *
   * @see preg_match()
   */
  protected function matchRegexp(MediaInterface $media) {
    $matches = [];

    if (isset($this->configuration['source_field'])) {
      $source_field = $this->configuration['source_field'];
      if ($media->hasField($source_field)) {
        $property_name = $media->{$source_field}->first()->mainPropertyName();
        foreach (static::$validationRegexp as $pattern => $key) {
          if (preg_match($pattern, $media->{$source_field}->{$property_name}, $matches)) {
            return $matches;
          }
        }
      }
    }
    return FALSE;
  }

}
