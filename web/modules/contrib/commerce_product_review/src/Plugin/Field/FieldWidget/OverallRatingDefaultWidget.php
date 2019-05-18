<?php

namespace Drupal\commerce_product_review\Plugin\Field\FieldWidget;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Field\Annotation\FieldWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements 'commerce_product_review_overall_rating_default' widget plugin.
 *
 * @FieldWidget(
 *   id = "commerce_product_review_overall_rating_default",
 *   label = @Translation("Overall rating"),
 *   field_types = {
 *     "commerce_product_review_overall_rating"
 *   }
 * )
 */
class OverallRatingDefaultWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['#type'] = 'commerce_product_review_overall_rating';
    if (!$items[$delta]->isEmpty()) {
      $element['#default_value'] = $items[$delta]->toOverallProductRating()->toArray();
    }

    return $element;
  }

}
