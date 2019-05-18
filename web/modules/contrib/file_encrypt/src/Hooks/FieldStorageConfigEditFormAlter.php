<?php

namespace Drupal\file_encrypt\Hooks;

use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\encrypt\EncryptionProfileManagerInterface;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\file\Plugin\Field\FieldType\FileItem;

/**
 * Class which implements hook_form_field_storage_config_edit_form_alter().
 *
 * The general idea is to change the field storage configuration form to allow
 * storing a encryption profile when the encrypt
 */
class FieldStorageConfigEditFormAlter {

  use StringTranslationTrait;

  /** @var \Drupal\Core\Field\FieldTypePluginManagerInterface */
  protected $fieldTypeManager;

  /** @var \Drupal\encrypt\EncryptionProfileManagerInterface */
  protected $encryptionProfileManager;

  /**
   * Creates a new FieldStorageConfigEditFormAlter instance.
   *
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_manager
   *   The field type plugin manager.
   * @param \Drupal\encrypt\EncryptionProfileManagerInterface $encryption_profile_manager
   *   The encryption profile manager.
   */
  public function __construct(FieldTypePluginManagerInterface $field_type_manager, EncryptionProfileManagerInterface $encryption_profile_manager) {
    $this->fieldTypeManager = $field_type_manager;
    $this->encryptionProfileManager = $encryption_profile_manager;
  }

  /**
   * Implements hook_form_field_storage_config_edit_form_alter().
   */
  public function alterForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\field\FieldStorageConfigInterface $field_storage_config */
    $field_storage_config = $form_state->getFormObject()->getEntity();
    $field_type_definition = $this->fieldTypeManager->getDefinition($field_storage_config->getType());
    if (is_a($field_type_definition['class'], FileItem::class, TRUE)) {
      $this->doFormAlter($field_storage_config, $form, $form_state);
    }
  }

  /**
   * Adds the encryption_profile setting to the field storage settings form.
   *
   * @param \Drupal\field\FieldStorageConfigInterface $field_storage_config
   *   The field storage.
   * @param array $form
   *   The changed form.
   */
  protected function doFormAlter(FieldStorageConfigInterface $field_storage_config, array &$form) {
    $options = $this->encryptionProfileManager->getEncryptionProfileNamesAsOptions();
    if (empty($options)) {
      $form['settings']['encryption_profile'] = [
        '#markup' => $this->t('No encryption profile found.'),
      ];
    }
    else {
      $form['settings']['uri_scheme']['#weight'] = -10;
      $form['settings']['encryption_profile'] = [
        '#type' => 'radios',
        '#title' => $this->t('Encryption profile'),
        '#options' => $options,
        '#default_value'=> $field_storage_config->getThirdPartySetting('file_encrypt', 'encryption_profile', NULL),
        '#states' => [
          'visible' => [
            'input[name="settings[uri_scheme]"]' => ['value' => 'encrypt'],
          ],
        ],
        '#weight' => -5,
      ];
    }

    $form['#entity_builders'][] = static::class . '::buildEntity';
  }

  /**
   * Builds the entity by adding the encryption_profile third party settings.
   *
   * @param string $entity_type_id
   *    The entity type ID.
   * @param \Drupal\field\FieldStorageConfigInterface $field_storage_config
   *   The field storage configuration.
   * @param array $form
   *   The original form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function buildEntity($entity_type_id, FieldStorageConfigInterface $field_storage_config, $form, FormStateInterface $form_state) {
    if ($encryption_profile = $form_state->getValue(['settings', 'encryption_profile'])) {
      $field_storage_config->setThirdPartySetting('file_encrypt', 'encryption_profile', $encryption_profile);
    }
  }

}
