<?php

namespace Drupal\Tests\date_time_day\Kernel;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Language\Language;
use Drupal\date_time_day\Plugin\Field\FieldType\DateTimeDayItem;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Test to ensure the date_time_day separators are translatable.
 *
 * @group date_time_day
 */
class SeparatorsTranslationTest extends KernelTestBase {

  /**
   * A field storage to use in this test class.
   *
   * @var \Drupal\field\Entity\FieldStorageConfig
   */
  protected $fieldStorage;

  /**
   * The field used in this test class.
   *
   * @var \Drupal\field\Entity\FieldConfig
   */
  protected $field;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'datetime',
    'date_time_day',
    'entity_test',
    'field',
    'language',
    'system',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('user');
    $this->installConfig(['system']);
    $this->installSchema('system', ['sequences', 'key_value']);

    // Add a date_time_day field.
    $this->fieldStorage = FieldStorageConfig::create([
      'field_name' => Unicode::strtolower($this->randomMachineName()),
      'entity_type' => 'entity_test',
      'type' => 'datetimeday',
      'settings' => ['datetime_type' => DateTimeDayItem::DATEDAY_TIME_DEFAULT_TYPE_FORMAT],
    ]);
    $this->fieldStorage->save();

    $this->field = FieldConfig::create([
      'field_storage' => $this->fieldStorage,
      'bundle' => 'entity_test',
      'required' => TRUE,
    ]);
    $this->field->save();

    $display_options = [
      'type' => 'datetimeday_default',
      'label' => 'hidden',
      'settings' => [
        'format_type' => 'fallback',
        'time_format_type' => 'fallback',
        'day_separator' => 'D_UNTRANSLATED',
        'time_separator' => 'T_UNTRANSLATED',
      ],
    ];
    EntityViewDisplay::create([
      'targetEntityType' => $this->field->getTargetEntityTypeId(),
      'bundle' => $this->field->getTargetBundle(),
      'mode' => 'default',
      'status' => TRUE,
    ])->setComponent($this->fieldStorage->getName(), $display_options)
      ->save();
  }

  /**
   * Tests the translation of the date_time_day separators.
   */
  public function testSeparatorsTranslation() {
    // Create an entity.
    $entity = EntityTest::create([
      'name' => $this->randomString(),
      $this->fieldStorage->getName() => [
        'value' => '2018-02-06',
        'start_time_value' => '10:00',
        'end_time_value' => '10:00',
      ],
    ]);

    // Verify the untranslated separator.
    $display = EntityViewDisplay::collectRenderDisplay($entity, 'default');
    $build = $display->build($entity);
    $output = $this->container->get('renderer')->renderRoot($build);
    $this->verbose($output);
    $this->assertContains('D_UNTRANSLATED', (string) $output);
    $this->assertContains('T_UNTRANSLATED', (string) $output);

    // Translate the separators.
    ConfigurableLanguage::createFromLangcode('nl')->save();
    /** @var \Drupal\language\ConfigurableLanguageManagerInterface $language_manager */
    $language_manager = $this->container->get('language_manager');
    $language_manager->getLanguageConfigOverride('nl', 'core.entity_view_display.entity_test.entity_test.default')
      ->set('content.' . $this->fieldStorage->getName() . '.settings.day_separator', 'DNL_TRANSLATED!')
      ->save();
    $language_manager->getLanguageConfigOverride('nl', 'core.entity_view_display.entity_test.entity_test.default')
      ->set('content.' . $this->fieldStorage->getName() . '.settings.time_separator', 'TNL_TRANSLATED!')
      ->save();

    $this->container->get('language.config_factory_override')
      ->setLanguage(new Language(['id' => 'nl']));
    $this->container->get('cache_tags.invalidator')->invalidateTags($entity->getCacheTags());
    $display = EntityViewDisplay::collectRenderDisplay($entity, 'default');
    $build = $display->build($entity);
    $output = $this->container->get('renderer')->renderRoot($build);
    $this->verbose($output);
    $this->assertContains('DNL_TRANSLATED!', (string) $output);
    $this->assertContains('TNL_TRANSLATED!', (string) $output);
  }

}
