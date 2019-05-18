<?php

/**
 * @file
 * Node type form alterations.
 */

use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeTypeInterface;

/**
 * Add Parse.ly Tag configuration options to the node type form.
 *
 * @see parsely_tag_form_node_type_form_alter()
 */
function _parsely_tag_form_node_type_form_alter(array &$form, FormStateInterface $form_state) {
  /** @var \Drupal\node\NodeTypeInterface $type */
  $type = $form_state->getFormObject()->getEntity();
  $permission = 'edit ' . $type->id() . ' parsely tag settings';
  if (\Drupal::currentUser()->hasPermission($permission)) {
    $config = \Drupal::config('parsely_tag.settings');
    $form['#attached']['library'][] = 'parsely_tag/summary-tabs';

    $form['parsely_tag'] = [
      '#type' => 'details',
      '#title' => t('Parse.ly Tag settings'),
      '#group' => 'additional_settings',
    ];

    $form['parsely_tag']['parsely_tag_enable'] = [
      '#title' => t('Include a Parse.ly tag for nodes of this type.'),
      '#type' => 'checkbox',
      '#default_value' => $type->getThirdPartySetting(
        'parsely_tag',
        'enable',
        $config->get('default_enable')
      ),
    ];

    foreach (_parsely_tag_properties() as $property => $info) {
      $form['parsely_tag']['parsely_tag_' . $property] = [
        '#title' => $info['title'],
        '#description' => $info['description'],
        '#type' => 'textfield',
        '#default_value' => $type->getThirdPartySetting(
          'parsely_tag',
          $property,
          $config->get('default_' . $property)
        ),
        '#element_validate' => ['token_element_validate'],
        '#token_types' => ['node'],
      ];
    }

    $form['parsely_tag']['token_tree'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => ['node'],
      '#show_restricted' => TRUE,
    ];

    $form['#entity_builders'][] = 'parsely_tag_form_node_type_form_builder';
  }
}

/**
 * Entity builder for the node type form with Parse.ly Tag properties.
 *
 * @see _parsely_tag_form_node_type_form_alter()
 */
function parsely_tag_form_node_type_form_builder($entity_type, NodeTypeInterface $type, &$form, FormStateInterface $form_state) {
  $type->setThirdPartySetting(
    'parsely_tag',
    'enable',
    $form_state->getValue('parsely_tag_enable')
  );
  foreach (_parsely_tag_properties() as $property => $info) {
    $type->setThirdPartySetting(
      'parsely_tag',
      $property,
      $form_state->getValue('parsely_tag_' . $property)
    );
  }
}
