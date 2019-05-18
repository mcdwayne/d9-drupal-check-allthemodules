<?php

/**
 * @file
 * Admin page callbacks for the context node template module.
 */

use Drupal\Component\Utility\Html;
use Drupal\Core\Render\Element;
use Drupal\Core\Template\Attribute;

/**
 * Prepares variables for context node template setting templates.
 *
 * Default template: context-node-template-setting.html.twig.
 *
 * @param $variables
 *   An associative array containing:
 *   - form: A render element representing the form. Child elements of the form
 *     are individual modules. Each module is an associative array containing
 *     the following elements:
 *     - #node_templates: The template name as a string.
 *
 * @ingroup themeable
 */
function template_preprocess_context_node_template_setting(&$variables) {
  $form = $variables['form'];
  $variables['node_templates'] = [];

  // Iterate through all the node templates, which are children of this element.
  foreach (Element::children($form['node_templates']) as $key) {
    $template = $form['node_templates'][$key];
    $template['template_name'] = $template['#template_name'];
    $template['template_alias'] = $form['node_templates_alias'][$key];
    $template['attributes'] = new Attribute($template['#attributes']);
    $variables['node_templates'][] = $template;
  }
}
