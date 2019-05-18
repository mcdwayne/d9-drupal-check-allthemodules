<?php

/**
 * @file
 * Contains dhis.page.inc.
 *
 * Page callback for Dhis entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Dhis templates.
 *
 * Default template: dhis.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_dhis(array &$variables)
{
    // Fetch Dhis Entity Object.
    $dhis = $variables['elements']['#dhis'];

    // Helpful $content variable for templates.
    foreach (Element::children($variables['elements']) as $key) {
        $variables['content'][$key] = $variables['elements'][$key];
    }
}
