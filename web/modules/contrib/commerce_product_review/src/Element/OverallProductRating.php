<?php

namespace Drupal\commerce_product_review\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;

/**
 * Provides a product overall rating form element.
 *
 * Usage example:
 * @code
 * $form['rating'] = [
 *   '#type' => 'commerce_product_review_overall_rating',
 *   '#title' => $this->t('Rating'),
 *   '#default_value' => ['score' => '4.5', 'count' => 5],
 *   '#size' => 10,
 *   '#maxlength' => 5,
 *   '#required' => TRUE,
 * ];
 * @endcode
 *
 * @FormElement("commerce_product_review_overall_rating")
 */
class OverallProductRating extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#size' => 10,
      '#maxlength' => 5,
      '#default_value' => NULL,
      '#process' => [
        [$class, 'processElement'],
        [$class, 'processAjaxForm'],
        [$class, 'processGroup'],
      ],
      '#pre_render' => [
        [$class, 'preRenderGroup'],
      ],
      '#input' => TRUE,
      '#theme_wrappers' => ['container'],
    ];
  }

  /**
   * Builds the form element.
   *
   * @param array $element
   *   The initial form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The built form element.
   *
   * @throws \InvalidArgumentException
   *   Thrown when #default_value is invalid.
   */
  public static function processElement(array $element, FormStateInterface $form_state, array &$complete_form) {
    $default_value = $element['#default_value'];
    if (isset($default_value) && !self::validateDefaultValue($default_value)) {
      throw new \InvalidArgumentException('The #default_value for a commerce_product_review_overall_rating element must be an array containing "score" and "count" keys.');
    }

    $element['#tree'] = TRUE;
    $element['#attributes']['class'][] = 'form-type-commerce-product-rating-overall';

    $element['score'] = [
      '#type' => 'commerce_number',
      '#title' => $element['#title'],
      '#title_display' => $element['#title_display'],
      '#default_value' => $default_value ? $default_value['score'] : NULL,
      '#required' => $element['#required'],
      '#size' => $element['#size'],
      '#maxlength' => $element['#maxlength'],
      '#min_fraction_digits' => 0,
      '#max_fraction_digits' => 3,
      '#min' => 0,
    ];
    if (isset($element['#ajax'])) {
      $element['score']['#ajax'] = $element['#ajax'];
    }

    $element['count'] = [
      '#type' => 'number',
      '#title' => t('Count'),
      '#default_value' => $default_value ? $default_value['count'] : NULL,
      '#title_display' => $element['#title_display'],
      '#field_suffix' => '',
    ];
    if (isset($element['#ajax'])) {
      $element['count']['#ajax'] = $element['#ajax'];
    }

    // Add the help text if specified.
    if (!empty($element['#description'])) {
      $element['count']['#field_suffix'] .= '<div class="description">' . $element['#description'] . '</div>';
    }
    // Remove the keys that were transferred to child elements.
    unset($element['#size']);
    unset($element['#maxlength']);
    unset($element['#ajax']);

    return $element;
  }

  /**
   * Validates the default value.
   *
   * @param mixed $default_value
   *   The default value.
   *
   * @return bool
   *   TRUE if the default value is valid, FALSE otherwise.
   */
  public static function validateDefaultValue($default_value) {
    if (!is_array($default_value)) {
      return FALSE;
    }
    if (!array_key_exists('score', $default_value) || !array_key_exists('count', $default_value)) {
      return FALSE;
    }
    return TRUE;
  }

}
