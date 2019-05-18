<?php

namespace Drupal\cyr2lat\Services;

use Drupal\content_translation\ContentTranslationManagerInterface;
use Drupal\Core\Config\ConfigException;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Messenger\MessengerTrait;

/**
 * Class Cyr2LatTransliterator.
 *
 * @package Drupal\cyr2lat
 */
class Cyr2LatTransliterator {

  use MessengerTrait;
  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Field types that are transliterable.
   *
   * @var array
   */
  protected $transliterableTypes;

  /**
   * The content translation manager.
   *
   * @var \Drupal\content_translation\ContentTranslationManagerInterface
   */
  protected $contentTranslationManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Cyrillic language.
   *
   * @var \Drupal\core\Language\LanguageInterface|null
   */
  public $cyrillicLanguage;

  /**
   * Latin language.
   *
   * @var \Drupal\core\Language\LanguageInterface|null
   */
  public $latinLanguage;

  /**
   * Destination Language.
   *
   * @var \Drupal\core\Language\LanguageInterface|null
   */
  protected $destinationLanguage;

  /**
   * Cyrillic entities.
   *
   * @var array
   */
  protected $cyrillicEntity;

  /**
   * Latin entities.
   *
   * @var array
   */
  protected $latinEntity;

  /**
   * Configuration object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Cyr2LatTransliterator constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity Type Manager.
   * @param \Drupal\content_translation\ContentTranslationManagerInterface $content_translation_manager
   *   Content Translation Manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ContentTranslationManagerInterface $content_translation_manager, LanguageManagerInterface $language_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->contentTranslationManager = $content_translation_manager;
    $this->languageManager = $language_manager;
    // @todo Add dependency injection.
    $this->config = \Drupal::config('cyr2lat.settings');

    // Check if configuration is properly set.
    if ($this->config->get('enabled') && (!$this->config->get('cyrillic_language') || !$this->config->get('latin_language'))) {
      throw new ConfigException('Cyr2Lat enabled but languages are not properly configured.');
    }

    // Load Cyrillic and Latin languages.
    $this->cyrillicLanguage = $this->languageManager->getLanguage($this->config->get('cyrillic_language'));
    $this->latinLanguage = $this->languageManager->getLanguage($this->config->get('latin_language'));

    // List of field types that are transliterable.
    $this->transliterableTypes = [
      'string',
      'string_long',
      'text',
      'text_long',
      'text_with_summary',
      'link',
      'file',
      'image',
    ];
  }

  /**
   * Check if entity satisfies conditions to be transliterated.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Entity object.
   *
   * @return bool
   *   Whether entity should be transliterated.
   */
  public function isTransliterable(ContentEntityInterface $entity) {

    // Make sure that we process entity only once.
    $type = $entity->getEntityTypeId();
    $id = $entity->id();

    $entities_in_translation = &drupal_static("cyr2lat_translate", []);
    if (!empty($entities_in_translation["$type:$id"])) {
      return FALSE;
    }

    $entities_in_translation["$type:$id"] = TRUE;

    // @todo Check if configs exist.

    // Check if entity is cyrillic.
    $cyrillic_langcode = $this->cyrillicLanguage->getId();

    if ($entity->language()->getId() != $cyrillic_langcode) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Transliterate entity fields from Cyrillic to Latin.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Entity object.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function transliterateEntity(ContentEntityInterface $entity) {
    // Form identifier for the entity.
    $type = $entity->getEntityTypeId();
    $id = $entity->id();
    $cid = "$type:$id";

    // Prepare entity transliteration.
    $this->prepareTranslation($entity, $cid);

    // Get transliterable fields.
    $transliterable_fields = $this->getTransliterableFields($cid);

    // Get translatable fields.
    $translatable_fields = $this->getTranslatableFields($transliterable_fields, $cid);

    // Transliterate node.
    $this->doTransliterate($translatable_fields, $cid);
  }

  /**
   * Prepare entity translations.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity being translated.
   * @param string $cid
   *   Identifier in form $entity_type:$entity_id.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function prepareTranslation(ContentEntityInterface $entity, $cid) {
    // Make sure that we process entity only once.
    $translation_prepared = &drupal_static('cyr2lat_prepare', []);
    if (empty($translation_prepared[$cid])) {
      $translation_prepared[$cid] = TRUE;

      $cyr_langcode = $this->cyrillicLanguage->getId();
      $lat_langcode = $this->latinLanguage->getId();

      // Cyrillic translation.
      $this->cyrillicEntity[$cid] = $entity->getTranslation($cyr_langcode);

      // Remove existing latin translation.
      if ($entity->hasTranslation($lat_langcode)) {
        $entity->removeTranslation($lat_langcode);
      }

      // Get label key.
      $entity_label_key = $entity->getEntityType()->getKey('label');

      // Check if entity has label key.
      $translated_values = [];
      if (!empty($entity_label_key)) {
        $translated_values[$entity_label_key] = $entity->label();
      }

      /** @var \Drupal\Core\Entity\ContentEntityInterface $latin_entity */
      // Create initial latin entity.
      $latin_entity = $this->cyrillicEntity[$cid]->addTranslation($lat_langcode, $translated_values);

      // If there is an image field in the entity, transliteration breaks
      // unless we save empty translation first.
      // @todo Figure out why we need to save the node first.
      $latin_entity->updateLoadedRevisionId();
      $latin_entity->setNewRevision(FALSE);
      $latin_entity->save();

      $this->latinEntity[$cid] = $latin_entity;
    }
  }

  /**
   * Get fields from node that are transliterable.
   *
   * @param string $cid
   *   Identifier in form $entity_type:$entity_id.
   *
   * @return array
   *   List of fields.
   */
  protected function getTransliterableFields($cid) {
    $transliterable_fields = [];

    // Get all fields from entity.
    $entity_fields = $this->cyrillicEntity[$cid]->getFields();

    // Check which fields are capable of transliteration.
    foreach ($entity_fields as $index => $entity_field) {
      /** @var \Drupal\Core\Field\FieldItemList $entity_field */
      /** @var \Drupal\field\Entity\FieldConfig $field_definition */
      $field_definition = $entity_field->getFieldDefinition();
      $field_type = $field_definition->getType();

      if (in_array($field_type, $this->transliterableTypes)) {
        $transliterable_fields[$index] = $entity_field;
      }
    }

    return $transliterable_fields;
  }

  /**
   * Get fields from node that are transliterable.
   *
   * @param array $transliterable_fields
   *   List of transliterable fields.
   * @param string $cid
   *   Identifier in form $entity_type:$entity_id.
   *
   * @return array
   *   List of fields.
   */
  protected function getTranslatableFields(array $transliterable_fields, $cid) {
    // Get list of translatable fields for entity.
    $translatable_fields = $this->cyrillicEntity[$cid]->getTranslatableFields();

    // Find which transliterable fields are translatable.
    foreach ($transliterable_fields as $field_name => $field) {
      /** @var \Drupal\Core\Field\FieldItemList $field */
      // Remove all fields that are not translatable.
      if (!isset($translatable_fields[$field_name])) {
        unset($transliterable_fields[$field_name]);
      }
    }

    return $transliterable_fields;
  }

  /**
   * Get all paragraph fields.
   *
   * @param string $cid
   *   Identifier in form $entity_type:$entity_id.
   *
   * @return array
   *   List of paragraph fields.
   */
  protected function getParagraphFields($cid) {
    $paragraph_fields = [];

    // Get all fields from entity.
    $entity_fields = $this->cyrillicEntity[$cid]->getFields();

    // Check which fields are capable of transliteration.
    foreach ($entity_fields as $index => $entity_field) {
      /** @var \Drupal\Core\Field\FieldItemList $entity_field */
      /** @var \Drupal\field\Entity\FieldConfig $field_definition */
      $field_definition = $entity_field->getFieldDefinition();
      $field_type = $field_definition->getType();

      if ($field_type == 'entity_reference_revisions') {
        $paragraph_fields[$index] = $entity_field;
      }
    }

    return $paragraph_fields;
  }

  /**
   * Transliterate entity fields.
   *
   * @param array $transliterable_fields
   *   List of transliterable fields.
   * @param string $cid
   *   Identifier in form $entity_type:$entity_id.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function doTransliterate(array $transliterable_fields, $cid) {
    // Make sure that we process entity only once.
    $transliterated = &drupal_static("cyr2lat_transliterate", []);
    if (empty($transliterated[$cid])) {
      $transliterated[$cid] = TRUE;

      // Loop through all fields.
      foreach ($transliterable_fields as $field_name => $field) {
        // Get field values.
        $field_values = $field->getValue();

        // Transliterate field.
        foreach ($field_values as $index => $field_value) {
          foreach ($field_value as $id => $item) {
            $field_values[$index][$id] = $this->cir2lat($item);
          }
        }

        // Set transliterated field.
        $this->latinEntity[$cid]->set($field_name, $field_values);

      }

      // Translate all paragraph fields.
      $paragraph_fields = $this->getParagraphFields($cid);
      foreach ($paragraph_fields as $index => $paragraph_field) {
        $paragraphs = $paragraph_field->referencedEntities();
        foreach ($paragraphs as $paragraph) {
          $this->transliterateEntity($paragraph);
        }
      }

      // Save entity.
      try {
        $this->latinEntity[$cid]->save();
        $message = $this->t("Latin translation was automatically updated.");
        $this->messenger()->addMessage($message);
      }
      catch (EntityStorageException $e) {
        $this->messenger()->addMessage($e->getMessage(), 'error');
      }
    }
  }

  /**
   * Transliterate Cyrillic to Latin.
   *
   * @param string $string
   *   Word/phrase to be transliterated.
   *
   * @return string
   *   String transliterated to Latin.
   */
  public function cir2lat($string) {
    $search = [
      'њ',
      'љ',
      'а',
      'б',
      'в',
      'г',
      'д',
      'ђ',
      'e',
      'ж',
      'з',
      'и',
      'ј',
      'к',
      'л',
      'м',
      'н',
      'о',
      'п',
      'р',
      'с',
      'т',
      'ћ',
      'у',
      'ф',
      'х',
      'ц',
      'ч',
      'џ',
      'ш',
      'Њ',
      'Љ',
      'А',
      'Б',
      'В',
      'Г',
      'Д',
      'Ђ',
      'Е',
      'Ж',
      'З',
      'И',
      'Ј',
      'К',
      'Л',
      'М',
      'Н',
      'О',
      'П',
      'Р',
      'С',
      'Т',
      'Ћ',
      'У',
      'Ф',
      'Х',
      'Ц',
      'Ч',
      'Џ',
      'Ш',
    ];
    $replace = [
      'nj',
      'lj',
      'a',
      'b',
      'v',
      'g',
      'd',
      'đ',
      'e',
      'ž',
      'z',
      'i',
      'j',
      'k',
      'l',
      'm',
      'n',
      'o',
      'p',
      'r',
      's',
      't',
      'ć',
      'u',
      'f',
      'h',
      'c',
      'č',
      'dž',
      'š',
      'Nj',
      'Lj',
      'A',
      'B',
      'V',
      'G',
      'D',
      'Đ',
      'E',
      'Ž',
      'Z',
      'I',
      'J',
      'K',
      'L',
      'M',
      'N',
      'O',
      'P',
      'R',
      'S',
      'T',
      'Ć',
      'U',
      'F',
      'H',
      'C',
      'Č',
      'Dž',
      'Š',
    ];
    $result = str_replace($search, $replace, $string);
    return $result;
  }

}
