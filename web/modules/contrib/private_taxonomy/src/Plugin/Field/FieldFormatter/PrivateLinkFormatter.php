<?php

namespace Drupal\private_taxonomy\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;

/**
 * Implementation of the 'private_taxonomy_term_reference_link' formatter.
 *
 * @FieldFormatter(
 *   id = "private_taxonomy_term_reference_link",
 *   label = @Translation("Private Link"),
 *   field_types = {
 *     "private_taxonomy_term_reference"
 *   }
 * )
 */
class PrivateLinkFormatter extends PrivateTaxonomyFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    // Terms without target_id do not exist yet, theme such terms as just their
    // name.
    foreach ($items as $delta => $item) {
      if (!$item->target_id) {
        $elements[$delta] = [
          '#plain_text' => $item->entity->label(),
        ];
      }
      else {
        $term = $item->entity;
        $elements[$delta] = [
          '#type' => 'link',
          '#title' => $term->getName(),
          '#url' => $term->urlInfo(),
        ];

        if (!empty($item->_attributes)) {
          $options = $elements[$delta]['#url']->getOptions();
          $options += ['attributes' => []];
          $options['attributes'] += $item->_attributes;
          $elements[$delta]['#url']->setOptions($options);
          // Unset field item attributes since they have been included in the
          // formatter output and should not be rendered in the field template.
          unset($item->_attributes);
        }

        $elements[$delta]['#cache']['tags'] = $item->entity->getCacheTags();
      }
    }

    return $elements;
  }

}
