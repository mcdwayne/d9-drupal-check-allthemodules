<?php

namespace Drupal\Tests\stress_test_add_translations\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\Entity\Node;
use Drupal\Tests\BrowserTestBase;

/**
 * Class StressAddTranslationsTest.
 *
 * @package Drupal\Test\stress_test_add_translations\Functional
 *
 * @group StressAddTranslationsTest
 */
class StressAddTranslationsTest extends BrowserTestBase {

  /**
   * Amount of languages to be used.
   */
  const LANGUAGES_COUNT = 300;
  /**
   * Amount of translatable fields to be created within a content type.
   */
  const FIELDS_COUNT = 10;
  /**
   * {@inheritdoc}
   */
  public $verbose = TRUE;
  /**
   * {@inheritdoc}
   */
  protected $profile = 'standard';
  /**
   * {@inheritdoc}
   */
  public static $modules = ['stress_test_add_translations'];
  /**
   * Language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  private $languageManager;
  /**
   * Default language ID.
   *
   * @var string
   */
  private $defaultLanguage;
  /**
   * Fake languages array.
   *
   * @var array
   */
  private $fakeLanguages = [];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    // Login as an admin user.
    $this->drupalLogin($this->createUser([], NULL, TRUE));
    // Enable translation for "Article" content type.
    $this->drupalPostForm(
      'admin/config/regional/content-language',
      [
        'entity_types[node]'                    => TRUE,
        'settings[node][article][translatable]' => TRUE,
      ],
      t('Save configuration')
    );
    $this->languageManager = \Drupal::service('language_manager');
    $this->defaultLanguage = $this->languageManager
      ->getDefaultLanguage()
      ->getId();
  }

  /**
   * Generate node with a lot of translations.
   */
  public function testAddingTranslations() {
    // Generate translatable text fields.
    $fields = [];
    for ($i = 0; $i < static::FIELDS_COUNT; $i++) {
      $fields[$i] = 'field_' . $this->randomMachineName();
      $this->createTextField($fields[$i]);
    }

    // Generate fake languages list.
    $this->fakeLanguages = $this->generateFakeLanguages();

    // Some basic assertions.
    $this->assertEqual(static::FIELDS_COUNT, count($fields), static::FIELDS_COUNT . ' fields has been created');
    $this->assertTrue(is_array($this->fakeLanguages), 'Languages list is an array');
    $this->assertEqual(static::LANGUAGES_COUNT, count($this->fakeLanguages), static::LANGUAGES_COUNT . ' languages prepared');


    // Create initial node with default language.
    /** @var \Drupal\node\Entity\Node $node */
    $node = Node::create([
      'type'     => 'article',
      'title'    => $this->randomString(24),
      'langcode' => $this->defaultLanguage,
    ]);
    // Fill the newly created fields with a random data.
    foreach ($fields as $field) {
      $node->set($field, $this->randomString(128));
      $this->assertTrue($node->hasField($field), 'Node has field - ' . $field);
      $this->assertFalse($node->get($field)->isEmpty(), 'Field "' . $field . '" is not empty');
    }
    $node->save();

    // Create languages.
    foreach ($this->fakeLanguages as $langcode) {
      // Ignore default langcode if it randomly come here.
      if ($this->languageManager->getDefaultLanguage()->getId() != $langcode) {
        // Create language from langcode.
        ConfigurableLanguage::createFromLangcode($langcode)->save();
        // Check if language has been created.
        $this->assertNotNull(
          ConfigurableLanguage::load($langcode),
          'Configurable language "' . $langcode . '" has been created'
        );
      }
    }

    // Create translation of the node for each language using UI.
    foreach ($this->fakeLanguages as $language) {
      // Prepare fields values.
      $edit = ['title[0][value]' => $this->randomString(24)];
      foreach ($fields as $field) {
        $edit[$field . '[0][value]'] = $this->randomString(128);
      }
      // Prepare translations adding path.
      $path = $language . '/node/' . $node->id()
        . '/translations/add/' . $this->defaultLanguage . '/' . $language;

      // Start timer for time execution report.
      $start = microtime(TRUE);
      // Submit new translation.
      $this->drupalPostForm($path, $edit, 'Save (this translation)');
      // Ensure submission process done without errors.
      $this->assertResponse(200);
      // Generate performance report for each translation.
      // Including 3 main measures:
      //  - translation creation time,
      //  - database size,
      //  - memory(RAM) usage,
      $this->generatePerformanceReport(microtime(TRUE) - $start);
    }
  }

  /**
   * Generate performance report.
   *
   * @param float $time
   *   Time spent for adding translation(in seconds).
   */
  private function generatePerformanceReport($time) {
    $this->export(self::array2Csv([
      $time,
      $this->getDatabaseSize(),
      $this->getMemoryUsage(),
    ]));
  }

  /**
   * Export measurements to the CSV file.
   */
  protected function export($string) {
    $filename = DRUPAL_ROOT . '/sites/simpletest/stress_test_results.csv';
    if (!file_exists($filename)) {
      touch($filename);
      // Add CSV file headers.
      $file = fopen($filename, 'w');
      fwrite($file, 'Translation creation time (sec),Database size (MB),Memory(RAM) usage (MB)');
      fwrite($file, "\r\n");
      fclose($file);
    }
    // Append report information line to the CSV file.
    $file = fopen($filename, 'a');
    fwrite($file, $string);
    fclose($file);
  }

  /**
   * Convert PHP array to CSV string.
   *
   * @param array $array
   *   Array to be converted.
   * @param string $delimiter
   *   Delimiter value.
   *
   * @return string
   *   CSV-formatted string.
   */
  private static function array2Csv(array $array = []) {
    $csv = '';
    foreach ($array as $value) {
      $csv .= ((string) $value) . ',';
    }
    return substr($csv, 0, -1) . "\r\n";
  }

  /**
   * Generate fake langcodes.
   *
   * @return array
   *   Fake langcodes array.
   */
  private function generateFakeLanguages() {
    $languages = [];
    for ($i = 0; $i < static::LANGUAGES_COUNT; $i++) {
      $string = $this->randomMachineName(5);
      // Remove digits from the langcodes.
      $string = preg_replace('/[0-9]+/', '', $string);
      // Ensure we don't have the same languages
      // in a languages list. If so - repeat loop iteration again.
      if (in_array($string, $languages)) {
        $i--; continue;
      }
      $languages[] = $string;
    }
    return count($languages) > static::LANGUAGES_COUNT
      ? array_slice($languages, 0, static::LANGUAGES_COUNT)
      : $languages;
  }

  /**
   * Create translatable text field in "Article" content type.
   *
   * @param string|null $field_name
   *   Field name.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function createTextField($field_name = NULL) {
    // Generate random field name if not passed as an argument.
    if (is_null($field_name)) {
      $field_name = $this->randomMachineName();
    }
    // Create the appropriate field config entities.
    FieldStorageConfig::create([
      'field_name'  => $field_name,
      'entity_type' => 'node',
      'type'        => 'text',
    ])->save();
    FieldConfig::create([
      'field_name'   => $field_name,
      'entity_type'  => 'node',
      'bundle'       => 'article',
      'translatable' => TRUE,
    ])->save();
    // Make created fields visible on the entity add/edit forms.
    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $entity_form_display */
    $entity_form_display = \Drupal::entityTypeManager()
      ->getStorage('entity_form_display')
      ->load('node.article.default');
    $options = [
      'type'     => 'string_textfield',
      'settings' => [
        'fields[field_test][settings_edit_form][settings][size]' => 128,
      ],
    ];
    $entity_form_display->setComponent($field_name, $options)->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function randomMachineName($length = 8) {
    // Prevent capital letters being generated.
    return strtolower(parent::randomMachineName($length));
  }

  /**
   * Get database size.
   *
   * @return string
   *   Database size formatted.
   */
  private function getDatabaseSize() {
    $tables = $this->getDatabaseConnection()
      ->query('SHOW TABLE STATUS')
      ->fetchAll();

    $size = 0;
    if (!empty($tables) && is_array($tables)) {
      foreach ($tables as $table) {
        $size += (int) $table->Data_length;
        $size += (int) $table->Index_length;
      }
    }

    return self::formatBytes($size);
  }

  /**
   * Get memory usage converted(to KB, MB etc.).
   *
   * @return string
   *   Memory usage.
   */
  private function getMemoryUsage() {
    return self::formatBytes(memory_get_usage());
  }

  /**
   * Format bytes to MB.
   *
   * @param int $bytes
   *   Size in bytes.
   * @param int $precision
   *   Precision value. Defaults to 2.
   *
   * @return string
   *   Formatted size in MB.
   */
  private static function formatBytes($bytes, $precision = 2) {
    return number_format($bytes / 1048576, $precision);
  }

}
