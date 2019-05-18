<?php

namespace Drupal\media_entity_pinterest\Plugin\media\Source;

use Drupal\media\MediaSourceBase;
use Drupal\media\MediaInterface;
use Drupal\media\MediaSourceFieldConstraintsInterface;

/**
 * Provides media type plugin for Pinterest.
 *
 * @MediaSource(
 *   id = "pinterest",
 *   label = @Translation("Pinterest"),
 *   description = @Translation("Provides business logic and metadata for Pinterest."),
 *   default_thumbnail_filename = "pinterest.png",
 *   allowed_field_types = {
 *     "link", "string", "string_long"
 *   }
 * )
 */
class Pinterest extends MediaSourceBase implements MediaSourceFieldConstraintsInterface {

  /**
   * List of validation regular expressions.
   *
   * @var array
   *
   * possible hostnames:
   *   www.pinterest.com,
   *   pinterest.com,
   *   jp.pinterest.com,
   *   pinterest.jp,
   *   pinterest.co.uk
   */
  public static $validationRegexp = [
    // Match PIN_URL_RE.
    '@^\s*(https?://)?(\w+\.)?pinterest\.([a-zA-Z]+\.)?([a-zA-Z]+)/pin/(?P<id>\d+)/?\s*$$@i' => 'id',
    // Match BOARD_URL_RE.
    '@^\s*(https?://)?(\w+\.)?pinterest\.([a-zA-Z]+\.)?([a-zA-Z]+)/(?P<username>\w+)/(?P<slug>[\w\-_\~]+)/?\s*$@iu' => 'board',
    // Match BOARD_SECTION_URL_RE.
    '@^\s*(https?://)?(\w+\.)?pinterest\.([a-zA-Z]+\.)?([a-zA-Z]+)/(?P<username>\w+)/(?P<slug>[\w\-_\~]+)/(?P<section>[\w\-_\~%]+)/?\s*$@iu' => 'section',
    // Match USER_URL_RE.
    '@^\s*(https?://)?(\w+\.)?pinterest\.([a-zA-Z]+\.)?([a-zA-Z]+)/(?P<username>\w+)/?\s*$@iu' => 'user',
  ];

  /**
   * {@inheritdoc}
   */
  public function getMetadataAttributes() {
    $fields = [
      'id' => $this->t('Pin ID'),
      'board' => $this->t('Board name'),
      'section' => $this->t('Section name'),
      'user' => $this->t('Pinterest user'),
    ];
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadata(MediaInterface $media, $name) {
    $matches = $this->matchRegexp($media);

    if (empty($matches)) {
      return NULL;
    }

    // First we return the fields that are available from regex.
    switch ($name) {
      case 'thumbnail_uri':
        if ($local_image = $this->getMetadata($media, 'thumbnail_local')) {
          return $local_image;
        }

        return parent::getMetadata($media, 'thumbnail_uri');

      case 'default_name':
        $id = $this->getMetadata($media, 'id');
        $board = $this->getMetadata($media, 'board');
        $section = $this->getMetadata($media, 'section');
        $user = $this->getMetadata($media, 'user');
        // The default name will be the Pin ID for Pins.
        if (!empty($id)) {
          return $id;
        }
        // The default name will be the username, board slug, and section
        // for Sections.
        if (!empty($user) && !empty($board) && !empty($section)) {
          return $user . ' - ' . $board . ' - ' . $section;
        }
        // The default name will be the username and board slug for Boards.
        if (!empty($user) && !empty($board)) {
          return $user . ' - ' . $board;
        }
        // The default name will be the username for Profiles.
        if (!empty($user) && empty($board)) {
          return $user;
        }
        return parent::getMetadata($media, 'default_name');

      case 'id':
        if (!empty($matches['id'])) {
          return $matches['id'];
        }
        return NULL;

      case 'section':
        if (!empty($matches['section'])) {
          return $matches['section'];
        }
        return NULL;

      case 'board':
        if (!empty($matches['slug'])) {
          return $matches['slug'];
        }
        return NULL;

      case 'user':
        if (!empty($matches['username'])) {
          return $matches['username'];
        }
        return NULL;

      default:
        return parent::getMetadata($media, $name);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceFieldConstraints() {
    return ['PinEmbedCode' => []];
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

    $source_field = $this->getSourceFieldDefinition($media->bundle->entity)->getName();
    if ($media->hasField($source_field)) {
      $property_name = $media->{$source_field}->first()->mainPropertyName();
      foreach (static::$validationRegexp as $pattern => $key) {
        // URLs will sometimes have urlencoding, so we decode for matching.
        if (preg_match($pattern, urldecode($media->{$source_field}->{$property_name}), $matches)) {
          return $matches;
        }
      }
    }
    return FALSE;
  }

}
