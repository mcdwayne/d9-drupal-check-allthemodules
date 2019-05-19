<?php

/**
 * @file
 * Contains Excel class.
 */

namespace Drupal\wwaf_import\Plugin\migrate\source;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\file\Entity\File;
use Drupal\wwaf_import\ExcelFileObject;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\migrate\Row;

/**
 * Source for Excel.
 *
 * @MigrateSource(
 *   id = "wwaf_excel",
 *   source_module = "wwaf_import"
 * )
 */
class wwafExcel extends SourcePluginBase implements ContainerFactoryPluginInterface {

  /**
   * Import file.
   *
   * @var \Drupal\file\Entity\File
   */
  protected $file;

  /**
   * List of available source fields.
   *
   * Keys are the field machine names as used in field mappings, values are
   * descriptions.
   *
   * @var array
   */
  protected $fields = [];

  /**
   * List of key fields, as indexes.
   *
   * @var array
   */
  protected $keys = [];

  /**
   * Contains the configuration object factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    MigrationInterface $migration,
    ConfigFactoryInterface $config,
    FileSystemInterface $file_system,
    LanguageManagerInterface $language_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
    $this->configFactory = $config;
    $this->fileSystem = $file_system;
    $this->languageManager = $language_manager;

    // Key field(s) are required.
    if (empty($this->configuration['keys'])) {
      throw new MigrateException('You must declare "keys" as a unique array of fields in your source settings.');
    }

    $fid = $this->configFactory->get('wwaf_import.settings')->get('import_file');
    if (empty($fid)) {
      throw new MigrateException('Upload import file at first.');
    }
    $this->file = File::load($fid);
    $this->configuration['pos_language'] = $this->getLangCodeFromFilename();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
    MigrationInterface $migration = NULL
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('config.factory'),
      $container->get('file_system'),
      $container->get('language_manager')
    );
  }

  /**
   * Return a string representing the source query.
   *
   * @return string
   *   The file path.
   */
  public function __toString() {
    return $this->configuration['plugin'];
  }

  /**
   * {@inheritdoc}
   */
  public function initializeIterator() {
    if (!empty($this->file)) {
      $path = $this->fileSystem->realpath($this->file->getFileUri());
      return new ExcelFileObject($path);
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids = [];
    foreach ($this->configuration['keys'] as $key) {
      $ids[$key]['type'] = 'string';
    }

    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return $this->getIterator()->getColumnNames();
  }

  /**
   * Get file language from the import file name.
   *
   * @return string
   *   The langcode.
   */
  public function getLangCodeFromFilename() {
    // Default language.
    $lang_code = $this->languageManager->getDefaultLanguage()->getId();
    if (!empty($this->file)) {
      $lang_code = strtolower(substr(basename($this->file->getFileUri(), '.csv'), '-2'));
    }
    $languages = array_keys($this->languageManager->getLanguages());
    if (in_array($lang_code, $languages)) {
      return $lang_code;
    }

    return $this->languageManager->getDefaultLanguage()->getId();
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $source_values = $row->getSource();

//    $category_columns = array_filter(array_keys($source_values), function ($item) {
//      return strpos($item, 'pos_cat_') === 0;
//    });
//
//    // Put all categories together.
//    $pos_categories = [];
//    foreach ($category_columns as $cat_column) {
//      static::extractCategories($pos_categories, $source_values, $cat_column);
//    }
//    $row->setSourceProperty('pos_categories', $pos_categories);

    return parent::prepareRow($row);
  }

  /**
   * Extract categories from a POS.
   *
   * @param array|mixed $pos_categories
   *   The list of POS categories.
   * @param array|mixed $source_values
   *   The list of source values.
   * @param string $cat_column
   *   The category column.
   */
  public function extractCategories(&$pos_categories, $source_values, $cat_column) {
    $parent_category_name = strtolower(str_replace('_', ' ', substr($cat_column, 8)));
    $parent_category_name_humanized = ucfirst(trim($parent_category_name));
    if (!empty($source_values[$cat_column])) {
      $categories = explode('|', $source_values[$cat_column]);
      foreach ($categories as $category) {
        $pos_categories[] = [
          'parent_category' => $parent_category_name_humanized,
          'value' => $category,
        ];
      }
    }
    else {
      $pos_categories = [];
    }
  }

  /**
   * Get file name of the instance's file.
   *
   * @return string
   *   The file name.
   */
  public function getFileName() {
    if (!$this->file) {
      return '';
    }
    return $this->file->getFilename();
  }

}
