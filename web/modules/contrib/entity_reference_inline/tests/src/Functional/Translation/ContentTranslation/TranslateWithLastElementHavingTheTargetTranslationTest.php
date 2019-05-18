<?php

namespace Drupal\Tests\entity_reference_inline\Functional\Translation\ContentTranslation;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests content translation for the inline entity reference widget.
 *
 * @group entity_reference_inline
 */
class TranslateWithLastElementHavingTheTargetTranslationTest extends BrowserTestBase {

  /**
   * The entity types to test with.
   *
   * Will be set by
   * ::testTranslatingWithLastLevelReferenceHavingTheTargetTranslation and used
   * by :: doTestTranslatingWithLastLevelReferenceHavingTheTargetTranslation.
   *
   * @var array
   */
  protected $entityTypes = [];

  /**
   * The entity types to enable content translation for.
   *
   * Will be set by
   * ::testTranslatingWithLastLevelReferenceHavingTheTargetTranslation and used
   * by :: doTestTranslatingWithLastLevelReferenceHavingTheTargetTranslation.
   *
   * @var array
   */
  protected $entityTypesEnableContentTranslation = [];

  /**
   * {@inheritdoc}
   */
  public static $modules = ['entity_reference_inline', 'entity_test', 'content_translation'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create three languages.
    ConfigurableLanguage::createFromLangcode('l1')->save();
    ConfigurableLanguage::createFromLangcode('l2')->save();
    ConfigurableLanguage::createFromLangcode('l3')->save();
  }

  /**
   * Tests the correct content translation of an entity structure with the last
   * element being already translated into the target translation and having as
   * a source language of the target translation a language different than the
   * source translation language used to translate the whole entity structure.
   */
  public function testTranslatingWithLastLevelReferenceHavingTheTargetTranslation() {
    $test_entity_types_combinations = $this->getEntityTypes();
    $entity_types_enable_content_translation = $this->getEntityTypesEnableContentTranslation();

    foreach ($test_entity_types_combinations as $delta => $entity_types) {
      $this->entityTypes = $entity_types;
      $this->entityTypesEnableContentTranslation = $entity_types_enable_content_translation[$delta];

      foreach ($this->entityTypesEnableContentTranslation as $entity_type_data) {
        $this->setEnabledContentTranslation($entity_type_data['entity_type'], $entity_type_data['bundle'], TRUE);
      }

      $this->doTestCorrectTestSetup();
      $this->doTestTranslatingWithLastLevelReferenceHavingTheTargetTranslation(TRUE);

      // Delete all entities in order to execute the test again with the field
      // definition installed non-translatable otherwise it could not be
      // uninstalled.
      foreach ($entity_types as $entity_type_data) {
        $storage =  \Drupal::entityTypeManager()->getStorage($entity_type_data['entity_type']);
        $storage->resetCache();
        $storage->delete($storage->loadMultiple());
      }

      $this->doTestTranslatingWithLastLevelReferenceHavingTheTargetTranslation(FALSE);

      foreach ($this->entityTypesEnableContentTranslation as $entity_type_data) {
        $this->setEnabledContentTranslation($entity_type_data['entity_type'], $entity_type_data['bundle'], FALSE);
      }
    }
  }

  /**
   * Ensures a correct test setup.
   *
   * The first and the last entity types should be enabled for content
   * translation.
   */
  protected function doTestCorrectTestSetup() {
    reset($this->entityTypes);
    $first_entity_type_data = current($this->entityTypes);
    $last_entity_type_data = end($this->entityTypes);

    $content_translation_manager = \Drupal::service('content_translation.manager');

    $ct_first_entity_type = $content_translation_manager->isEnabled($first_entity_type_data['entity_type'], $first_entity_type_data['bundle']);
    $ct_last_entity_type = $content_translation_manager->isEnabled($last_entity_type_data['entity_type'], $last_entity_type_data['bundle']);

    $this->assertTrue($ct_first_entity_type && $ct_last_entity_type, 'The first and the last levels of the entity structure have to be enabled for content translation for this test.');
  }

  /**
   * Helper method for testTranslatingWithLastLevelReferenceHavingTheTargetTranslation.
   *
   * @param $translatable_inline_field
   *   Whether to test with a translatable or non-translatable field.
   */
  protected function doTestTranslatingWithLastLevelReferenceHavingTheTargetTranslation($translatable_inline_field) {
    $this->createInlineReferenceFields($translatable_inline_field);

    $web_user = $this->drupalCreateUser($this->getTranslatorPermissions());
    $this->drupalLogin($web_user);

    $entity_type_manager = \Drupal::entityTypeManager();

    $default_langcode = 'l1';
    $target_langcode = 'l3';
    $last_level_src_translation_of_target = 'l2';

    // Create the entity structure with all the entities having one translation
    // with the default language code and the last entity having three
    // translations - the default one, an intermediate one and the target
    // translation of the test having as a source language the intermediate one.
    $reversed_entity_types = array_reverse($this->entityTypes);
    $reversed_entity_structure = [];
    $count = count($reversed_entity_types);
    for ($i = 0; $i < $count; $i++) {
      $entity_type_id = $reversed_entity_types[$i]['entity_type'];
      $bundle = $reversed_entity_types[$i]['bundle'];
      $entity_type = $entity_type_manager->getDefinition($entity_type_id);
      $bundle_key = $entity_type->getKey('bundle');
      $label_key = $entity_type->getKey('label');
      $langcode_key = $entity_type->getKey('langcode');

      $level_name = $count - 1 - $i;
      $values = [$label_key => "level_{$level_name}", $langcode_key => $default_langcode];
      if ($bundle_key) {
        $values[$bundle_key] = $bundle;
      }

      if ($i != 0) {
        $field_name_inline_reference = $reversed_entity_types[$i]['field_name'];
        $values[$field_name_inline_reference] = $reversed_entity_structure[$i - 1];
        /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
        $entity = $this->createEntity($entity_type_manager->getStorage($entity_type_id), $entity_type_id, $values);
      }
      // The last entity should get also the target translation language.
      else {
        /** @var \Drupal\content_translation\ContentTranslationManagerInterface $content_translation_manager */
        $content_translation_manager = \Drupal::service('content_translation.manager');

        /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
        $entity = $this->createEntity($entity_type_manager->getStorage($entity_type_id), $entity_type_id, $values);

        // Translate to an intermediate translation to be used as a source for
        // the target translation.
        $translation = $entity->addTranslation($last_level_src_translation_of_target, $entity->toArray());
        $metadata = $content_translation_manager->getTranslationMetadata($translation);
        $metadata->setSource($default_langcode);

        $translation = $translation->addTranslation($target_langcode, $translation->toArray());
        $metadata_l3 = $content_translation_manager->getTranslationMetadata($translation);
        $metadata_l3->setSource($last_level_src_translation_of_target);
      }

      $reversed_entity_structure[$i] = $entity;
    }

    // Saving only the main entity should trigger saving the referenced
    // entities as well.
    /** @var \Drupal\Core\Entity\ContentEntityInterface $first_level_entity */
    $first_level_entity = end($reversed_entity_structure);
    $first_level_entity->save();
    $first_level_entity = $entity_type_manager->getStorage($first_level_entity->getEntityTypeId())->load($first_level_entity->id());

    // Assert that all the entities have only the default language code and the
    // last one has the target and an intermediate one translation.
    /** @var \Drupal\Core\Entity\ContentEntityInterface $current_entity */
    $current_entity = $first_level_entity;
    $translation_langcodes = array_keys($current_entity->getTranslationLanguages());
    $this->assertEquals([$default_langcode], $translation_langcodes);
    $entity_data[0][$default_langcode] = $current_entity->getTranslation($default_langcode)->toArray();

    for ($i = 1; $i < $count; $i++) {
      $field_name_inline_reference = $this->entityTypes[$i - 1]['field_name'];
      $current_entity = $current_entity->$field_name_inline_reference->entity;
      $this->assertNotNull($current_entity);

      $expected = [$default_langcode];

      // Intermediate levels.
      if ($i != ($count - 1)) {
        $translation_langcodes = array_keys($current_entity->getTranslationLanguages());
        $this->assertEquals([$default_langcode], $translation_langcodes);
        $entity_data[$i][$default_langcode] = $current_entity->toArray();
      }
      // Last level.
      else {
        $translation_langcodes = array_keys($current_entity->getTranslationLanguages());
        $expected[] = $last_level_src_translation_of_target;
        $expected[] = $target_langcode;
        sort($expected);
        sort($translation_langcodes);
        $this->assertEquals($expected, $translation_langcodes);
      }

      foreach ($translation_langcodes as $langcode) {
        $entity_data[$i][$langcode] = $current_entity->getTranslation($langcode)->toArray();
      }
    }

    // Translate the entity structure.
    $add_url = Url::fromRoute("entity.{$first_level_entity->getEntityTypeId()}.content_translation_add",
      [
        $first_level_entity->getEntityTypeId() => $first_level_entity->id(),
        'source' => $default_langcode,
        'target' => $target_langcode,
      ],
      [
        'language' => ConfigurableLanguage::load($target_langcode),
      ]
      );
    $this->drupalPostForm($add_url, [], $this->getFormSubmitAction());

    // Reset the entity cache of the whole entity structure.
    foreach ($this->entityTypes as $entity_type_data) {
      $entity_type_manager->getStorage($entity_type_data['entity_type'])->resetCache();
    }

    // Assert correct translations without changes to already existing
    // translations.
    $first_level_entity = $entity_type_manager->getStorage($first_level_entity->getEntityTypeId())->load($first_level_entity->id());

    $entity_data_after_translation = $this->assertEntityReferenceCorrectTranslation($first_level_entity, $default_langcode, $target_langcode, $last_level_src_translation_of_target);

    $this->assertEntityDataCorrectAfterTranslation($entity_data, $entity_data_after_translation);
  }

  /**
   * Check if the translations are correctly set in all references.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $current_entity
   *
   * @return array
   *   Array structure of all entities by level and langcode
   *   level =>
   *     langcode =>
   *       entity_data
   */
  protected function assertEntityReferenceCorrectTranslation($current_entity, $default_langcode, $target_langcode, $last_level_src_translation_of_target) {
    $translation_langcodes = array_keys($current_entity->getTranslationLanguages());
    $expected = [$default_langcode, $target_langcode];
    sort($expected);
    sort($translation_langcodes);
    $this->assertEquals($expected, $translation_langcodes);

    $entity_data_after_translation = [];
    foreach (array_keys($current_entity->getTranslationLanguages()) as $langcode) {
      $entity_data_after_translation[0][$langcode] = $current_entity->getTranslation($langcode)->toArray();
    }

    $count = count($this->entityTypes);
    for ($i = 1; $i < $count; $i++) {
      $field_name_inline_reference = $this->entityTypes[$i - 1]['field_name'];
      $current_entity = $current_entity->$field_name_inline_reference->entity;

      $expected = [$default_langcode];

      // Intermediate levels.
      if ($i != ($count - 1)) {
        $translation_langcodes = array_keys($current_entity->getTranslationLanguages());
        sort($expected);
        sort($translation_langcodes);
        $this->assertEquals($expected, $translation_langcodes, 'Translation mismatch for level ' . $i);

        foreach ($translation_langcodes as $langcode) {
          $entity_data_after_translation[$i][$langcode] = $current_entity->getTranslation($langcode)->toArray();
        }
      }
      // Last level.
      else {
        $translation_langcodes = array_keys($current_entity->getTranslationLanguages());
        $expected[] = $target_langcode;
        $expected[] = $last_level_src_translation_of_target;
        sort($expected);
        sort($translation_langcodes);
        $this->assertEquals($expected, $translation_langcodes);

        foreach ($translation_langcodes as $langcode) {
          $entity_data_after_translation[$i][$langcode] = $current_entity->getTranslation($langcode)->toArray();
        }
      }
    }

    return $entity_data_after_translation;
  }

  /**
   * Checks for correct entity data after the content translation.
   *
   * @param $entity_data_before
   *   The entity data before the translation.
   * @param $entity_data_after
   *   The entity data after the translation.
   */
  protected function assertEntityDataCorrectAfterTranslation($entity_data_before, $entity_data_after) {
    reset($entity_data_before);
    end($entity_data_before);
    $last_level = key($entity_data_before);
    foreach ($entity_data_before as $level => $entity_level_data_before) {
      $entity_level_data_after = $entity_data_after[$level];

      if ($level != $last_level) {
        foreach ($entity_level_data_before as $langcode => $data) {
          $this->assertEquals($data, $entity_level_data_after[$langcode]);
        }
      }
      else {
        $this->assertEquals($entity_level_data_before, $entity_level_data_after);
      }

    }
  }

  /**
   * Returns an array of entity types structure to test with.
   *
   * @return array
   */
  protected function getEntityTypes() {
    $entityTypes = [
      0 => [
        0 => ['entity_type' => 'entity_test_mul', 'bundle' => 'entity_test_mul', 'field_name' => 'entity_reference_inline', 'field_type' => 'entity_reference_inline'],
        1 => ['entity_type' => 'entity_test_mul', 'bundle' => 'entity_test_mul', 'field_name' => 'entity_reference_inline', 'field_type' => 'entity_reference_inline'],
        2 => ['entity_type' => 'entity_test_mul', 'bundle' => 'entity_test_mul', 'field_name' => 'entity_reference_inline'],
      ],
    ];
    return $entityTypes;
  }

  /**
   * Returns an array of entity types structure to enable content translation
   * for.
   *
   * @return array
   */
  protected function getEntityTypesEnableContentTranslation() {
    $entityTypesEnableContentTranslation = [
      0 => [
        0 => ['entity_type' => 'entity_test_mul', 'bundle' => 'entity_test_mul'],
      ],
    ];
    return $entityTypesEnableContentTranslation;
  }

  /**
   * Creates an inline reference field or alters its translatability.
   *
   * @param $translatable_inline_field
   *   Whether the inline reference field should be translatable or not.
   */
  protected function createInlineReferenceFields($translatable_inline_field) {
    /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager */
    $entity_field_manager = \Drupal::service('entity_field.manager');
    $entity_definition_update_manager = \Drupal::entityDefinitionUpdateManager();
    for ($i = 0; $i < count($this->entityTypes) - 1; $i++) {
      $entity_type = $this->entityTypes[$i]['entity_type'];
      $bundle = $this->entityTypes[$i]['bundle'];
      $field_name = $this->entityTypes[$i]['field_name'];
      $referenced_entity_type = $this->entityTypes[$i + 1]['entity_type'];
      $referenced_entity_bundle = $this->entityTypes[$i + 1]['bundle'];

      // Allow for base fields to be used as inline reference field.
      $field_definitions = $entity_field_manager->getFieldDefinitions($entity_type, $bundle);
      if (isset($field_definitions[$field_name])) {
        $field_definitions[$field_name]->getConfig($bundle)->setTranslatable($translatable_inline_field)->save();
      }
      else {
        if ($field_storage = FieldStorageConfig::load($entity_type . '.' . $field_name)) {
          $field_storage->setTranslatable($translatable_inline_field);
          $field_storage->save();
          $entity_definition_update_manager->updateFieldStorageDefinition($field_storage);
        }
        else {
          $field_type = $this->entityTypes[$i]['field_type'];
          FieldStorageConfig::create([
            'field_name' => $field_name,
            'entity_type' => $entity_type,
            'type' => $field_type,
            'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
            'translatable' => $translatable_inline_field,
            'settings' => [
              'target_type' => $referenced_entity_type,
            ],
          ])
            ->save();
        }

        if ($field_config = FieldConfig::load($entity_type . '.' . $bundle . '.' . $field_name)) {
          $field_config->setTranslatable($translatable_inline_field);
          $field_config->save();
        }
        else {
          FieldConfig::create([
            'field_name' => $field_name,
            'entity_type' => $entity_type,
            'bundle' => $bundle,
            'translatable' => $translatable_inline_field,
            'settings' => [
              'handler' => 'default',
              'handler_settings' => [
                'target_bundles' => [
                  $referenced_entity_type => $referenced_entity_bundle,
                ],
              ],
            ],
          ])->save();

          // Activate the widget in case the field is being created now.
          // @todo: fails currently with "schema errors for core.entity_form_display.entity_test_mul.entity_test_mul.default
          // with the following errors: core.entity_form_display.entity_test_mul.entity_test_mul.default:content.entity_reference_inline.settings.form_modes_bundles
          // missing schema"
//          entity_get_form_display($entity_type, $bundle, 'default')
//            ->setComponent($field_name)
//            ->save();
        }
      }

      // Make sure both overrides are present.
      $entity_field_manager->clearCachedFieldDefinitions();
    }
  }

  /**
   * Returns an array of permissions needed to translate the entity structure.
   *
   * @return array
   *   An array of user permissions.
   */
  protected function getTranslatorPermissions() {
    $entity_type_bundle_level_zero_data = reset($this->entityTypes);
    $entity_type = $entity_type_bundle_level_zero_data['entity_type'];
    $bundle = $entity_type_bundle_level_zero_data['bundle'];
    $translate_permission = \Drupal::entityTypeManager()->getDefinition($entity_type)->getPermissionGranularity() == 'bundle' ? "translate {$bundle} {$entity_type}" : "translate {$entity_type}";
    $permissions = ["administer entity_test content", "view test entity", 'create content translations', 'update content translations', $translate_permission];
    return $permissions;
  }

  /**
   * The submit action name to save the translated form content.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string
   */
  protected function getFormSubmitAction() {
    return t('Save');
  }

  /**
   * Creates an entity based on the provided values.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The storage to use to create the entity.
   * @param $entity_type_id
   *   The entity type id.
   * @param $values
   *   The values to use to create the entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The created entity.
   */
  protected function createEntity(EntityStorageInterface $storage, $entity_type_id, $values) {
    return $storage->create($values);
  }

  /**
   * Enables translation for the current entity type and bundle.
   */
  protected function setEnabledContentTranslation($entity_type_id, $bundle, $enabled) {
    // Enable translation for the current entity type and ensure the change is
    // picked up.
    \Drupal::service('content_translation.manager')->setEnabled($entity_type_id, $bundle, $enabled);
    drupal_static_reset();
    \Drupal::entityManager()->clearCachedDefinitions();
    \Drupal::service('router.builder')->rebuild();
    \Drupal::service('entity.definition_update_manager')->applyUpdates();
  }

}
