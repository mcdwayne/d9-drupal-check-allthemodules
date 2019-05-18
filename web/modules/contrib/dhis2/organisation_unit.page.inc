<?php

/**
 * @file
 * Contains organisation_unit.page.inc.
 *
 * Page callback for Organisation unit entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Organisation unit templates.
 *
 * Default template: organisation_unit.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_organisation_unit(array &$variables)
{
    // Fetch OrganisationUnit Entity Object.
    $organisation_unit = $variables['elements']['#organisation_unit'];

    // Helpful $content variable for templates.
    foreach (Element::children($variables['elements']) as $key) {
        $variables['content'][$key] = $variables['elements'][$key];
    }
}
