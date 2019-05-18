<?php

namespace Drupal\lupus_taxonomy_import\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\file\FileInterface;

/**
 * Imports taxonomy hierarchies from csv file.
 */
class Importer {

  use StringTranslationTrait;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Do not allow to set these term fields via importer.
   *
   * @var string[]
   */
  protected $protectedFields = [
    'changed',
    'parent',
    'tid',
    'uuid',
    'vid',
    'metatag',
  ];

  /**
   * Constructor.
   *
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   */
  public function __construct(FileSystemInterface $file_system, EntityTypeManagerInterface $entity_type_manager) {
    $this->fileSystem = $file_system;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Imports taxonomy from csv.
   *
   * @param \Drupal\file\FileInterface $file
   *   The csv file.
   * @param string $vocabulary_id
   *   Vocabulary id.
   * @param bool $purge_vocabulary
   *   (optional) Remove all existing terms from the vocabulary.
   *
   * @return bool
   */
  public function importFromCsv(FileInterface $file, $vocabulary_id, $purge_vocabulary = FALSE) {
    $filepath = $this->fileSystem->realpath($file->getFileUri());

    if (!is_readable($filepath)) {
      return FALSE;
    }

    $rows = array_map('str_getcsv', file($filepath));
    $header = array_shift($rows);

    // Remove empty lines.
    foreach ($rows as $key => $row) {
      if (count(array_filter($row)) == 0) {
        unset($rows[$key]);
      }
    }

    // Reset keys.
    $rows = array_map('array_values', $rows);

    // Set header as keys in each row.
    array_walk($rows, function (&$row) use ($header) {
      $row = array_combine($header, $row);
    });

    $mapped_rows = [];
    $this->mapRows($mapped_rows, $rows);

    $id_map = [];

    /** @var \Drupal\taxonomy\TermStorage $term_storage */
    $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');

    if ($purge_vocabulary) {
      $current_terms = $term_storage->loadByProperties(['vid' => $vocabulary_id]);
      foreach ($current_terms as $current_term) {
        $current_term->delete();
      }
    }

    foreach ($mapped_rows as $row) {
      $parent_id = isset($id_map[$row['parent_id']]) ? $id_map[$row['parent_id']] : 0;

      /** @var \Drupal\taxonomy\Entity\Term $term */
      $term = $term_storage->create([
        'parent' => $parent_id,
        'name' => $row['term'],
        'vid' => $vocabulary_id,
      ]);

      if (!empty($row['fields'])) {
        foreach ($row['fields'] as $fieldname => $value) {
          if ($term->hasField($fieldname) && !in_array($fieldname, $this->protectedFields)) {
            $field = $term->get($fieldname);
            // Skip entity references.
            if ($field instanceof EntityReferenceFieldItemList) {
              continue;
            }
            $field->setValue($value);
          }
        }
      }

      $term->save();
      $id_map[$row['term_id']] = $term->id();
    }

    return TRUE;
  }

  /**
   * Validates the import file & returns an array of validation errors.
   *
   * @param \Drupal\file\FileInterface $file
   *   The data file.
   *
   * @return array
   */
  public function validate(FileInterface $file) {
    $errors = [];

    if (!$file) {
      $errors[] = $this->t('No CSV file.');
      return $errors;
    }

    $filepath = $this->fileSystem->realpath($file->getFileUri());

    if (!is_readable($filepath)) {
      $errors[] = $this->t('File not readable');
      return $errors;
    }

    $rows = array_map('str_getcsv', file($filepath));

    $header = array_shift($rows);
    $header_error = NULL;
    $term_keys = [];

    foreach ($header as $key => $value) {
      if (is_numeric($value)) {
        $term_keys[] = $value;
        if ($key != $value) {
          $params = [
            '@expected' => implode(', ', range(0, $key)),
            '@actual' => implode(', ', $term_keys),
          ];
          $header_error = $this->t('Header term keys are in the wrong order, expected: @expected, actual: @actual', $params);
        }
      }

      if (in_array($value, $this->protectedFields)) {
        $errors[] = $this->t('Field %field is a protected field and can not be set via the importer.', ['%field' => $value]);
      }
    }

    if ($header_error) {
      $errors[] = $header_error;
    }

    $term_count = [];

    $first_row = reset($rows);
    if (empty(reset($first_row))) {
      $errors[] = $this->t('First row does not begin with a term.');
    }

    foreach ($rows as $id => $row) {
      // On a single row, there can not be multiple terms.
      if (!$header_error && count(array_filter(array_intersect_key($row, $term_keys))) > 1) {
        $errors[] = $this->t('Multiple entries on a single row. Row :id', [':id' => $id + 2]);
      }

      $item = $this->getTermItem($row);
      if (!empty($item)) {
        $term = $item['term'];
        if (!isset($term_count[$term])) {
          $term_count[$term] = 0;
        }

        $term_count[$term]++;
      }
    }

    foreach ($term_count as $term => $count) {
      if ($count > 1) {
        $errors[] = $this->t('Multiple terms not allowed, `:term` was used :count times', [':term' => $term, ':count' => $count]);
      }
    }

    return $errors;
  }

  /**
   * Map rows to reflect the {parent}, {term} pair which is needed by taxonomy.
   *
   * Takes an array of rows, where the index of the term at the current row
   * indicates the hierarchical level and maps it into the output format.
   *
   * @param array $output
   *   The output array the for the mapped values.
   * @param array $rows
   *   The rows from the input file.
   * @param string|int $parent_id
   *   The parent item id.
   * @param int $level
   *   The current level which is being processed.
   */
  protected function mapRows(array &$output, array &$rows, $parent_id = 0, $level = 0) {
    while (list($key, $row) = each($rows)) {
      reset($rows);
      $item = $this->getTermItem($row);
      $current = $item['term'];
      $current_id = md5($parent_id . print_r($row, 1));

      if ($level == $item['level']) {
        $next = next($rows);
        $next_item = $this->getTermItem($next);

        $output[] = [
          'term_id' => $current_id,
          'parent_id' => $parent_id,
          'term' => $current,
          'fields' => $item['fields'],
        ];

        unset($rows[$key]);

        if (!empty($next_item) && $next_item['level'] > $level) {
          $this->mapRows($output, $rows, $current_id, $level + 1);
        }
      }
      elseif ($item['level'] < $level) {
        return;
      }
    }
  }

  /**
   * Helper method to get the level & value of the term for a row.
   *
   * @param array|bool $row
   *   The row.
   *
   * @return array
   *   term: The term name.
   *   level: The level the item is on.
   *   fields: Array with fieldnames as keys & field-values as values.
   */
  protected function getTermItem($row) {
    if (!is_array($row)) {
      return [];
    }

    $level = NULL;
    $term = NULL;
    $fields = [];

    foreach ($row as $key => $value) {
      // Terms have numeric keys starting from 0.
      if (is_numeric($key) && !$term && !empty($value)) {
        $term = $value;
        $level = $key;
      }

      // Non-numeric keys are fieldnames.
      if (!is_numeric($key)) {
        $fields[$key] = $value;
      }
    }

    if ($term) {
      return [
        'term' => $term,
        'level' => $level,
        'fields' => $fields,
      ];
    }

    return [];
  }

}
