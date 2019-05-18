<?php

namespace Drupal\media_entity_smugmug\Plugin\media\Source;

use Drupal\media\MediaInterface;
use Drupal\media\MediaSourceBase;
use Drupal\media\MediaSourceFieldConstraintsInterface;

/**
 * SmugMug entity media source.
 *
 * @MediaSource(
 *   id = "smugmug",
 *   label = @Translation("SmugMug"),
 *   description = @Translation("Provides business logic and metadata for SmugMug."),
 *   allowed_field_types = {"string_long"},
 *   default_thumbnail_filename = "smugmug.png"
 * )
 */
class SmugMug extends MediaSourceBase implements MediaSourceFieldConstraintsInterface {

  const IFRAME_TEMPLATE = '<iframe class="smugmug-gallery" id="smugmug-gallery-@id" src="@url" width="@width" frameborder="no" scrolling="no"></iframe>';

  /**
   *
   */
  private function getDefaultIframeQuerySettings() {
    return [
    // 'key' => '',.
      'autoStart' => '1',
      'captions' => '1',
      'navigation' => '1',
      'playButton' => '1',
      'randomize' => '0',
      'speed' => '3',
      'transition' => 'fade',
      'transitionSpeed' => '2',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadataAttributes() {
    return [
    // 'author_name',
    //      'width',
    //      'height',
    //      'url',.
      'html' => $this->t('HTML'),
    ];
  }

  /**
   * Returns the iframe embed for a SmugMug post.
   *
   * @return string
   */
  protected function getEmbed($content_url, $id) {
    $embed = str_replace('@url', $content_url, self::IFRAME_TEMPLATE);
    $embed = str_replace('@width', '100%', $embed);
    $embed = str_replace('@id', $id, $embed);

    return $embed;
  }

  /**
   * Runs preg_match on embed code/URL.
   *
   * @param \Drupal\media\MediaInterface $media
   *   Media object.
   *
   * @return string|false
   *   The smugmug url or FALSE if there is no field or it contains invalid
   *   data.
   */
  protected function getSmugMugUrl(MediaInterface $media) {
    if (empty($this->configuration['source_field'])) {
      return FALSE;
    }

    $source_field = $this->configuration['source_field'];

    if (!$media->hasField($source_field)) {
      return FALSE;
    }

    $property_name = $media->{$source_field}->first()->mainPropertyName();
    $embed = $media->{$source_field}->{$property_name};

    return static::parseSmugMugEmbedField($embed);
  }

  /**
   * Extract a SmugMug content URL from a string.
   *
   * Typically users will enter an iframe embed code that SmugMug provides, so
   * which needs to be parsed to extract the actual post URL.
   *
   * Users may also enter the actual content URL - in which case we just return
   * the value if it matches our expected format.
   *
   * @param string $data
   *   The string that contains the SmugMug post URL.
   *
   * @return string|bool
   *   The post URL, or FALSE if one cannot be found.
   */
  public static function parseSmugMugEmbedField($data) {
    $data = trim($data);
    // Ideally we would verify that the content URL matches an exact pattern,
    // but SmugMug has a ton of different ways posts/notes/videos/etc URLs can
    // be formatted, so it's not practical to try and validate them. Instead,
    // just validate that the content URL is from the smugmug domain.
    $content_url_regex = '/^https:\/\/(\w)+\.smugmug\.com\/frame/i';

    if (preg_match($content_url_regex, $data)) {
      return $data;
    }
    // Check if the user entered an iframe embed instead, and if so,
    // extract the post URL from the iframe src.
    $doc = new \DOMDocument();

    if (@$doc->loadHTML($data)) {
      $iframes = $doc->getElementsByTagName('iframe');

      if ($iframes->length > 0 && $iframes->item(0)->hasAttribute('src')) {
        $iframe_src = $iframes->item(0)->getAttribute('src');

        if (preg_match($content_url_regex, $iframe_src)) {
          return $iframe_src;
        }
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadata(MediaInterface $media, $attribute_name) {
    $content_url = $this->getSmugMugUrl($media);

    if ($content_url === FALSE) {
      return FALSE;
    }

    switch ($attribute_name) {
      // Todo: To be implemented.
      //      case 'author_name':
      //        return $data['author_name'];
      //
      //      case 'width':
      //        return $data['width'];
      //
      //      case 'height':
      //        return $data['height'];
      //
      //      case 'url':
      //        return $data['url'];.
      case 'html':
        return $this->getEmbed($content_url, $media->id());

      default:
        return parent::getMetadata($media, $attribute_name);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceFieldConstraints() {
    return ['SmugMugEmbedCode' => []];
  }

}
