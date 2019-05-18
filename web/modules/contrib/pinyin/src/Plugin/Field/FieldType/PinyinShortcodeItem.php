<?php

namespace Drupal\pinyin\Plugin\Field\FieldType;

use Drupal\Core\Field\Plugin\Field\FieldType\StringItem;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the "pinyin_shortcode" entity field type.
 *
 * @FieldType(
 *   id = "pinyin_shortcode",
 *   label = @Translation("Pinyin short code"),
 *   description = @Translation("A field containing pinyin short code value."),
 *   default_widget = "string_textfield",
 *   default_formatter = "string"
 * )
 */
class PinyinShortcodeItem extends StringItem {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'source_field' => NULL,
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $element = parent::storageSettingsForm($form, $form_state, $has_data);

    $element['source_field'] = [
      '#type' => 'textfield',
      '#title' => t('Source field'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function applyDefaultValue($notify = TRUE) {
    parent::applyDefaultValue($notify);
    // Field preSave is only called if have values in field.
    $this->setValue(['value' => 'pinyin'], $notify);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    parent::preSave();

    $entity = $this->getEntity();
    if (!$source_field = $this->getSetting('source_field')) {
      $source_field = $entity->getEntityType()->getKey('label');
    }
    if (!$entity->isNew()) {
      if ($entity->$source_field->value == $entity->original->$source_field->value) {
        return;
      }
    }

    $this->value = \Drupal::service('pinyin.shortcode')
      ->transliterate($entity->$source_field->value, 'en', '?', $this->getSetting('max_length'));
  }

}
