<?php

namespace Drupal\media_entity_pinterest\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\media_entity_pinterest\Plugin\media\Source\Pinterest;

/**
 * Plugin implementation of the 'pinterest_embed' formatter.
 *
 * @FieldFormatter(
 *   id = "pinterest_embed",
 *   label = @Translation("Pinterest embed"),
 *   field_types = {
 *     "link", "string", "string_long"
 *   }
 * )
 */
class PinterestEmbedFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    foreach ($items as $delta => $item) {
      $matches = [];
      foreach (Pinterest::$validationRegexp as $pattern => $key) {
        // URLs will sometimes have urlencoding, so we decode for matching.
        if (preg_match($pattern, urldecode($this->getEmbedCode($item)), $item_matches)) {
          $matches[] = $item_matches;
        }
      }

      if (!empty($matches)) {
        $matches = reset($matches);
      }

      // PIN_URL_RE matched.
      if (
          !empty($matches['id']) &&
          isset($matches[2]) &&
          isset($matches[3]) &&
          isset($matches[4])
        ) {
        $element[$delta] = [
          '#theme' => 'media_entity_pinterest_pin',
          '#path' => 'https://' . $matches[2] . 'pinterest.' . $matches[3] . $matches[4] . '/pin/' . $matches['id'],
          '#attributes' => [
            'class' => [],
            'data-conversation' => 'none',
            'lang' => $langcode,
          ],
        ];
      }

      // BOARD_SECTION_URL_RE matched.
      if (
          !empty($matches['username']) &&
          !empty($matches['slug']) &&
          !empty($matches['section']) &&
          isset($matches[2]) &&
          isset($matches[3]) &&
          isset($matches[4])
        ) {
        $element[$delta] = [
          '#theme' => 'media_entity_pinterest_board_section',
          '#path' => 'https://' . $matches[2] . 'pinterest.' . $matches[3] . $matches[4] . '/' . $matches['username'] . '/' . $matches['slug'] . '/' . $matches['section'],
          '#attributes' => [
            'class' => [],
            'data-conversation' => 'none',
            'lang' => $langcode,
          ],
        ];

      }

      // BOARD_URL_RE matched.
      if (
          !empty($matches['username']) &&
          !empty($matches['slug']) &&
          empty($matches['section']) &&
          isset($matches[2]) &&
          isset($matches[3]) &&
          isset($matches[4])
        ) {
        $element[$delta] = [
          '#theme' => 'media_entity_pinterest_board',
          '#path' => 'https://' . $matches[2] . 'pinterest.' . $matches[3] . $matches[4] . '/' . $matches['username'] . '/' . $matches['slug'],
          '#attributes' => [
            'class' => [],
            'data-conversation' => 'none',
            'lang' => $langcode,
          ],
        ];

      }

      // USER_URL_RE matched.
      if (
          !empty($matches['username']) &&
          empty($matches['slug']) &&
          empty($matches['section']) &&
          isset($matches[2]) &&
          isset($matches[3]) &&
          isset($matches[4])
        ) {
        $element[$delta] = [
          '#theme' => 'media_entity_pinterest_profile',
          '#path' => 'https://' . $matches[2] . 'pinterest.' . $matches[3] . $matches[4] . '/' . $matches['username'],
          '#attributes' => [
            'class' => [],
            'data-conversation' => 'none',
            'lang' => $langcode,
          ],
        ];

      }
    }

    if (!empty($element)) {
      $element['#attached'] = [
        'library' => [
          'media_entity_pinterest/integration',
        ],
      ];
    }

    return $element;
  }

  /**
   * Extracts the embed code from a field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   The field item.
   *
   * @return string|null
   *   The embed code, or NULL if the field type is not supported.
   */
  protected function getEmbedCode(FieldItemInterface $item) {
    switch ($item->getFieldDefinition()->getType()) {
      case 'link':
        return $item->uri;

      case 'string':
      case 'string_long':
        return $item->value;

      default:
        break;
    }
  }

  // @TODO: Provide settings form to configure field formatters.
}
