<?php

namespace Drupal\ief_dnd_categories\Services;

class IefDndCategoriesPositionHandler {

  /**
   * @var array $categories
   *   Category data model used internally for processing entity rows & category order and associations.
   */
  protected $categories = [];

  /**
   * IefDndCategoriesPositionHandler constructor.
   * @param $taxonomyTerms array of stdClass
   *   Taxonomy term objects used to build categories.
   */
  public function __construct($taxonomyTerms) {

    foreach ($taxonomyTerms as $term) {
      $this->categories[$term->tid] = [
        'data' => $term->name,
        'weight' => $term->weight,
        'class' => [],
        'position' => NULL,
      ];
    }
    uasort($this->categories, function($categoryA, $categoryB) {
      return $categoryA['weight'] - $categoryB['weight'];
    });
  }

  public function getCategories() {
    return $this->categories;
  }

  /**
   * @return array $categoriesPositions
   *   Associative array of positions => category ids.
   */
  public function getCategoriesPositions() {
    $categoriesPositions = [];
    foreach ($this->categories as $tid => $categoryData) {
      if (!is_null($categoryData['position'])) {
        $categoriesPositions[$categoryData['position']] = $tid;
      }
    }
    return $categoriesPositions;
  }

  /**
   * @param array $entityFieldValues
   *   Entity values ordered by keyed positions as given from an entity relationship field.
   *
   * @return array $tableCategoriesPositions
   *   Categories associated by table indexes.
   */
  public function getTableCategoriesFromPosition($entityFieldValues) {

    $tableCategoriesPositions = [];
    $categoriesPositions = $this->getCategoriesPositions();

    // Starting with highest category position,
    krsort($categoriesPositions);
    foreach ($categoriesPositions as $position => $categoryId) {
      // Record all entity rows category above or equal to that position:
      for ($index = count($entityFieldValues) - 1; $index >= $position; $index--) {
        array_pop($entityFieldValues);
        $tableCategoriesPositions[$index] = $categoryId;
      }
    }

    // Remaining entity rows have no categories:
    foreach ($entityFieldValues as $index => $entity) {
      $tableCategoriesPositions[$index] = NULL;
    }

    return $tableCategoriesPositions;
  }

  /**
   *
   * @param array entity rows sorted on the drag & drop table.
   *
   * Updates categories positions from row entities datas.
   * @see ::renderTable
   */
  public function setCategoriesPositionsFromEntityRowsData($entityRows)
  {
    // We loop through entity rows from the top, assuming $entityRows is already sorted by weight:
    $position = 0;
    $categoryPointer = NULL;
    foreach ($entityRows as $key => $rowData) {
      if (isset($rowData['category-id'])) {
        $currentCategory = $rowData['category-id'];
        // When category threshold is detected, increment its position:
        if (!is_null($currentCategory) && $currentCategory != $categoryPointer) {
          $this->categories[$currentCategory]['position'] = $position;
          $categoryPointer = $currentCategory;
        }
        $position++;
      }
    }
  }

  /**
   * Updates category positions from hidden category input fields values.
   * The position value correspond to the number of table entities before it.
   *
   * @see js/inline-documents-form.js
   *
   * @param $formInputs
   *   Array of for input given from a FormState object.
   *   Contains a mapping between category-id and position in the table.
   */
  public function setCategoriesPositionsFromUserInput($formInputs)
  {
    foreach ($this->categories as $tid => $categoryData) {
      if (isset($formInputs['category-' . $tid])) {
        $categoryPosition = $formInputs['category-' . $tid];
        $this->categories[$tid]['position'] = $categoryPosition;
      }
    }
  }

  public static function getRelativeWeight($a, $b) {
    if (!empty($a['is_category'])) {
      if (is_null($a['weight'])) {
        return 1;
      }
      if ($a['weight'] == $b['weight']) {
        return -1;
      }
    }
    if (!empty($b['is_category'])) {
      if (is_null($b['weight'])) {
        return -1;
      }
      if ($a['weight'] == $b['weight']) {
        return 1;
      }
    }
    return $a['weight'] - $b['weight'];
  }

}
