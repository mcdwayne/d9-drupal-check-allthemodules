<?php

/**
 * @file
 * Contains data_element.page.inc.
 *
 * Page callback for Data element entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Data element templates.
 *
 * Default template: data_element.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_data_element(array &$variables)
{
    // Fetch DataElement Entity Object.
    $data_element = $variables['elements']['#data_element'];

    // Helpful $content variable for templates.
    foreach (Element::children($variables['elements']) as $key) {
        $variables['content'][$key] = $variables['elements'][$key];
    }
}
