<?php
/**
 * @file
 *
 * Template preprocess functions for filebrowser
 */

use Drupal\Core\Template\Attribute;

/**
 * Prepares variables for filebrowser form container template that shows the
 * items in a grid view.
 *
 * The id of the container is defined in service Common::ACTION_FORM_GRID_ID
 *
 * Default template: filebrowser--container.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties of the element.
 *     Properties used: #id, #attributes, #children.
 */
function template_preprocess_filebrowser_container(&$variables) {
  //debug(array_keys($variables));
  //print('<pre>' . print_r(($variables)) . '</pre>');
  $items = [];
  $variables['has_parent'] = FALSE;
  $element = $variables['element'];
  // Ensure #attributes is set.
  $element += ['#attributes' => []];

  // Special handling for form elements.
  if (isset($element['#array_parents'])) {
    // Assign an html ID.
    if (!isset($element['#attributes']['id'])) {
      $element['#attributes']['id'] = $element['#id'];
    }
    $variables['has_parent'] = TRUE;
  }
  $variables['children'] = $element['#children'];
  $variables['attributes'] = $element['#attributes'];
  //debug(count($variables['element']['#render_children']));
  foreach($variables['children'] as $item) {
    $items[] = $item;
  }
  $variables['items'] = $items;
}

/**
 * Returns HTML for a form element.
 * Prepares variables for form element templates.
 *
 * Default template: form-element.html.twig.
 *
 * In addition to the element itself, the DIV contains a label for the element
 * based on the optional #title_display property, and an optional #description.
 *
 * The optional #title_display property can have these values:
 * - before: The label is output before the element. This is the default.
 *   The label includes the #title and the required marker, if #required.
 * - after: The label is output after the element. For example, this is used
 *   for radio and checkbox #type elements. If the #title is empty but the field
 *   is #required, the label will contain only the required marker.
 * - invisible: Labels are critical for screen readers to enable them to
 *   properly navigate through forms but can be visually distracting. This
 *   property hides the label for everyone except screen readers.
 * - attribute: Set the title attribute on the element to create a tooltip
 *   but output no label element. This is supported only for checkboxes
 *   and radios in
 *   \Drupal\Core\Render\Element\CompositeFormElementTrait::preRenderCompositeFormElement().
 *   It is used where a visual label is not needed, such as a table of
 *   checkboxes where the row and column provide the context. The tooltip will
 *   include the title and required marker.
 *
 * If the #title property is not set, then the label and any required marker
 * will not be output, regardless of the #title_display or #required values.
 * This can be useful in cases such as the password_confirm element, which
 * creates children elements that have their own labels and required markers,
 * but the parent element should have neither. Use this carefully because a
 * field without an associated label can cause accessibility challenges.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties of the element.
 *     Properties used: #title, #title_display, #description, #id, #required,
 *     #children, #type, #name.
 */
function template_preprocess_filebrowser_form_element(&$variables) {
  $element = &$variables['element'];

  // This function is invoked as theme wrapper, but the rendered form element
  // may not necessarily have been processed by
  // \Drupal::formBuilder()->doBuildForm().
  $element += [
    '#title_display' => 'before',
    '#wrapper_attributes' => [],
  ];
  $variables['attributes'] = $element['#wrapper_attributes'];

  // Add element #id for #type 'item'.
  if (isset($element['#markup']) && !empty($element['#id'])) {
    $variables['attributes']['id'] = $element['#id'];
  }

  // Pass elements #type and #name to template.
  if (!empty($element['#type'])) {
    $variables['type'] = $element['#type'];
  }
  if (!empty($element['#name'])) {
    $variables['name'] = $element['#name'];
  }

  // Pass elements disabled status to template.
  $variables['disabled'] = !empty($element['#attributes']['disabled']) ? $element['#attributes']['disabled'] : NULL;

  // Suppress error messages.
  $variables['errors'] = NULL;

  // If #title is not set, we don't display any label.
  if (!isset($element['#title'])) {
    $element['#title_display'] = 'none';
  }

  $variables['title_display'] = $element['#title_display'];

  $variables['prefix'] = isset($element['#field_prefix']) ? $element['#field_prefix'] : NULL;
  $variables['suffix'] = isset($element['#field_suffix']) ? $element['#field_suffix'] : NULL;

  $variables['description'] = NULL;
  if (!empty($element['#description'])) {
    $variables['description_display'] = $element['#description_display'];
    $description_attributes = [];
    if (!empty($element['#id'])) {
      $description_attributes['id'] = $element['#id'] . '--description';
    }
    $variables['description']['attributes'] = new Attribute($description_attributes);
    $variables['description']['content'] = $element['#description'];
  }

  // Add label_display and label variables to template.
  $variables['label_display'] = $element['#title_display'];
  $variables['label'] = ['#theme' => 'form_element_label'];
  $variables['label'] += array_intersect_key($element, array_flip(['#id', '#required', '#title', '#title_display']));

  $variables['children'] = $element['#children'];
}
