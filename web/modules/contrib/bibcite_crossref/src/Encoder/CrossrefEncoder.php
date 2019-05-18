<?php

namespace Drupal\bibcite_crossref\Encoder;

use Symfony\Component\Serializer\Encoder\DecoderInterface;

/**
 * Crossref format encoder.
 */
class CrossrefEncoder implements DecoderInterface {

  /**
   * The format that this encoder supports.
   *
   * @var array
   */
  protected static $format = 'crossref';

  /**
   * {@inheritdoc}
   */
  public function supportsDecoding($format) {
    return $format === self::$format;
  }

  /**
   * {@inheritdoc}
   */
  public function decode($data, $format, array $context = []) {
    $json = json_decode($data, TRUE);
    $result = [];
    foreach ($json['message'] as $key => $value) {
      switch ($key) {
        case 'chair':
          foreach ($value as $contributor) {
            $result['author'][] = ['value' => $contributor, 'role' => 'author'];
          }
          break;

        case 'author':
        case 'editor':
        case 'translator':
          foreach ($value as $contributor) {
            $result['author'][] = ['value' => $contributor, 'role' => $key];
          }
          break;

        case 'subject':
          $result[$key] = $value;
          break;

        case 'ISSN':
          $result[$key] = implode(', ', $value);
          break;

        case 'published-print':
        case 'published-online':
          if (isset($value['date-parts'], $value['date-parts'][0])) {
            $result[$key] = reset($value['date-parts'][0]);
          }
          break;

        case 'URL':
          $result['link'][] = $value;
          break;

        case 'link':
          foreach ($value as $link) {
            $result[$key][] = $link['URL'];
          }
          break;

        default:
          if (is_array($value)) {
            $result[$key] = reset($value);
          }
          else {
            $result[$key] = $value;
          }
          break;
      }
    }
    return $result;
  }

}
