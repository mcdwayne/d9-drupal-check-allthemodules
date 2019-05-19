<?php

namespace Drupal\uppy\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Plugin\Field\FieldWidget\FileWidget;

/**
 * Plugin implementation of the 'uppy_widget' widget.
 *
 * @FieldWidget(
 *   id = "uppy_widget",
 *   label = @Translation("Uppy file uploader"),
 *   field_types = {
 *     "file",
 *     "image"
 *   }
 * )
 */
class UppyWidget extends FileWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'auto_proceed' => FALSE,
      'file_sources' => 'dashboard',
      'uploader' => 'tus',
      'chunk_size' => 2000000,
    ] + parent::defaultSettings();
  }

  /**
   * Options for which file source(s) to use.
   *
   * Local:
   * dashboard
   * drag-drop
   * file-input
   * webcam
   *
   * Remote:
   * google-drive
   * dropbox
   * instagram
   * url
   *
   * @return array
   *   Array of file_sources options.
   */
  public function fileSourceOptions() {
    return [
      'dashboard' => '@uppy/dashboard',
      'drag-drop' => '@uppy/drag-drop',
    ];
  }

    /**
   * Options for which uploader to use.
   *
   * Options:
   * tus
   * xhr-upload
   * aws-s3
   * aws-s3-multipart
   *
   * @return array
   *   Array of file_sources options.
   */
  public function uploaderOptions() {
    return [
      'tus' => '@uppy/tus',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];
    $elements['auto_proceed'] = [
      '#title' => t('Upload immediately?'),
      '#type' => 'checkbox',
      '#options' => [FALSE, TRUE],
      '#default_value' => $this->getSetting('auto_proceed'),
      '#description' => t('File upload will begin without requiring button press.'),
      '#weight' => 14,
    ];
    $elements['file_sources'] = [
      '#title' => t('File sources'),
      '#type' => 'select',
      '#options' => $this->fileSourceOptions(),
      '#default_value' => $this->getSetting('file_sources'),
      '#description' => t('Which file source options should be available.'),
      '#weight' => 15,
    ];
    $elements['uploader'] = [
      '#title' => t('Uploader'),
      '#type' => 'select',
      '#options' => $this->uploaderOptions(),
      '#default_value' => $this->getSetting('uploader'),
      '#description' => t('Which upload approach to use. (TUS reccomended)'),
      '#weight' => 16,
    ];
    $elements['chunk_size'] = [
      '#title' => t('Chunk size'),
      '#type' => 'number',
      '#default_value' => $this->getSetting('chunk_size'),
      '#description' => t('Chunk size in bytes (2MB default)'),
      '#weight' => 17,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $summary[] = t('Upload immediately: @auto_proceed', ['@auto_proceed' => $this->getSetting('auto_proceed')]);
    $summary[] = t('File source(s): @file_sources', ['@file_sources' => $this->getSetting('file_sources')]);
    if (!empty($this->getSetting('uploader'))) {
      $summary[] = t('Uploader: @uploader', ['@uploader' => $this->getSetting('uploader')]);
    }
    $summary[] = t('Chunk size: @chunk_size', ['@chunk_size' => $this->getSetting('chunk_size')]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    // Add Uppy JS.
    $element['#attached'] = [
      'library' => ['uppy/uppy_widget'],
    ];

    // Get entity details.
    $entityType = $this->fieldDefinition->getTargetEntityTypeId();
    $bundle = $this->fieldDefinition->getTargetBundle();
    $fieldName = $this->fieldDefinition->getName();

    // Identifier per field instance.
    $id = $entityType . '-' . $fieldName . '-' . $delta;
    // Start array of settings to pass to Uppy.
    $settings = $this->getSettings();
    $settings['delta'] = $delta;

    // Add required data for TUS upload.
    if ($this->getSetting('uploader') == 'tus') {
      $settings += [
        'entityType' => $entityType,
        'entityBundle' => $bundle,
        'fieldName' => $fieldName,
      ];
    }

    // Cardinality.
    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();
    if ($cardinality == -1) {
      $cardinality = NULL;
    }
    $settings['max_number_of_files'] = $cardinality;

    // Finalize our settings into drupalSettings by field id.
    $element['#attached']['drupalSettings']['uppy'][$id] = $settings;
    $element['#attributes']['class'][] = 'uppy-file';
    $element['#uppy-id'] = $id;
    // Container that will hold the widget.
    $element['uppy-widget'] = [
      '#type' => 'container',
      '#id' => $id,
      '#attributes' => [
        'class' => 'uppy-widget',
      ],
    ];

    return $element;
  }

}
