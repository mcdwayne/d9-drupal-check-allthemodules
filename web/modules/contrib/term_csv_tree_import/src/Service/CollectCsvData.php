<?php

namespace Drupal\term_csv_tree_import\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Service for importing terms.
 */
class CollectCsvData implements CollectCsvDataInterface {

  use StringTranslationTrait;

  /**
   * Column names and prefixes.
   *
   * @var array
   */
  const COLUMNS = ['Parent', 'Child', 'Custom'];

  /**
   * Error message.
   *
   * @var string
   */
  protected $message;

  /**
   * Array of data.
   *
   * @var array
   */
  protected $data;

  /**
   * Vocabulary.
   *
   * @var string
   */
  protected $vocabulary;

  /**
   * Entity Type Manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Load data to array and create term.
   *
   * @param string $csv_file_path
   *   Csv filename.
   * @param string $vocabulary_id
   *   Vocabulary where to create term.
   *
   * @return mixed
   *   Number of terms processed.
   */
  public function loadData($csv_file_path, $vocabulary_id) {
    // Create an associative array.
    if ($this->checkNumberOfColumns($csv_file_path)) {
      // Create term.
      $this->vocabulary = $vocabulary_id;
      $this->columns = self::COLUMNS;
      $processed = $this->createTerms();
      $this->message = 'Imported ' . $processed . ' terms.';
      return $this->message;
    }
    else {
      return $this->message;
    }
  }

  /**
   * Check number of columns should be same.
   *
   * @param string $file
   *   File path.
   *
   * @return bool
   *   True if all rows have same number of columns else false.
   */
  public function checkNumberOfColumns($file) {
    $header = '';
    $no_of_cols = 0;
    if (($handle = fopen($file, "r")) !== FALSE) {
      while (($row = fgetcsv($handle)) !== FALSE) {
        if (!$header) {
          $no_of_cols = count($row);
          $header = $row;
        }
        else {
          if (count($row) == $no_of_cols) {
            $this->data[] = array_combine($header, $row);
          }
          else {
            $this->message = 'Number of columns of all the rows are not same.';
            return FALSE;
          }
        }
      }
      if (count($this->data) > 0) {
        return TRUE;
      }
      $this->message = "Insufficient number of rows to import.";
      return FALSE;
    }
    else {
      $this->message = 'File Could not open.';
      return FALSE;
    }
  }

  /**
   * Create terms and sub terms with custom fields if any.
   */
  private function createTerms() {
    $operations = [];
    foreach ($this->data as $rows) {
      $operations[] = [
        get_class() . '::createTermsBatch',
        [$this->vocabulary, $rows],
      ];
    }
    if ($operations) {
      $batch = [
        'title' => $this->t('Importing CSV Terms'),
        'operations' => $operations,
      ];

      batch_set($batch);
    }
  }

  /**
   * Create terms by batch caller.
   *
   * @param int $vocabulary_id
   *   Taxonomy Vocab.
   * @param array $rows
   *   Rows of operations.
   */
  public static function createTermsBatch($vocabulary_id, array $rows) {
    $collectCsvData = \Drupal::service('term_csv_tree_import.collectCsvData');
    $collectCsvData->vocabulary = $vocabulary_id;
    $collectCsvData->createTerm($rows);
  }

  /**
   * Create Term and subterms with custom fields if any.
   *
   * @param array $rows
   *   A row of data.
   *
   * @return mixed
   *   number of data processed.
   */
  private function createTerm(array $rows) {
    $processed = 0;
    $prev = '';
    foreach ($rows as $key => $element) {
      // Get column name.
      $col_name = $this->getColumnName($key);
      switch ($col_name) {
        case 'Parent':
          $term = $this->addTerm($element);
          if ($term) {
            $term->save();
          }
          $prev = $element;
          $processed++;
          break;

        case 'Child':
          $term = $this->addTerm($element);
          if ($term) {
            $previous_term = $this->previousTerm($prev);
            if ($previous_term) {
              $term->parent = ['target_id' => $previous_term->id()];
            }
            $term->save();
          }
          $prev = $element;
          break;

        case 'Custom':
          $previous_term = $this->previousTerm($prev);
          if ($previous_term) {
            $term = $this->entityTypeManager->getStorage('taxonomy_term')
              ->load($previous_term->id());
            if (!empty($term) && !empty($element)) {
              // Get field name.
              $field = substr($key, strpos($key, '_') + 1);
              $term->set($field, $element);
              $term->save();
            }
          }
          break;
      }
    }
    return $processed;
  }

  /**
   * Get column name from a given element.
   *
   * @param string $element
   *   Csv element.
   *
   * @return string
   *   Column name.
   */
  public function getColumnName($element) {
    foreach (self::COLUMNS as $column => $value) {
      $pos = strpos($element, $value);
      if ($pos !== FALSE) {
        $column_name = substr($element, 0, strlen($value));
        return $column_name;
      }
    }
  }

  /**
   * Add term.
   *
   * @param string $ele
   *   Term to add.
   *
   * @return object
   *   Term object to save.
   */
  public function addTerm($ele) {
    $term_existing = taxonomy_term_load_multiple_by_name($ele, $this->vocabulary);
    if (!empty(trim($ele)) && count($term_existing) == 0) {
      // $new_term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->create([
      $new_term = $this->entityTypeManager->getStorage('taxonomy_term')
        ->create([
          'name' => $ele,
          'vid' => $this->vocabulary,
        ]);
      return $new_term;
    }
  }

  /**
   * Find term.
   *
   * @param string $name
   *   Term to find.
   *
   * @return array
   *   Array of matching term object.
   */
  private function previousTerm($name) {
    $previous_terms = taxonomy_term_load_multiple_by_name($name, $this->vocabulary);
    return array_shift($previous_terms);
  }

}
