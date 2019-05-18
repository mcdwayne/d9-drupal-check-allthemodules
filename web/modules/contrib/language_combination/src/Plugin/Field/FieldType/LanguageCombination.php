<?php

namespace Drupal\language_combination\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Plugin implementation of the 'language_combination' field type.
 *
 * @FieldType(
 *   id = "language_combination",
 *   label = @Translation("Language Combination"),
 *   description = @Translation("Allows the definition of language combinations (e.g. 'From english to german')."),
 *   default_widget = "language_combination_default",
 *   default_formatter = "language_combination_default",
 *   constraints = {"LanguageCombination" = {}},
 *   multiple = true,
 * )
 */
class LanguageCombination extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field) {
    $property_definitions['language_source'] = DataDefinition::create('string')
      ->setLabel(t('From language'));
    $property_definitions['language_target'] = DataDefinition::create('string')
      ->setLabel(t('To language'));
    return $property_definitions;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field) {
    return [
      'columns' => [
        'language_source' => [
          'description' => 'The langcode of the source language which the user is able to translate from.',
          'type'        => 'varchar',
          'length'      => 10,
        ],
        'language_target' => [
          'description' => 'The langcode of the target language which the user is able to translate into.',
          'type'        => 'varchar',
          'length'      => 10,
        ],
      ],
      'indexes' => [
        'language' => ['language_source', 'language_target'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    if (empty($this->language_source)
      || empty($this->language_target)
      || $this->language_source == '_none'
      || $this->language_target == '_none') {
         return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    parent::preSave();

    // In case the skill languages is not know to the system, install them.
    $languages = \Drupal::languageManager()->getLanguages();
    if (!isset($languages[$this->language_source])) {
      $language = ConfigurableLanguage::createFromLangcode($this->language_source);
      $language->save();
    }
    if (!isset($languages[$this->language_target])) {
      $language = ConfigurableLanguage::createFromLangcode($this->language_target);
      $language->save();
    }
  }


}
