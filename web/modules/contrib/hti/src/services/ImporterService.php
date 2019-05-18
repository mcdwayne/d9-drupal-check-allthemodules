<?php

namespace Drupal\hierarchical_taxonomy_importer\services;

use Drupal\taxonomy\Entity\Term;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Database\Connection;

/**
 * Class ImporterService.
 */
class ImporterService {

  const SAME_PARENT = 0;
  const DIFFERENT_PARENT = 1;
  const PREVIOUS_PARENT = -1;

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;
  protected $connection;

  /**
   * Constructs a new ImporterService object.
   */
  public function __construct(EntityTypeManager $entity_type_manager, Connection $connection) {
    $this->entityTypeManager = $entity_type_manager;
    $this->connection = $connection;
  }

  /**
   *
   * @param string $vid
   *   Vocabulary ID to import terms.
   * @param mixed $data
   *   Array structure with values to import as terms.
   * @param numeric $count
   *   Counter for total number of records checking to stop the execution.
   * @param numeric $row
   *   Current row number for CSV row.
   * @param numeric $pointer
   *   Current Column Pointer
   * @param mixed $parent
   *   Parent TID for term being created.
   * @param mixed $tag
   *   This is a flag used for checking if there is a record on next level,
   *   same level or on top level.
   * @return mixed
   */
  public function import($vid, $data, $count = 0, $row = 0, $pointer = 0, $parent = 0, $tag = 1) {
    // If all levels of a parents have been traversed and new parent has comeup for
    // import then reset the levels counter back to 0 for hierarhichal connect.
    if(!empty($data[$row][0])) {
      $pointer = 0;
      $parent = 0;
    }
    // If all rows have been traversed then exit.
    if($count > count($data)) {
      return;
    }

    // Returns the index of csv column has a value in a current record row.
    $pointer = $this->getIndexOfNonNullValues($data[$row]);
    // If pointer is null then return null.
    if(is_null($pointer)) {
      return;
    }
    // Current Column value.
    $term_name = $data[$row][$pointer];
    // Searching for existing term.
    // It finds pointer for levels and breaks upon finding it.
    // If not empty columns value then assing it's index to the current pointer
    // and break.
    // Assigning current offset where a record resides.
    $parent_term_name = "";
    // Parent Row for the current record being read.
    $parent_row = $this->getParentRow($data, $row, $pointer);
    // This checks for the parent row.
    if($parent_row >= 0) {
      // This is the parent term name of currently processed taxonomy term.
      $parent_term_name = $data[$parent_row][$pointer - 1];
      // If parent_term is not empty then fetch the parent term's information.
      if(!empty($parent_term_name) || is_null($parent_term_name)) {
        // Query : if there is any record already created in mapping table for the current record.
        $query = "Select tid from {hti_term_levels} WHERE name IN (:name) AND level =:level";
        // Creating the query with assigning the variables.
        $query_result = $this->connection->query($query, [':name' => $parent_term_name, ':level' => $pointer]);
        // Fetching the query result.
        $result = $query_result->fetch();
        // Getting the tid of term being created.
        $parent = !empty($result->tid) ? $result->tid : $parent;
      }
    }
    $parent = $this->updateActualParent($vid, $data, $row, $pointer, $term_name, $parent, $tag);
    // This will be used when current term is being added or generated on same level.
    $original_parent = $parent;
    // This works when system progress to the next level and finds values for that.
    if(!empty($data[$row + 1][$pointer + 1])) {
      return $this->import($vid, $data, $count + 1, $row + 1, $pointer + 1, $parent, $this::DIFFERENT_PARENT);
    }
    // This works when system progress to the same level for current parent.
    if(!empty($data[$row + 1][$pointer])) {
      return $this->import($vid, $data, $count + 1, $row + 1, $pointer, $original_parent, $this::SAME_PARENT);
    }
    // If none of the conditions matched above, then it traverses back by levelling backwards.
    return $this->import($vid, $data, $count + 1, $row + 1, $pointer - 1, $parent, $this::PREVIOUS_PARENT);
  }

  /**
   * This method returns the index on non-null values in Array.
   *
   * @param mixed $data
   *
   * @return mixed
   */
  public function getIndexOfNonNullValues($data) {
    // This gives the index of column in CSV that would be created as a new
    // taxonomy term.
    if(!empty($data) && !is_null($data)) {
      // Return the column after removing null or blanks values from a row.
      return !empty($data) ? array_shift(array_keys(array_diff($data, [" ", ""]))) : 0;
    }
    return 0;
  }

  /**
   * This method gives the index of parent row of a term being imported as a
   * taxonomy term.
   * 
   * @param mixed $data
   *   CSV Data as an array.
   * @param numeric $parent_row
   *   Taxonomy term's parent offset.
   * @param numeric $pointer
   *   Imported term's offset.
   * @return numeric
   *   This returns the parent offset after calculations.
   */
  protected function getParentRow($data, $parent_row, $pointer) {
    // Fetching parent row.
    while(empty($data[$parent_row][$pointer - 1]) && $parent_row > 0) {
      $parent_row = $parent_row - 1;
    }
    return $parent_row;
  }

  /**
   * This method adds a mapping of a term for managing parent child relationship
   * in a custom db table.
   * 
   * @param numeric $tid
   *   Term ID of taxonomy term.
   * @param string $term
   *   Term name
   * @param numeric $pointer
   *   Term's offset.
   * @param numeric $row
   *   CSV row's offset.
   * @param numeric $parent
   *   Parent TID of the term being imported.
   */
  protected function addLevelsMapping($tid, $term, $pointer, $row, $parent) {
    // Insert mapping of term created into a custom DB table.
    try {
      $this->connection->insert('hti_term_levels')
        ->fields(['tid', 'name', 'level', 'row', 'parent'])
        ->values([$tid, $term, $pointer, $row, $parent])
        ->execute();
    }
    catch(\Exception $e) {
      \Drupal::logger('hti')->error($e->getMessage());
      return;
    }
  }

  /**
   * This method adds a new taxonomy term.
   * 
   * @param string $vid
   *   Vocabulary ID.
   * @param string $term
   *   Term name
   * @param numeric $parent
   *   Parent Term ID.
   * @return numeric
   *   Newly create terms ID.
   */
  protected function createNewTerm($vid, $term, $parent) {
    try {
      // Adding a new term to the TAxonomy..
      $new_term = Term::create([
         'name' => $term,
         'parent' => $parent,
         'vid' => $vid,
      ]);
      $new_term->save();
    }
    catch(\Exception $ex) {
      \Drupal::logger('hti')->error($ex->getMessage());
      return;
    }
    // Newly entered Term ID.
    return $new_term->id();
  }

  /**
   * This method calculates upon CSV data and finds out actual parent.
   * 
   * @param string $vid
   *   Vocabulary ID.
   * @param mixed $data
   *   CSV Data array.
   * @param numeric $row
   *   Row offset in CSV Data.
   * @param numeric $pointer
   *   Column offset of CSV Data.
   * @param string $term_name
   *   current taxonomy term name.
   * @param numeric $parent
   *   Parent ID 
   * @param numeric $tag
   *   This tells that if next term is to import is on same parent level, new parent
   *   level or on nested levels backward.
   * @return numeric
   *   Parent Term ID.
   */
  protected function updateActualParent($vid, $data, $row, $pointer, $term_name, $parent, $tag) {
    // If term being created is not on same and next level but belongs to other 
    // parent level in hierarchy tree.
    if($tag == $this::PREVIOUS_PARENT) {
      $iterator = $row - 1;
      // Traverse the row back till you find the parent of it.
      while(empty($data[$iterator][$pointer - 1]) && $iterator > 0) {
        $iterator--;
      }
      // Getting result for parent.
      $query = $this->connection->query(
        "SELECT * FROM {hti_term_levels} WHERE name=:name AND level=:level AND row = :row", [
       ':name' => $data[$iterator][$pointer - 1],
       ':level' => ($pointer - 1),
       ':row' => $iterator,
      ]);
      // Fetch results of database query.
      $parent_term = $query->fetch();
      // Assign parent term tid.
      if(!empty($parent_term->tid)) {
        $parent = $parent_term->tid;
      }
      // Add a new term.
      $new_term_id = $this->createNewTerm($vid, $term_name, $parent);
      // Add mapping of levels & rows to database table.
      $this->addLevelsMapping($new_term_id, $term_name, $pointer, $row, $parent);
      // Assing newly created term TID as Parent ID for next record to import.
      $parent = $new_term_id;
    }
    // IF next term to import is at child level.
    elseif($tag == $this::DIFFERENT_PARENT) {
      $new_term_id = $this->createNewTerm($vid, $term_name, $parent);
      // Add mapping of levels & rows to database table.
      $this->addLevelsMapping($new_term_id, $term_name, $pointer, $row, $parent);
      // Tagging if next level of record exists.
      if($tag == $this::DIFFERENT_PARENT) {
        $parent = $new_term_id;
      }
    }
    // IF next term to import is at child level.
    elseif($tag == $this::SAME_PARENT) {
      $new_term_id = $this->createNewTerm($vid, $term_name, $parent);
      // Add mapping of levels & rows to database table.
      $this->addLevelsMapping($new_term_id, $term_name, $pointer, $row, $parent);
      // Tagging if next level of record exists.
    }
    return $parent;
  }

}


