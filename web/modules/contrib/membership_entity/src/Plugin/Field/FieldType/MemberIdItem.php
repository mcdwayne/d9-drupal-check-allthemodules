<?php
declare(strict_types=1);

namespace Drupal\membership_entity\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\StringItemBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the 'member_id' entity field type.
 *
 * @FieldType(
 *   id = "member_id",
 *   label = @Translation("Member ID"),
 *   description = @Translation("An field containing a unique, auto-generated member ID."),
 *   category = @Translation("Membership"),
 *   default_widget = "member_id",
 *   default_formatter = "member_id"
 * )
 */
class MemberIdItem extends StringItemBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    // Member ID will be used as the primary key. Max length for keys in
    // MySQL <= 5.6 is 191 characters.
    return [
      'member_id_plugin' => 'numeric_member_id',
      'max_length' => 191,
      'is_ascii' => FALSE,
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => 'varchar',
          'length' => $field_definition->getSetting('max_length'),
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraints = parent::getConstraints();

    if ($max_length = $this->getSetting('max_length')) {
      $constraint_manager = \Drupal::typedDataManager()->getValidationConstraintManager();
      $constraints[] = $constraint_manager->create('ComplexData', [
        'value' => [
          'Length' => [
            'max' => $max_length,
            'maxMessage' => t('%name: may not be longer than @max characters.', ['%name' => $this->getFieldDefinition()->getLabel(), '@max' => $max_length]),
          ],
        ],
      ]);
    }

    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $manager = \Drupal::service('plugin.manager.member_id');
    $member_id = $manager->createInstance($field_definition->getSetting('member_id_plugin'), []);
    return ['value' => $member_id->sampleValue()];
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $element['member_id_plugin'] = [
      '#type' => 'radios',
      '#title' => $this->t('Member ID Plugin'),
      '#options' => [],
      '#default_value' => $this->getSetting('member_id_plugin'),
      '#required' => TRUE,
    ];

    $manager = \Drupal::service('plugin.manager.member_id');
    foreach ($manager->getDefinitions() as $id => $plugin) {
      $element['member_id_plugin']['#options'][$id] = $plugin['title'];
    }

    return $element;
  }

}
