<?php

namespace Drupal\media_entity_issuu\Plugin\media\Source;

use Drupal\media\MediaInterface;
use Drupal\media\MediaSourceBase;
use Drupal\media\MediaSourceFieldConstraintsInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\media\MediaTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Issuu entity media source.
 *
 * @MediaSource(
 *   id = "issuu",
 *   label = @Translation("Issuu"),
 *   description = @Translation("Provides business logic and metadata for Issuu."),
 *   allowed_field_types = {"string_long"},
 *   default_thumbnail_filename = "issuu.png"
 * )
 */
class Issuu extends MediaSourceBase implements MediaSourceFieldConstraintsInterface {

  /**
   * {@inheritdoc}
   */
  public function getMetadataAttributes() {
    return [
//      'width',
//      'height',
      'id' => $this->t('ID'),
      'html' => $this->t('HTML'),
    ];
  }

  /**
   * Returns the embed data for a Issuu post.
   *
   * @param string $configId
   *   The URL to the issuu post.
   *
   * @return bool|array
   *   FALSE if there was a problem retrieving the oEmbed data, otherwise
   *   an array of the data is returned.
   */
  protected function defaultEmbed($configId) {
    $string = '<div data-configid="@config_id" class="issuuembed"></div>';

    return str_replace('@config_id', $configId, $string);
  }

  /**
   * Runs preg_match on embed code/URL.
   *
   * @param MediaInterface $media
   *   Media object.
   *
   * @return string|false
   *   The issuu url or FALSE if there is no field or it contains invalid
   *   data.
   */
  protected function getIssuuUrl(MediaInterface $media) {
    if (empty($this->configuration['source_field'])) {
      return FALSE;
    }

    $source_field = $this->configuration['source_field'];

    if (!$media->hasField($source_field)) {
      return FALSE;
    }

    $property_name = $media->{$source_field}->first()->mainPropertyName();
    $embed = $media->{$source_field}->{$property_name};

    return static::parseIssuuEmbedField($embed);
  }

  /**
   * Extract a Issuu content URL from a string.
   *
   * Typically users will enter an iframe embed code that Issuu provides, so
   * which needs to be parsed to extract the actual post URL.
   *
   * Users may also enter the actual content URL - in which case we just return
   * the value if it matches our expected format.
   *
   * @param string $data
   *   The string that contains the Issuu post URL.
   *
   * @return string|bool
   *   The post URL, or FALSE if one cannot be found.
   */
  public static function parseIssuuEmbedField($data) {
    $data = trim($data);
    // Ideally we would verify that the content URL matches an exact pattern,
    // but Issuu has a ton of different ways posts/notes/videos/etc URLs can
    // be formatted, so it's not practical to try and validate them. Instead,
    // just validate that the content URL is from the issuu domain.
    $content_url_regex = '/^http:\/\/(www\.)?issuu\.com\//i';
    $default_regex = '/data-configid="(\d*\/\d*)"/';
    $item_regex = '/\d*\/\d*/';

    if (preg_match($content_url_regex, $data)) {
      $uri_parts = parse_url($data);

      if ($uri_parts !== FALSE && isset($uri_parts['query'])) {
        parse_str($uri_parts['query'], $query_params);

        if (isset($query_params['e']) && preg_match($item_regex, $query_params['e'])) {
          return $query_params['e'];
        }
      }

      return $data;
    }

    if (preg_match($default_regex, $data, $matches)) {
      if (!empty($matches[1])) {
        return $matches[1];
      }

      return FALSE;
    }

    // Check if the user entered an iframe embed instead, and if so,
    // extract the post URL from the iframe src.
    $doc = new \DOMDocument();

    if (@$doc->loadHTML($data)) {
      $iframes = $doc->getElementsByTagName('iframe');

      if ($iframes->length > 0 && $iframes->item(0)->hasAttribute('src')) {
        $iframe_src = $iframes->item(0)->getAttribute('src');
        $uri_parts = parse_url($iframe_src);

        if ($uri_parts !== FALSE && isset($uri_parts['fragment'])) {
          parse_str($uri_parts['fragment'], $query_params);
          $fragment = array_keys($query_params);

          if (isset($fragment[0]) && preg_match($item_regex, $fragment[0])) {
            return $fragment[0];
          }
        }
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadata(MediaInterface $media, $attribute_name) {
    $content_id = $this->getIssuuUrl($media);

    if ($content_id === FALSE) {
      return FALSE;
    }

    switch ($attribute_name) {
//      Todo: To be implemented.
//      case 'width':
//        return $data['width'];
//
//      case 'height':
//        return $data['height'];
//
      case 'id':
        return $content_id;

      case 'html':
        return $this->defaultEmbed($content_id);

      default:
        return parent::getMetadata($media, $attribute_name);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceFieldConstraints() {
    return ['IssuuEmbedCode' => []];
  }

}
