<?php

namespace Drupal\bitdash_player\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Plugin\Field\FieldType\FileItem;

/**
 * Plugin implementation of the 'bitdash_player' field type.
 *
 * @FieldType(
 *   id = "bitdash_player",
 *   label = @Translation("Bitdash Player"),
 *   description = @Translation("This field stores the ID of a Bitdash Player file as an integer value."),
 *   category = @Translation("Reference"),
 *   default_widget = "bitdash_player",
 *   default_formatter = "bitdash_player",
 *   column_groups = {
 *     "file" = {
 *       "label" = @Translation("File"),
 *       "columns" = {
 *         "target_id"
 *       },
 *       "require_all_groups_for_translation" = TRUE
 *     },
 *   },
 *   list_class = "\Drupal\file\Plugin\Field\FieldType\FileFieldItemList",
 *   constraints = {"ReferenceAccess" = {}, "FileValidation" = {}}
 * )
 */
class BitdashPlayerItem extends FileItem {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    $settings = [
      'file_extensions' => 'mp4 mkv',
      'file_directory' => '',
      'max_filesize' => '',
      'target_destination' => 'bitdash',
      'target_ftp_auth' => '',
      'target_ftp_url' => '',
      'target_ftp_directory' => '',
      'encoding_profile' => '{}',
      ] + parent::defaultFieldSettings();

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'target_id' => [
          'description' => 'The ID of the file entity.',
          'type' => 'int',
          'unsigned' => TRUE,
        ],
      ],
      'indexes' => [
        'target_id' => ['target_id'],
      ],
      'foreign keys' => [
        'target_id' => [
          'table' => 'file_managed',
          'columns' => ['target_id' => 'fid'],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);

    unset($properties['display']);
    unset($properties['description']);

    /*
    $properties['title'] = DataDefinition::create('string')
    ->setLabel(t('Title'))
    ->setDescription(t("Image title text, for the image's 'title' attribute."));
     */

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $element = parent::storageSettingsForm($form, $form_state);
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    // Get base form from FileItem.
    $element = parent::fieldSettingsForm($form, $form_state);

    $settings = $this->getSettings();

    $element['target_destination'] = [
      '#type' => 'radios',
      '#title' => t('Target destination'),
      '#default_value' => $settings['target_destination'],
      '#options' => [
        'bitdash' => t('Bitdash'),
        'local' => t('Local'),
        'ftp' => t('FTP'),
      ],
      '#description' => t("Select a destination for the transcoded video's"),
      '#weight' => -2,
    ];
    $element['file_directory']['#weight'] = -1;
    $element['file_directory']['#states'] = [
      'visible' => [
        ':input[name="settings[target_destination]"]' => ['value' => 'local'],
      ],
    ];

    // Decode the encoded authentication.
    $auth = bitdash_player_parse_auth($settings['target_ftp_auth']);

    $element['ftp_auth'] = [
      '#type' => 'fieldset',
      '#title' => t('FTP authentication'),
      '#states' => [
        'visible' => [
          ':input[name="settings[target_destination]"]' => ['value' => 'ftp'],
        ],
      ],
      '#parents' => ['settings'],
    ];
    $element['ftp_auth']['target_ftp_url'] = [
      '#type' => 'textfield',
      '#title' => t('URL'),
      '#default_value' => $settings['target_ftp_url'],
    ];
    $element['ftp_auth']['user'] = [
      '#type' => 'textfield',
      '#title' => t('Username'),
      '#default_value' => $auth['user'],
    ];
    $element['ftp_auth']['pass'] = [
      '#type' => 'password',
      '#title' => t('Password'),
      '#default_value' => $auth['pass'],
    ];
    $element['ftp_auth']['target_ftp_directory'] = [
      '#type' => 'textfield',
      '#title' => t('Directory'),
      '#default_value' => $settings['target_ftp_directory'],
    ];
    $element['target_ftp_auth'] = [
      '#type' => 'value',
      '#default_value' => $settings['target_ftp_auth'],
      '#value_callback' => 'target_ftp_auth_value',
    ];
    $element['encoding_profile'] = [
      '#type' => 'textarea',
      '#title' => t('Encoding profile'),
      '#default_value' => $settings['encoding_profile'],
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    parent::preSave();
  }

  /**
   * Validates the managed_file element for the default Image form.
   *
   * This function ensures the fid is a scalar value and not an array. It is
   * assigned as a #element_validate callback in
   * \Drupal\image\Plugin\Field\FieldType\ImageItem::defaultImageForm().
   *
   * @param array $element
   *   The form element to process.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function validateDefaultImageForm(array &$element, FormStateInterface $form_state) {
    // Consolidate the array value of this field to a single FID as #extended
    // for default image is not TRUE and this is a single value.
    if (isset($element['fids']['#value'][0])) {
      $value = $element['fids']['#value'][0];
      // Convert the file ID to a uuid.
      if ($file = \Drupal::entityManager()->getStorage('file')->load($value)) {
        $value = $file->uuid();
      }
    }
    else {
      $value = '';
    }
    $form_state->setValueForElement($element, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function isDisplayed() {
    // Image items do not have per-item visibility settings.
    return TRUE;
  }

  /**
   * Gets the entity manager.
   *
   * @return \Drupal\Core\Entity\EntityManagerInterface.
   *   The entityManager of the file field.
   */
  protected function getEntityManager() {
    if (!isset($this->entityManager)) {
      $this->entityManager = \Drupal::entityManager();
    }
    return $this->entityManager;
  }

}
