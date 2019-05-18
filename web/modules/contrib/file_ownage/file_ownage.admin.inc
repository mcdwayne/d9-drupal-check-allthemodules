<?php

/**
 * @file
 * Admin UI for file ownage settings.
 */

use Drupal\field\Entity\FieldConfig;
use Drupal\Core\Url;
/**
 * Extra preferences to support the filefield option.
 *
 * @param array $subsettings
 *   Settings values to expose.
 *
 * @return array
 *   A FAPI form fragment.
 */
function file_ownage_attach_filefield_subform(array $subsettings = []) {

  $available_filefields = file_ownage_available_fields(['file', 'image']);
  $subsettings += [
    'filefield_name' => '',
  ];

  $subform = [];
  $subform['about_filefields'] = [
    '#type' => 'markup',
    '#value' => '<p>' . t('The filefields you select should be consistent and re-used throughout your <a href="!content_types_url">content types</a>. If a filefield is unavailable for a node, it will not be used.', ['!content_types_url' => Url::fromRoute('entity.node_type.collection')]) . '</p>',
  ];
  $subform['filefield_name'] = [
    '#title' => "Which filefield to use for supplimentary images",
    '#type' => 'select',
    '#options' => $available_filefields,
    '#default_value' => $subsettings['filefield_name'],
    '#description' => 'This filefield is expected to have "unlimited" entries.',
  ];
  return $subform;
}

/**
 * Extra preferences to support the feature filefield option.
 *
 * @param array $subsettings
 *   Settings values to expose.
 *
 * @return array
 *   A FAPI form fragment.
 */
function file_ownage_attach_feature_filefield_subform(array $subsettings = []) {

  $subsettings += [
    'feature_filefield_name' => 'image_feature',
    'feature_filefield_min_width' => '200',
    'remove_original' => TRUE,
  ];
  $available_filefields = file_ownage_available_fields('image');

  $subform = [];
  $subform['about_filefields'] = [
    '#type' => 'markup',
    '#value' => '<p>' . t('The filefields you select should be consistent and re-used throughout your <a href="!content_types_url">content types</a>. If a filefield is unavailable for a node, it will not be used.', ['!content_types_url' => Url::fromRoute('entity.node_type.collection')]) . '</p>',
  ];
  $subform['feature_filefield_name'] = [
    '#title' => "Which filefield to use for feature file",
    '#type' => 'select',
    '#options' => $available_filefields,
    '#default_value' => $subsettings['feature_filefield_name'],
    '#description' => 'This option supports attaching one file that may be rendered uniquely (eg as a representative teaser image) while the others go inline. This filefield chosen here is expected to have a limit of one item.',
  ];
  $subform['feature_filefield_min_width'] = [
    '#title' => "What is the minimum width before an embedded image is eligible to become a featured image.",
    '#type' => 'textfield',
    '#size' => 4,
    '#default_value' => $subsettings['feature_filefield_min_width'],
    '#description' => 'You don\'t want small images accidentally being chosen to be the feature image. Enter a minimum width in pixels. Images smaller than that will not become features.',
  ];
  $subform['remove_original'] = [
    '#type' => 'checkbox',
    '#title' => t('When an embedded feature image is found and attached, remove it from the normal text flow'),
    '#default_value' => $subsettings['remove_original'],
    '#description' => t('This allows you to reformat the feature image and theme it your own way. It only applies on "feature" images, not supplimentary ones'),
  ];

  return $subform;
}

/**
 * Extra preferences to support the imageholder_nodereference option.
 *
 * @param array $subsettings
 *   Settings values to expose.
 *
 * @return array
 *   A FAPI form fragment.
 */
function file_ownage_attach_fileholder_nodereference_subform(array $subsettings = []) {

  // These defaults are unlikely to be right,
  // provided here as a hint for a vanilla installation.
  $subsettings += [
    'imageholder_type' => 'image',
    'imagefield_name' => 'image',
    'fileholder_type' => 'file',
    'filefield_name' => 'file',
    'nodereference_field' => 'attached',
  ];
  $available_content_types = node_type_get_names();
  $available_nodereferencefields = file_ownage_available_fields('node_reference');
  $available_imagefields = file_ownage_available_fields('image');
  $available_filefields = file_ownage_available_fields(['file', 'image']);

  $subform = [];
  $subform['imageholder_type'] = [
    '#title' => "This content type to create as a placeholder for new resources",
    '#type' => 'select',
    '#options' => $available_content_types,
    '#default_value' => $subsettings['imageholder_type'],
    '#description' => 'You may have to make a new content type called, eg, "image" and add an imagefield to it.',
  ];
  $subform['imagefield_name'] = [
    '#title' => "Which filefield on the target content type to store the image file in",
    '#type' => 'select',
    '#options' => $available_filefields,
    '#default_value' => $subsettings['imagefield_name'],
  ];
  $subform['fileholder_type'] = [
    '#title' => "This content type to create as a placeholder for new files",
    '#type' => 'select',
    '#options' => $available_content_types,
    '#default_value' => $subsettings['fileholder_type'],
    '#description' => 'You may have to make a new content type called, eg, "document" and add a filefield to it.',
  ];
  $subform['filefield_name'] = [
    '#title' => "Which filefield on the target document content type to store the file in",
    '#type' => 'select',
    '#options' => $available_filefields,
    '#default_value' => $subsettings['filefield_name'],
  ];
  $subform['nodereference_field'] = [
    '#title' => "The nodereference field used to link from the parent node content to the file holder.",
    '#type' => 'select',
    '#options' => $available_nodereferencefields,
    '#default_value' => $subsettings['nodereference_field'],
  ];
  return $subform;
}

/**
 * Return an array of all instances of filefield from any content type.
 *
 * Lookup utility for the selection form.
 *
 * @param string|array $field_type
 *   Eg 'file', 'node_reference'. String or array of types.
 *
 * @return array
 *   List of field instance names keyed by ID.
 */
function file_ownage_available_fields($field_type) {
  $field_type = is_array($field_type) ? $field_type : [$field_type];
  $available_fields = [];
  $field_instances = FieldConfig::loadMultiple();
  /**
   * @var string $field_id
   * @var Drupal\field\FieldConfigInterface $field_def
   */
  foreach ($field_instances as $field_id => $field_def) {
    if (in_array($field_def->getType(), $field_type)) {
      $available_fields[$field_id] = $field_def->getName();
    }
  }
  return $available_fields;
}
