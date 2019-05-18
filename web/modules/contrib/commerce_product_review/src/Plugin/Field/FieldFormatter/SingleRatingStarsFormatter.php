<?php

namespace Drupal\commerce_product_review\Plugin\Field\FieldFormatter;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Field\Annotation\FieldFormatter;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Implements the 'commerce_product_review_single_rating_stars' formatter.
 *
 * This field formatter shows the rating value as stars, based on the
 * rateit.js library.
 *
 * @FieldFormatter(
 *   id = "commerce_product_review_single_rating_stars",
 *   label = @Translation("Stars"),
 *   field_types = {
 *     "integer"
 *   }
 * )
 */
class SingleRatingStarsFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $rating = $item->value;
      $elements[$delta] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#attributes' => [
          'class' => ['rateit'],
          'data-rateit-value' => $rating,
          'data-rateit-ispreset' => 'true',
          'data-rateit-readonly' => 'true',
        ],
      ];
    }
    $elements['#attached']['library'] = ['commerce_product_review/rateitjs'];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $entity_type = $field_definition->getTargetEntityTypeId();
    $field_name = $field_definition->getName();
    return $entity_type == 'commerce_product_review' && $field_name == 'rating_value';
  }

}
