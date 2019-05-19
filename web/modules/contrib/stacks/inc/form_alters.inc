<?php

/**
 * @file
 * Contains form_alters.inc.
 */

/**
 * Implements hook_inline_entity_form_entity_form_alter(). This gets hit when
 * adding and updating a form inside of inline_entity_form form field.
 *
 * - Delete the _none option from the multi-select drop downs.
 * - Show/Hide the Taxonomy Vocabulary option if the Taxonomy Term Terms filer is enabled.
 */
function stacks_inline_entity_form_entity_form_alter(&$entity_form, &$form_state) {
  if ($entity_form['#bundle'] == 'link') {
    if (isset($entity_form['field_extend_link_url'])) {
      $entity_form['field_extend_link_url']['widget'][0]['title']['#required'] = TRUE;
      $entity_form['field_extend_link_url']['widget'][0]['title']['#placeholder'] = t('Required link text');
    }
  }

  // Adding custom validation handler for Photo Captions
  if ($entity_form['#bundle'] == 'gallery_image_caption') {
    if (isset($entity_form['field_extend_description'])) {
      $entity_form['#element_validate'][] = [
        'Drupal\stacks\Form\WidgetExtendEntityInlineForm',
        'validatePhotoCaption',
      ];
    }
  }

  if ($entity_form['#entity_type'] == 'widget_entity' && substr($entity_form['#bundle'], 0, 11) === 'contentfeed') {
    unset($entity_form['field_cfeed_content_types']['widget']['#options']['_none']);
    unset($entity_form['field_cfeed_taxonomy_terms']['widget']['#options']['_none']);

    if (!in_array('taxonomy_terms', $entity_form['field_cfeed_enable_filtering']['widget']['#default_value'])) {
      $entity_form['field_cfeed_vocabulary']['#prefix'] = '<div id="filter_by_vocab" class="hide">';
      $entity_form['field_cfeed_vocabulary']['#suffix'] = '</div>';
    }
    else {
      $entity_form['field_cfeed_vocabulary']['#prefix'] = '<div id="filter_by_vocab">';
      $entity_form['field_cfeed_vocabulary']['#suffix'] = '</div>';
    }

    $preview_ajax = [
      'callback' => [
        'Drupal\stacks\WidgetAdmin\Controller\WidgetAdmin',
        'dGridPreview',
      ],
      'event' => 'change',
      'wrapper' => 'contentfeed-grid-content-preview',
      'progress' => [
        'type' => 'throbber',
      ],
    ];

    $entity_form['field_cfeed_content_types']['widget']['#ajax'] = $preview_ajax;
    $entity_form['field_cfeed_taxonomy_terms']['widget']['#ajax'] = $preview_ajax;
    $entity_form['field_cfeed_order']['widget']['#ajax'] = $preview_ajax;
    $entity_form['field_cfeed_results_per_page']['widget']['#ajax'] = $preview_ajax;

    $text = t('Choose content types and/or taxonomy terms to view the content preview...');
    $entity_form['field_cfeed_taxonomy_terms']['#suffix'] = '<div id="contentfeed-grid-content-preview">' . $text . '</div>';
  }
}

/**
 * Alter the fields used to represent an entity in the IEF table.
 *
 * @param array $fields
 *   The fields, keyed by field name.
 * @param array $context
 *   An array with the following keys:
 *   - parent_entity_type: The type of the parent entity.
 *   - parent_bundle: The bundle of the parent entity.
 *   - field_name: The name of the reference field on which IEF is operating.
 *   - entity_type: The type of the referenced entities.
 *   - allowed_bundles: Bundles allowed on the reference field.
 *
 * @see \Drupal\inline_entity_form\InlineFormInterface::getTableFields()
 */
function stacks_inline_entity_form_table_fields_alter(&$fields, $context) {
  if ($context['entity_type'] == 'widget_extend') {
    unset($fields['label']);
    unset($fields['type']);

    // Add any fields you need to the $fields array.
    $fields['table_formatted_entity'] = [
      'type' => 'callback',
      'callback' => 'stacks_widget_extend_ief_table_render',
      'label' => '',
      'weight' => 0,
    ];
  }
}
