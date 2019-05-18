<?php

namespace Drupal\protected_file\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\file\Plugin\Field\FieldType\FileItem;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;

/**
 * Plugin implementation of the 'Protected File' field type.
 *
 * @FieldType(
 *   id = "protected_file",
 *   label = @Translation("Protected File"),
 *   description = @Translation("Protected File"),
 *   default_widget = "protected_file_widget",
 *   default_formatter = "protected_file_formatter",
 *   list_class = "\Drupal\protected_file\Plugin\Field\FieldType\ProtectedFileFieldItemList",
 *   constraints = {"ReferenceAccess" = {}, "FileValidation" = {}}
 * )
 */
class ProtectedFile extends FileItem {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);

    $properties['protected_file'] = DataDefinition::create('boolean')
      ->setLabel(t('File protected'))
      ->setDescription(t('Flag to control whether this file is protected against downloading by users who do not have according permissions'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = parent::schema($field_definition);

    $schema['columns']['protected_file'] = [
      'type' => 'int',
      'size' => 'tiny',
      'unsigned' => TRUE,
      'default' => 0,
    ];

    return $schema;

  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $element = parent::storageSettingsForm($form, $form_state, $has_data);

    // Validate the uri scheme selected.
    $element['uri_scheme']['#element_validate'] = array(array(get_class($this), 'validateUriScheme'));

    // We force the selection of the private file system. Otherwise, if not
    // available, we disabled the form.
    // @TODO disable too the form actions.
    $scheme_options = \Drupal::service('stream_wrapper_manager')->getNames(StreamWrapperInterface::WRITE_VISIBLE);
    if (!isset($scheme_options['private'])) {
      $element['#prefix'] = '<div class="messages messages--error">' . $this->t('Protected file is only useful if the storage file is created under the private system file. You need to configure the Private file system path in the settings.php file.') . '</div>' . $element['#prefix'];
      $element['#disabled'] = TRUE;
    }
    else {
      $element['uri_scheme']['#default_value'] = 'private';
      $element['uri_scheme']['#disabled'] = TRUE;
    }

    return $element;
  }

  /**
   * Form API callback.
   *
   * This function is assigned as an #element_validate callback in
   * fieldSettingsForm().
   *
   * This doubles as a convenience clean-up function and a validation routine.
   * Commas are allowed by the end-user, but ultimately the value will be stored
   * as a space-separated list for compatibility with file_validate_extensions().
   */
  public static function validateUriScheme($element, FormStateInterface $form_state) {
    if ($element['#value'] != 'private') {
      $form_state->setError($element, t('Public file system can not be used with a Protected file. This field type is only useful if the storage file is created under the private system file. You need to configure the Private file system path in the settings.php file.'));
    }
  }

  /**
   * Check if the field is protected.
   *
   * @return bool
   *   The field is protected or not.
   */
  public function isProtected() {
    if ($this->protected_file) {
      return TRUE;
    }
    return FALSE;
  }

}
