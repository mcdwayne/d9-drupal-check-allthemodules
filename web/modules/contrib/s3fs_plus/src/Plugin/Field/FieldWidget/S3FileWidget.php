<?php

namespace Drupal\s3fs_plus\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\file\Plugin\Field\FieldWidget\FileWidget;

/**
 * Plugin implementation of the 's3fs_plus_file' widget.
 *
 * @FieldWidget(
 *   id = "s3fs_plus_file",
 *   label = @Translation("S3fs File"),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class S3FileWidget extends FileWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $element['#type'] = 'file';
    $element['#default_value'] = $items[$delta]->getValue();
    $element['#progress_message'] = '';

    // Default value of s3fs directory field.
    $s3fs_default_directory = '';

    // Get default s3fs directory value.
    $default_file_id = isset($element['#default_value']['target_id']) ? $element['#default_value']['target_id'] : '';
    // If there is fid.
    if (!empty($default_file_id)) {
      $file = File::load($default_file_id);
      // If file exists for given fid.
      if ($file) {
        $file_uri = $file->getFileUri();
        if (!empty($file_uri)) {
          $exploded_data = explode('/', explode('://', $file_uri)[1]);
          // Removes the file name from path as we only need directory.
          unset($exploded_data[count($exploded_data) - 1]);
          $s3fs_default_directory = implode('/', $exploded_data);
        }
      }
    }

    $element['s3fs_directory'] = [
      '#type' => 'textfield',
      '#title' => t('S3fs Directory'),
      '#description' => t('No trailing slashes. Example: S3FS/directory'),
      '#default_value' => $s3fs_default_directory,
      '#required' => TRUE,
      '#element_validate' => ['_s3fs_plus_path_exists'],
      '#autocomplete_route_name' => 's3fs_plus.autocomplete',
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function process($element, FormStateInterface $form_state, $form) {
    $parents_prefix = implode('_', $element['#parents']);
    $fids = isset($element['#value']['fids']) ? $element['#value']['fids'] : [];
    $element['#progress_indicator'] = empty($element['#progress_indicator']) ? 'none' : $element['#progress_indicator'];
    $element['#files'] = !empty($fids) ? File::loadMultiple($fids) : FALSE;

    $element['#tree'] = TRUE;
    $element['upload_button'] = [
      '#name' => $parents_prefix . '_upload_button',
      '#type' => 'submit',
      '#value' => t('Upload'),
      '#attributes' => ['class' => ['js-hide']],
      '#validate' => [],
      '#submit' => ['file_managed_file_submit'],
      '#limit_validation_errors' => [$element['#parents']],
      '#weight' => -5,
    ];

    $element['upload'] = [
      '#name' => 'files[' . $parents_prefix . ']',
      '#type' => 'file',
      '#title' => t('Choose a file'),
      '#title_display' => 'invisible',
      '#size' => $element['#size'],
      '#multiple' => $element['#multiple'],
      '#theme_wrappers' => [],
      '#weight' => -10,
      '#error_no_message' => TRUE,
    ];

    if (isset($element['#upload_validators']['file_validate_extensions'][0])) {
      $extension_list = implode(',', array_filter(explode(' ', $element['#upload_validators']['file_validate_extensions'][0])));
      $element['upload']['#attached']['drupalSettings']['file']['elements']['#' . $element['#id']] = $extension_list;
    }

    // Let #id point to the file element, so the field label's 'for' corresponds
    // with it.
    $element['#id'] = &$element['upload']['#id'];

    $element['fids'] = [
      '#type' => 'hidden',
      '#value' => $fids,
    ];

    $item = $element['#value'];
    $item['fids'] = isset($element['#value']['fids']) ? $element['#value']['fids'] : [];

    $element['#theme'] = 'file_widget';

    if (empty($fids)) {
      $element['remove_button']['#access'] = FALSE;
    }
    else {
      $element['upload']['#access'] = FALSE;
    }

    return parent::process($element, $form_state, $form);
  }

  /**
   * {@inheritdoc}
   */
  public static function value($element, $input = FALSE, FormStateInterface $form_state = NULL) {
    if ($input && !empty($input['s3fs_directory'])) {
      $element['#upload_location'] = 's3fs://' . rtrim($input['s3fs_directory'], '/') . '/';
    }
    return parent::value($element, $input, $form_state);
  }

}
