<?php

/**
 * @file
 * Callbacks for adding, editing, and deleting content and managing revisions.
 *
 * Also includes validation, submission and other helper functions.
 *
 * @see node_menu()
 */

use Drupal\Component\Utility\Xss;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\erpal_budget\ErpalBudgetInterface;

/**
 * Prepares variables for list of available erpal_budget type templates.
 *
 * Default template: erpal_budget-add-list.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - content: An array of content types.
 *
 * @see erpal_budget_add_page()
 */
function template_preprocess_erpal_budget_add_list(&$variables) {
  $variables['types'] = array();
  if (!empty($variables['content'])) {
    foreach ($variables['content'] as $type) {
      $variables['types'][$type->id] = array(
        'type' => $type->id,
        'add_link' => \Drupal::l($type->name, new Url('erpal_budget.add', array('erpal_budget_type' => $type->id))),
        'description' => Xss::filterAdmin($type->description),
      );
    }
  }
}
