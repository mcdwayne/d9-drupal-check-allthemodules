<?php

namespace Drupal\s3fs_cors\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Bytes;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Plugin\Field\FieldWidget\FileWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\s3fs_cors\Element\S3fsCorsFile;

/**
 * Plugin implementation of the 's3fs_cors_widget' widget.
 *
 * @FieldWidget(
 *   id = "s3fs_cors_file_widget",
 *   label = @Translation("S3fs Cors File Upload"),
 *   field_types = {
 *     "file",
 *     "s3fs_cors_file"
 *   }
 * )
 */
class S3fsCorsFileWidget extends FileWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = parent::defaultSettings();
    $settings['max_filesize'] = '';
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['max_filesize'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Maximum upload size'),
      '#description' => $this->t('Enter a value like "512" (bytes), "80 KB" (kilobytes), "50 MB" (megabytes) or "2 GB" (gigabytes) in order to restrict the allowed file size. This value will override any value specified in the field configuration settings.'),
      '#default_value' => $this->getSetting('max_filesize'),
      '#size' => 10,
      '#weight' => 5,
      '#element_validate' => [[$this, 'settingsMaxFilesizeValidate']],
    ];

    return $elements;

  }

  /**
   * Validate the submiited value for max filesize.
   */
  public function settingsMaxFilesizeValidate($element, FormStateInterface $form_state) {
    $submitted_value = $form_state->getValue($element['#parents']);
    if ($submitted_value) {
      $matches = [];
      if (preg_match('/(\d+)(.*)/', $submitted_value, $matches)) {
        $number = $matches[1];
        $suffix = strtoupper(trim($matches[2]));
        if ($suffix && !in_array($suffix, ['B', 'KB', 'MB', 'GB'])) {
          $form_state->setError($element, t('Invalid numeric value or size suffix. Specify an integer followed by "KB", "MB" or "GB".'));
          return;
        }
      }

      $max_filesize = Bytes::toInt($submitted_value);
      if (!$max_filesize) {
        $form_state->setError($element, t('Invalid max filesize. Enter a value like "512" (bytes), "80 KB" (kilobytes), "50 MB" (megabytes) or "2 GB" (gigabytes).'));
      }

      if ($max_filesize > Bytes::toInt('5 GB')) {
        $form_state->setError($element, t('Invalid max filesize. 5 GB is largest file size current supported.'));
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    if ($this->getSetting('max_filesize')) {
      $summary[] = $this->t('Max upload filesize: @max_filesize (override field configuration value).',
        ['@max_filesize' => $this->getSetting('max_filesize')]);
    }
    else {
      $summary[] = $this->t('Max upload filesize not specified (use field configuration value).');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $element_info = $this->elementInfo->getInfo('s3fs_cors_file');

    // Use specified max filesize override or the default value for this field.
    $max_filesize = $this->getSetting('max_filesize') ?: $element['#upload_validators']['file_validate_size'][0];

    // Specify custom element validation.
    $element['#element_validate'] = [
      ['\Drupal\s3fs_cors\Element\S3fsCorsFile', 'validateManagedFile'],
    ];

    $element['#type'] = 's3fs_cors_file';
    $element['#process'] = [$element_info['#process'][0], $element['#process'][1]];
    $element['#max_filesize'] = $max_filesize;
    $element['#upload_validators']['file_validate_size'] = [$max_filesize];

    $element['#attributes'] = ['class' => ['s3fs-cors-file']];

    return $element;
  }

  /**
   * Form API callback. Retrieves the value for the file_generic field element.
   *
   * This method is assigned as a #value_callback in formElement() method.
   */
  public static function value($element, $input = FALSE, FormStateInterface $form_state = NULL) {
    if ($input) {
      // Checkboxes lose their value when empty.
      // If the display field is present make sure its unchecked value is saved.
      if (empty($input['display'])) {
        $input['display'] = $element['#display_field'] ? 0 : 1;
      }
      // Suppress processing of multiple uploads at the same time to force each
      // uploaded file to be processed separately.
      // Refer https://www.drupal.org/project/s3fs_cors/issues/2980155
      if (in_array('upload', array_keys($input)) && is_null($input['upload'])) {
        $input = FALSE;
      }
    }
    // We depend on the managed file element to handle uploads.
    $return = S3fsCorsFile::valueCallback($element, $input, $form_state);

    // Ensure that all the required properties are returned even if empty.
    $return += [
      'fids' => [],
      'display' => 1,
      'description' => '',
    ];
    return $return;
  }

}
