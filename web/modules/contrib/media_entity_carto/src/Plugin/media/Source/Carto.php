<?php

namespace Drupal\media_entity_carto\Plugin\media\Source;

use Drupal\media\MediaInterface;
use Drupal\media\MediaSourceBase;
use Drupal\media\MediaSourceFieldConstraintsInterface;
use Drupal\media\MediaTypeInterface;

/**
 * CARTO entity media source.
 *
 * @MediaSource(
 *   id = "carto",
 *   label = @Translation("CARTO"),
 *   allowed_field_types = {"string", "string_long", "link"},
 *   default_thumbnail_filename = "carto.png",
 *   description = @Translation("Provides business logic and metadata for CARTO.")
 * )
 */
class Carto extends MediaSourceBase implements MediaSourceFieldConstraintsInterface {

  /**
   * List of validation regular expressions.
   *
   * @var array
   */
  public static $validationRegexp = [
    '@((http|https):){0,1}//(www\.){0,1}(?<user>[a-z0-9_-]+)\.carto\.com/(u/[a-z]+/){0,1}([a-z]+)/(?<id>[a-z0-9_-]+)/embed@i' => 'id',
  ];

  /**
   * {@inheritdoc}
   */
  public function getMetadataAttributes() {
    $fields = [
      'id' => $this->t('Map ID'),
      'user' => $this->t('CARTO user information'),
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadata(MediaInterface $media, $attribute_name) {
    $matches = $this->matchRegexp($media);

    // First we return the fields that are available from regex.
    switch ($attribute_name) {
      case 'id':
      case 'user':
        return $matches[$attribute_name] ?: NULL;

      case 'default_name':
        $user = $this->getMetadata($media, 'user');
        $id = $this->getMetadata($media, 'id');
        if (!empty($user) && !empty($id)) {
          return $user . ' - ' . $id;
        }
    }

    return parent::getMetadata($media, $attribute_name);
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceFieldConstraints() {
    return [
      'CartoEmbedCode' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function createSourceField(MediaTypeInterface $type) {
    return parent::createSourceField($type)->set('label', 'Carto URL');
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
