<?php

namespace Drupal\code\Plugin\Field\FieldType;

use Drupal\Core\Field\Plugin\Field\FieldType\StringItem;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the "code" entity field type.
 *
 * @FieldType(
 *   id = "code",
 *   label = @Translation("Code"),
 *   description = @Translation("A field containing code value."),
 *   default_widget = "string_textfield",
 *   default_formatter = "string",
 *   list_class = "\Drupal\code\Plugin\Field\FieldType\CodeFieldItemList"
 * )
 */
class CodeItem extends StringItem {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'encoding_rules' => '[code:rules:01]',
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $token_tree = [
      '#theme' => 'token_tree_link',
      '#token_types' => [$this->getFieldDefinition()->getTargetEntityTypeId()],
    ];
    $rendered_token_tree = \Drupal::service('renderer')->render($token_tree);
    $element['encoding_rules'] = [
      '#type' => 'textfield',
      '#title' => t('Encoding rule'),
      '#description' => $this->t('This field supports tokens. @browse_tokens_link', ['@browse_tokens_link' => $rendered_token_tree]),
      '#default_value' => $this->getSetting('encoding_rules'),
      '#required' => TRUE,
      '#disabled' => $has_data,
    ];

    return $element;
  }

}
