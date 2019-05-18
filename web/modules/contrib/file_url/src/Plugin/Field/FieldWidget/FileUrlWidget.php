<?php

namespace Drupal\file_url\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\file\Element\ManagedFile;
use Drupal\file\Entity\File;
use Drupal\file\Plugin\Field\FieldWidget\FileWidget;
use Drupal\file_url\Entity\RemoteFile;
use Drupal\file_url\FileUrlHandler;

/**
 * Plugin implementation of the 'file_url_generic' widget.
 *
 * @FieldWidget(
 *   id = "file_url_generic",
 *   label = @Translation("File URL"),
 *   field_types = {
 *     "file_url"
 *   }
 * )
 */
class FileUrlWidget extends FileWidget {

  /**
   * File URL item type: file upload.
   */
  const TYPE_UPLOAD = 'upload';

  /**
   * File URL item type: URL to remote file..
   */
  const TYPE_REMOTE = 'remote';

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'add_new_label' => 'Upload a new file or enter a URL',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    // Allow configuring only if the field has multiple values.
    if ($this->fieldDefinition->getFieldStorageDefinition()->getCardinality() !== 1) {
      $element['add_new_label'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Label for new item form'),
        '#default_value' => $this->getSetting('add_new_label'),
      ];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {
    $elements = parent::formMultipleElements($items, $form, $form_state);

    // Use the configurable 'new item' label.
    $elements['#file_upload_title'] = $this->getSetting('add_new_label');
    // The upload instructions are added directly to 'file_url' element.
    unset($elements['#file_upload_description']);

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    if ($this->fieldDefinition->getFieldStorageDefinition()->getCardinality() === 1) {
      // On single fields show only the field description. Upload instructions
      // were moved to 'file_url' element.
      $element['#description'] = $this->getFilteredDescription();
    }

    // Add our own validator.
    $element['#element_validate'] = [[static::class, 'validate']];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function process($element, FormStateInterface $form_state, $form) {
    $file_url_type = isset($element['#value']['file_url_type']) ? $element['#value']['file_url_type'] : NULL;
    $file_url_remote = $element['#value']['file_url_remote'];
    $file_url_remote_is_valid = UrlHelper::isValid($file_url_remote, TRUE);
    if ($file_url_remote_is_valid && $file_url_type) {
      // The parent widget only populates '#files' with managed files, we add
      // the remote files too, to get them listed as items in the widget.
      $remote_file = RemoteFile::load($file_url_remote);
      $element['#files'] = [$file_url_remote => $remote_file];
      $file_link = [
        '#type' => 'link',
        '#title' => $remote_file->getFileUri(),
        '#url' => Url::fromUri($remote_file->getFileUri()),
      ];
      if ($element['#multiple']) {
        $element["file_{$file_url_remote}"]['selected'] = [
          '#type' => 'checkbox',
          '#title' => \Drupal::service('renderer')->renderPlain($file_link),
        ];
      }
      else {
        $element["file_{$file_url_remote}"]['filename'] = $file_link + ['#weight' => -10];
      }
    }

    $access_file_url_elements = (empty($element['#files']) && !$file_url_remote_is_valid) || !$file_url_type;

    // Build the file URL additional sub-elements.
    $element['file_url_type'] = [
      '#type' => 'radios',
      '#options' => [
        static::TYPE_UPLOAD => t('Upload file'),
        static::TYPE_REMOTE => t('Remote file URL'),
      ],
      '#default_value' => $file_url_type,
      '#prefix' => '<div class="container-inline">',
      '#suffix' => '</div>',
      '#access' => $access_file_url_elements,
      '#weight' => 5,
    ];

    $field_name = $element['#field_name'];
    $delta = $element['#delta'];
    $selector = ':input[name="' . $field_name . '[' . $delta . '][file_url_type]"]';
    $remote_visible = [$selector => ['value' => static::TYPE_REMOTE]];
    $element['file_url_remote'] = [
      '#type' => 'url',
      '#title' => t('Remote URL'),
      '#title_display' => 'invisible',
      '#description' => t('This must be an external URL such as <em>http://example.com</em>.'),
      '#default_value' => $file_url_remote,
      // Only show this field when the 'remote' radio is selected.
      '#states' => ['visible' => $remote_visible],
      '#attached' => [
        // Load the JS functionality that triggers automatically the 'Upload'
        // button when a remote URL is entered.
        'library' => ['file_url/remote_url'],
      ],
      '#attributes' => [
        // Used by 'file_url/remote_url' library identify the text field.
        'data-drupal-file-url-remote' => TRUE,
      ],
      '#access' => $access_file_url_elements,
      '#weight' => 15,
    ];

    // Only show this field when the 'upload' radio is selected. Add also a
    // wrapper around file upload, so states knows what field to target.
    $upload_visible = [$selector => ['value' => static::TYPE_UPLOAD]];
    $element['upload']['#states']['visible'] = $upload_visible;
    $element['upload']['#theme_wrappers'][] = 'form_element';
    // The upload instructions are added directly to the file upload element.
    $element['upload']['#description'] = [
      '#theme' => 'file_upload_help',
      '#description' => '',
      '#upload_validators' => $element['#upload_validators'],
      '#cardinality' => $element['#cardinality'],
    ];
    $element['upload']['#weight'] = 10;

    // Make sure the upload button is the last in form element.
    $element['upload_button']['#weight'] = 20;

    return parent::process($element, $form_state, $form);
  }

  /**
   * Replaces the ManagedFile validator.
   *
   * @param array $element
   *   The element render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   * @param array $complete_form
   *   The full form render array.
   */
  public static function validate(&$element, FormStateInterface $form_state, &$complete_form) {
    $file_url_type = NestedArray::getValue($form_state->getValues(), array_merge($element['#parents'], ['file_url_type']));
    $remote_url = NestedArray::getValue($form_state->getValues(), array_merge($element['#parents'], ['file_url_remote']));
    $fids = NestedArray::getValue($form_state->getValues(), array_merge($element['#parents'], ['fids']));

    if (($remote_url || $fids) && !in_array($file_url_type, [static::TYPE_UPLOAD, static::TYPE_REMOTE], TRUE)) {
      // @todo Find a way to guess the type from values. Temporary disable this
      //   validation.
      // $form_state->setError($element, t("A type should be selected. Either 'Upload file' or 'Remote file URL'."));
    }

    if ($file_url_type === static::TYPE_UPLOAD) {
      // If it's a file upload, pass it to the 'managed_file' validation.
      ManagedFile::validateManagedFile($element, $form_state, $complete_form);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function value($element, $input, FormStateInterface $form_state) {
    $file_url_remote = $file_url_type = NULL;

    if (!empty($element['#default_value']['fids'])) {
      $url = $element['#default_value']['fids'][0];
      $file = FileUrlHandler::urlToFile($url);
      if (!FileUrlHandler::isRemote($file)) {
        // Normalise managed file IDs from URLs to numeric IDs before calling
        // the parent method.
        $element['#default_value']['fids'] = [$file->id()];
        $file_url_type = 'upload';
      }
      else {
        $file_url_remote = $url;
        $file_url_type = 'remote';
      }
    }

    $return = parent::value($element, $input, $form_state);

    if ($input !== FALSE) {
      if (isset($input['file_url_type']) && $input['file_url_type'] === static::TYPE_REMOTE) {
        if (!empty($input['file_url_remote']) && !$input['fids']) {
          $file_url_remote = $input['file_url_remote'];
        }
      }
    }

    if (!empty($file_url_remote)) {
      // The parent widget is doing this but only for managed files.
      $return['fids'] = [$file_url_remote];
    }

    // Provide file URL parameters as part of the value.
    if (empty($return['file_url_remote'])) {
      $return['file_url_remote'] = $file_url_remote;
    }
    if (empty($return['file_url_type'])) {
      $return['file_url_type'] = $file_url_type;
    }

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $values = parent::massageFormValues($values, $form, $form_state);

    // Convert file IDs to URLs.
    foreach ($values as &$value) {
      if ($value['file_url_type'] === static::TYPE_UPLOAD) {
        /** @var \Drupal\file\FileInterface $file */
        $file = File::load((int) $value['target_id']);
        $value['target_id'] = FileUrlHandler::fileToUrl($file);
      }

      // Remove file URL specific keys from value.
      unset($value['file_url_type']);
      unset($value['file_url_remote']);
    }

    return $values;
  }

}
