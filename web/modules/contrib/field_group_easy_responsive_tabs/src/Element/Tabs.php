<?php

namespace Drupal\field_group_easy_responsive_tabs\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\RenderElement;

/**
 * Provides a render element for tabs.
 *
 * @FormElement("field_group_easy_responsive_tabs")
 */
class Tabs extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);

    return [
      '#type'           => 'field_group_easy_responsive_tabs',
      '#theme_wrappers' => ['field_group_easy_responsive_tabs'],
      '#process'        => [
        [$class, 'processTabs'],
      ],
    ];
  }

  /**
   * Creates a group formatted as tabs.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   details element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param bool $on_form
   *   Are the tabs rendered on a form or not.
   *
   * @return array
   *   The processed element.
   */
  public static function processTabs(&$element, FormStateInterface $form_state, $on_form = TRUE) {
    // Inject a new details as child, so that form_process_details() processes
    // this details element like any other details.
    $element['group'] = [
      '#type'           => 'details',
      '#theme_wrappers' => [],
      '#parents'        => $element['#parents'],
    ];

    // Add an invisible label for accessibility.
    if (!isset($element['#title'])) {
      $element['#title'] = t('Tabs');
      $element['#title_display'] = 'invisible';
    }

    return $element;
  }

}
