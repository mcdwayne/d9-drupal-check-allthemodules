<?php

/**
 * @file
 * Admin pages & functionality.
 */

use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;
use Drupal\node\Entity\NodeType;
use Drupal\og_sm\OgSm;
use Drupal\og_sm_path\OgSmPath;

/**
 * Adds the site-path related field and functionality to the Site node forms.
 *
 * @param array $form
 *   The form structure.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The form state.
 *
 * @see og_sm_pathauto_form_node_form_alter()
 */
function _og_sm_path_form_node_site_form_alter(array &$form, FormStateInterface $form_state) {
  /* @var \Drupal\node\NodeInterface $site */
  $site = $form_state->getFormObject()->getEntity();

  $default = NULL;
  if (!$site->isNew()) {
    $default = OgSmPath::sitePathManager()->getPathFromSite($site);
  }

  $base_path = Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString();
  // Add the Site Path field.
  $form['site_path'] = [
    '#type' => 'textfield',
    '#maxlength' => 30,
    '#title' => t('Site Path'),
    '#description' => t('Choose a Site path. May contain only lowercase letters, numbers and dashes. e.g. "my-site".'),
    '#default_value' => $default,
    '#required' => TRUE,
    '#element_validate' => ['_og_sm_path_field_site_path_validate'],
    '#field_prefix' => trim($base_path, '/'),
  ];

  // Check access to the Site path field.
  if (!$site->isNew() && !OgSmPath::changeAccess($site)) {
    $form['site_path']['#access'] = FALSE;
  }

  // Disable access to the pathauto settings as we use the Site Path value here!
  $form['path']['#access'] = FALSE;
  $form['#entity_builders'][] = 'og_sm_path_site_node_form_builder';
}

/**
 * Site Path field validator.
 *
 * Will check if the site path has a valid structure and is not already in use.
 *
 * @param array $element
 *   The element info.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The current form state.
 * @param array $form
 *   The form structure.
 *
 * @see _og_sm_pathauto_form_node_site_form_alter()
 */
function _og_sm_path_field_site_path_validate(array $element, FormStateInterface $form_state, array $form) {
  // Check if there is already a Site with the same path.
  $path = $element['#value'];

  if ($path && $path[0] !== '/') {
    $form_state->setError($element, t('The alias needs to start with a slash.'));
    return;
  }

  // Check format.
  if (!preg_match('/^\/[a-z0-9-]+$/', $path)) {
    $form_state->setError($element, t('The Site path may contain only lowercase letters, numbers and dashes.'));
    return;
  }

  // Check if the path is not already used by another Site.
  /* @var \Drupal\node\NodeInterface $current_site */
  $current_site = $form_state->getFormObject()->getEntity();
  $site_path_manager = OgSmPath::sitePathManager();
  foreach (OgSm::siteManager()->getAllSites() as $site) {
    if ($site->id() === $current_site->id()) {
      continue;
    }

    if ($site_path_manager->getPathFromSite($site) === $path) {
      $args = ['%site_path' => $path];
      $form_state->setError($element, t('The Site path %site_path is already in use.', $args));
      return;
    }
  }

  $form_state->setValue(['path', 0, 'pathauto'], 1);
}

/**
 * Validation of the posted og_sm_site_type field value.
 *
 * @param string $entity_type
 *   The entity type identifier.
 * @param \Drupal\node\NodeInterface $node
 *   The node updated with the submitted values.
 * @param array $form
 *   The complete form array.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The current state of the form.
 */
function og_sm_path_site_node_form_builder($entity_type, NodeInterface $node, array &$form, FormStateInterface $form_state) {
  $node->site_path = $form_state->getValue('site_path');
}

/**
 * Alter the Pathauto patterns form.
 *
 * Replace the patterns for Site content types by a message.
 *
 * @param array $form
 *   The form structure.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The current state of the form.
 *
 * @see og_sm_pathauto_form_node_form_alter()
 */
function _og_sm_path_form_pathauto_patterns_form_alter(array &$form, FormStateInterface $form_state) {
  /* @var \Drupal\pathauto\PathautoPatternInterface $pattern */
  $pattern = $form_state->getFormObject()->getEntity();

  if ($pattern->isNew()) {
    return;
  }

  $default_bundle = NULL;
  foreach ($pattern->getSelectionConditions() as $condition_id => $condition) {
    if (in_array($condition->getPluginId(), ['entity_bundle:node', 'node_type'], TRUE)) {
      $default_bundle = reset($condition->getConfiguration()['bundles']);
      break;
    }
  }

  if (!$default_bundle) {
    return;
  }
  $node_type = NodeType::load($default_bundle);
  if (!OgSm::siteTypeManager()->isSiteType($node_type)) {
    return;
  }

  $form['#disabled'] = TRUE;
  $args = ['%type' => $node_type->label()];
  drupal_set_message(
    t('The %type node type is a Site type, these are aliased using the Site path settings. The path is set in the create/update node form.', $args)
  );
}
