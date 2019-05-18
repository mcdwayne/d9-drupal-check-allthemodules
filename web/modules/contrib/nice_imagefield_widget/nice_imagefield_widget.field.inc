<?php

/**
 * @file
 * Field module functionality for the Nice ImageField Widget module.
 */

use Drupal\Core\Render\Element;
use Drupal\image\Entity\ImageStyle;
use Drupal\Core\Template\Attribute;

/**
 * Prepares variables for multi file form widget templates.
 *
 * Default template: nice-imagefield-widget-multiple.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: A render element representing the widgets.
 */
function template_preprocess_nice_imagefield_widget_multiple(&$variables)
{
    $element = $variables['element'];

    // Special ID and classes for draggable tables.
    $weight_class = $element['#id'] . '-weight';
    $sortable_id = $element['#id'] . '-sortable';

    // Get our list of widgets in order (needed when the form comes back after
    // preview or failed validation).
    $widgets = array();
    foreach (Element::children($element) as $key) {
        $widgets[] = &$element[$key];
    }
    usort($widgets, '_field_multiple_value_form_sort_helper');

    $rows = array();
    foreach ($widgets as $key => &$widget) {
        // TODO: need try to optimize.
        $imageStyle = ImageStyle::load($widget['#preview_image_style'])
                                ->getEffects();
        $effects = array();
        foreach ($imageStyle->getInstanceIds() as $effect) {
            $effects[] = $effect;
        }

        $summary = $imageStyle->get(end($effects))->getSummary();
        $attributes = new Attribute(array(
                                        'style' => 'height:' . $summary['#data']['height'] . 'px; width:' . $summary['#data']['width'] . 'px;',
                                    ));

        // Save the uploading row for last.
        if (empty($widget['#files'])) {
            $widget['#title'] = $element['#file_upload_title'];
            $widget['#description'] = \Drupal::service('renderer')
                                             ->renderPlain($element['#file_upload_description']);
            continue;
        }

        // Delay rendering of the buttons, so that they can be rendered later in the
        // "operations" column.
        $operations_elements = array();
        foreach (Element::children($widget) as $sub_key) {
            if (isset($widget[$sub_key]['#type']) && $widget[$sub_key]['#type'] == 'submit') {
                hide($widget[$sub_key]);
                $operations_elements[] = &$widget[$sub_key];
            }
        }

        hide($widget['_weight']);

        // Render everything else together in a column, without the normal wrappers.
        $widget['#theme_wrappers'] = array();
        $preview = \Drupal::service('renderer')
                          ->render($widget['preview'], FALSE);
        $widget['_weight']['#attributes']['class'] = array($weight_class);
        unset($widget['preview']);

        $widget['actions'] = array(
            '#type' => 'container',
            '#attributes' => array(
                'class' => array(
                    'operations',
                ),
            ),
            '#weight' => 100,
        );

        $widget['actions']['ok'] = array(
            '#type' => 'button',
            '#value' => t('Ok'),
            '#attributes' => array(
                'class' => array('flip-button', 'flip-front'),
            ),
        );

        $widget['title']['#attributes']['title'] = $widget['title']['#description'];
        unset($widget['title']['#description']);
        $widget['alt']['#attributes']['title'] = $widget['alt']['#description'];
        unset($widget['alt']['#description']);

        $edit = render($widget);

        // Arrange the row with all of the rendered columns.
        $row = array();
        $row['preview'] = $preview;
        $row['edit'] = $edit;
        $row['weight'] = render($widget['_weight']);

        // Show the buttons that had previously been marked as hidden in this
        // pre-process function. We use show() to undo the earlier hide().
        foreach (Element::children($operations_elements) as $key) {
            show($operations_elements[$key]);
        }

        $operations_elements[] = array(
            '#type' => 'button',
            '#value' => t('Edit'),
            '#attributes' => array(
                'class' => array('flip-button', 'flip-back'),
            ),
        );

        $row['operations'] = $operations_elements;
        $row['attributes'] = $attributes;
        $rows[] = $row;
    }

    $variables['id'] = $sortable_id;
    $variables['items'] = $rows;
    $variables['element'] = $element;
}
