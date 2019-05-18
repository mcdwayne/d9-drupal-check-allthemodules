<?php

namespace Drupal\pseudo_field;

use Drupal\Component\Utility\Html;
use Drupal\Core\Render\Element;

/**
 * Class PseudoFieldProcessor
 */
class PseudoFieldProcessor implements PseudoFieldProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function process(array &$build, $context) {
    try {
      foreach (Element::children($build) as $name) {
        if (!empty($build[$name]['#pseudo_field'])) {
          $build[$name] = $this->transform($build[$name], [
            '#entity' => $context,
            '#entity_type' => $context->getEntityTypeId(),
            '#bundle' => $context->bundle(),
            // Real view mode doesn't work well with some modules (e.g.
            // quickedit). It's not displayed by the "field" theme anyway.
            '#view_mode' => '_custom',
            '#field_machine_name' => $name,
          ]);
        }
      }
    } catch (\Exception $e) {
    }
  }

  /**
   * {@inheritdoc}
   */
  public function transform(array $element, $context) {
    // Return original element if context values are missing.
    if (empty($context['#entity_type']) || empty($context['#bundle']) || empty($context['#view_mode']) || empty($context['#field_machine_name'])) {
      return $element;
    }

    // Remove this pre-render function from the initial element.
    $this->cleanValues($element);

    // Add properties required by the "field" theme.
    $field_name = (isset($element['#field_name']) ? $element['#field_name'] : $context['#field_machine_name']);
    $field_type = (isset($element['#field_type']) ? $element['#field_type'] : 'extra_field');

    if (isset($element['#title']) && $element['#title'] != '') {
      $title = $element['#title'];
      $label_display = isset($element['#label_display']) ? $element['#label_display'] : 'above';
      // Remove from original element to avoid duplication of titles.
      unset($element['#title']);
    }
    else {
      $title = '';
      $label_display = 'hidden';
    }

    $extra_element = [
      '#theme' => 'field',
      '#view_mode' => $context['#view_mode'],
      '#object' => $context['#entity'],
      '#entity_type' => $context['#entity_type'],
      '#bundle' => $context['#bundle'],
      '#title' => $title,
      '#label_display' => $label_display,
      '#field_name' => Html::getClass($field_name),
      '#field_type' => Html::getClass($field_type),
      '#is_multiple' => FALSE,
    ];

    if (isset($element['#weight'])) {
      $extra_element['#weight'] = $element['#weight'];
    }

    if ($children = Element::children($element, TRUE)) {
      $extra_element['#is_multiple'] = TRUE;

      // Without #children field will not show up.
      $extra_element['#children'] = '';

      // Loop through element children.
      foreach ($children as $key) {
        // Only keys in "#items" property are required in
        // template_preprocess_field().
        $extra_element['#items'][$key] = new \stdClass();
        $extra_element[$key] = $element[$key];
      }
    }
    else {
      // Only keys in "#items" property are required in
      // template_preprocess_field().
      $extra_element['#items'][] = new \stdClass();
      $extra_element[] = $element;
    }

    return $extra_element;
  }

  /**
   * {@inheritdoc}
   */
  public function cleanValues(array &$element) {
    unset($element['#pseudo_field']);
  }

}
