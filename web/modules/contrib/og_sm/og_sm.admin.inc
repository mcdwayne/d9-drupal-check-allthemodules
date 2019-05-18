<?php

/**
 * @file
 * Admin pages & functionality.
 */

use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeTypeInterface;
use Drupal\og_sm\OgSm;

/**
 * Helper function for the real hook_form_node_type_form_alter().
 *
 * @param array $form
 *   An associative array containing the structure of the form.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The current state of the form.
 *
 * @see og_sm_form_node_type_form_alter()
 */
function _og_sm_form_node_type_form_alter(array &$form, FormStateInterface $form_state) {
  /** @var \Drupal\node\NodeTypeInterface $type */
  $type = $form_state->getFormObject()->getEntity();

  $form['og_sm'] = [
    '#type' => 'details',
    '#title' => t('Site Manager'),
    '#collapsible' => TRUE,
    '#group' => 'additional_settings',
    '#description' => t('This bundle may serve as a group, may belong to a group, or may not participate in OG at all.'),
  ];
  $description = [
    t('Make this content type a Site type.'),
    t('All nodes of this type will be recognized as being a Site.'),
    '<p><strong>' . t('WARNING: The content type needs also to be a Group type!') . '</strong></p>',
  ];
  $form['og_sm']['og_sm_site_type'] = [
    '#title' => t('Site type'),
    '#type' => 'checkbox',
    '#return_value' => 1,
    '#default_value' => OgSm::isSiteType($type),
    '#element_validate' => ['og_sm_node_type_form_field_validate'],
    '#description' => implode('<br />', $description),
    '#states' => [
      'disabled' => [
        ':input[name="og_is_group"]' => ['checked' => FALSE],
      ],
    ],
  ];
  $form['#entity_builders'][] = 'og_sm_node_type_form_builder';
}

/**
 * Validation of the posted og_sm_site_type field value.
 *
 * This will check if the node type is also a group type.
 *
 * @param array $element
 *   The form element to validate.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The current state of the form.
 * @param array $form
 *   The complete form structure.
 */
function og_sm_node_type_form_field_validate(array $element, FormStateInterface $form_state, array $form) {
  if ($form_state->getValue('og_sm_site_type') && !$form_state->getValue('og_is_group')) {
    $form_state->setError($element, t('A content type can only be a Site if it also a Group type.'));
  }
}

/**
 * Validation of the posted og_sm_site_type field value.
 *
 * This will check if the node type is also a group type.
 *
 * @param string $entity_type
 *   The entity type identifier.
 * @param \Drupal\node\NodeTypeInterface $type
 *   The node type updated with the submitted values.
 * @param array $form
 *   The complete form array.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The current state of the form.*
 */
function og_sm_node_type_form_builder($entity_type, NodeTypeInterface $type, array &$form, FormStateInterface $form_state) {
  OgSm::setSiteType($type, $form_state->getValue('og_sm_site_type'));
}
