<?php

namespace Drupal\spin\Plugin\Field\FieldType;

use Drupal\Component\Utility\Bytes;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'spin' field type.
 *
 * @FieldType(
 *   id = "spin",
 *   label = @Translation("3D Image Spinner"),
 *   module = "spin",
 *   description = @Translation("3D image spinner slideshow."),
 *   default_widget = "spin_widget",
 *   default_formatter = "spin_formatter"
 * )
 */
class SpinItem extends FieldItemBase {
  protected $entityManager;

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'alt_field'            => 0,
      'alt_field_required'   => 0,
      'description_field'    => 0,
      'file_directory'       => 'spin_img',
      'file_extensions'      => 'png gif jpg jpeg',
      'max_filesize'         => '',
      'max_resolution'       => '',
      'min_resolution'       => '',
      'title_field'          => 0,
      'title_field_required' => 0,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'target_type'     => 'integer',
      'display_field'   => FALSE,
      'display_default' => FALSE,
      'uri_scheme'      => file_default_scheme(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $settings = $this->getSettings();

    $element['file_directory'] = [
      '#type'             => 'textfield',
      '#title'            => t('File directory'),
      '#default_value'    => $settings['file_directory'],
      '#description'      => t('Optional subdirectory within the upload destination where files will be stored. Do not include preceding or trailing slashes.'),
      '#element_validate' => [[get_class($this), 'validateDirectory']],
    ];
    $element['file_extensions'] = [
      '#type'             => 'textfield',
      '#title'            => t('Allowed file extensions'),
      '#default_value'    => preg_replace('/[^a-zA-Z]+/', ' ', trim($settings['file_extensions'])),
      '#description'      => t('Separate extensions with a space or comma and do not include the leading dot.'),
      '#element_validate' => [[get_class($this), 'validateExtensions']],
      '#maxlength'        => 255,
      '#required' => TRUE,
    ];
    $element['max_filesize'] = [
      '#type' => 'textfield',
      '#title' => t('Maximum upload size'),
      '#default_value' => $settings['max_filesize'],
      '#description' => t('Enter a value like "512" (bytes), "80 KB" (kilobytes) or "50 MB" (megabytes) in order to restrict the allowed file size. If left empty the file sizes will be limited only by PHP\'s maximum post and file upload sizes (current limit <strong>%limit</strong>).', ['%limit' => format_size(file_upload_max_size())]),
      '#size' => 10,
      '#element_validate' => [[get_class($this), 'validateMaxFilesize']],
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'fid' => [
          'type'     => 'int',
          'unsigned' => TRUE,
          'not null' => FALSE,
        ],
        'spin' => [
          'type'     => 'varchar',
          'length'   => 125,
          'not null' => FALSE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return (bool) !$this->get('fid')->getValue() && !$this->get('spin')->getValue();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['fid'] = DataDefinition::create('integer')
      ->setLabel(t('File ID'));

    $properties['spin'] = DataDefinition::create('string')
      ->setLabel(t('Spin'));

    return $properties;
  }

  /**
   * Retrieves the upload validators for a file field.
   *
   * @return array
   *   An array suitable for passing to file_save_upload() or the file field
   *   element's '#upload_validators' property.
   */
  protected function getUploadValidators() {
    $max_filesize = Bytes::toInt(file_upload_max_size());
    $settings = $this->getSettings();
    $validators = [];

    if (!empty($settings['max_filesize'])) {
      $max_filesize = min($max_filesize, Bytes::toInt($settings['max_filesize']));
    }
    $validators['file_validate_size'] = [$max_filesize];

    if (!empty($settings['file_extensions'])) {
      $validators['file_validate_extensions'] = [$settings['file_extensions']];
    }
    return $validators;
  }

  /**
   * Gets the entity manager.
   *
   * @return \Drupal\Core\Entity\EntityManagerInterface
   *   An entity manager.
   */
  protected function getEntityManager() {
    if (!isset($this->entityManager)) {
      $this->entityManager = \Drupal::entityManager();
    }
    return $this->entityManager;
  }

}
