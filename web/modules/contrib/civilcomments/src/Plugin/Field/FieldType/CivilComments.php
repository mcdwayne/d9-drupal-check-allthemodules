<?php

namespace Drupal\civilcomments\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'Civil Comments' field type.
 *
 * @FieldType(
 *   id = "civil_comments",
 *   label = @Translation("Civil Comments field"),
 *   description = @Translation("This field attaches comments to entities."),
 *   default_widget = "civilcomments_default",
 *   default_formatter = "civilcomments_default"
 * )
 */
class CivilComments extends FieldItemBase implements CivilCommentItemInterface {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['status'] = DataDefinition::create('integer')
      ->setLabel(t('Civil Comment status'))
      ->setRequired(TRUE)
      ->setDescription(t('The status of comments for the parent entity.'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'status' => [
          'description' => 'Whether comments are allowed on this entity: 0 = no, 1 = closed (read only), 2 = open (read/write).',
          'type' => 'int',
          'default' => 0,
        ],
      ],
      'indexes' => [],
      'foreign keys' => [],
    ];
  }

  /**
   * {@inheritdoc}
   *
   * @todo
   *   - Implement some field settings, or get rid of this.
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = [];

    $element['placeholder'] = [
      '#type' => 'markup',
      '#markup' => '<p><strong>' . t('This space reserved for some actual settings.') . '</strong></p>',
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return 'status';
  }

  /**
   * Defines conditions where field can be considered empty.
   *
   * @return bool
   *   Returns TRUE if 'status' is unset (NULL) or set to 0 ("Disabled").
   */
  public function isEmpty() {
    return !((bool) $this->get('status')->getValue());
  }

}
