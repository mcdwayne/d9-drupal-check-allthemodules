<?php

namespace Drupal\ief_dnd_categories\tests\Unit\Services;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\ief_dnd_categories\Services\IefDndCategoriesPositionHandler;
use Drupal\taxonomy\TermStorage;
use Drupal\Tests\UnitTestCase;

class IefDndCategoriesPositionHandlerTest extends UnitTestCase {

  public function setup() {

    $terms = [
      (object) [
        'name' => 'Category A',
        'weight' => 0,
        'tid' => 1
      ],
      (object)[
        'name' => 'Category B',
        'weight' => 1,
        'tid' => 2
      ],
      (object)[
        'name' => 'Category C',
        'weight' => 2,
        'tid' => 3
      ],
    ];

    // This object arguments are only used for form rendering and submits. Here we test sorting methods of the class.
    $this->iefDndCategoriesPositionsHandler = new IefDndCategoriesPositionHandler($terms);

  }

  /**
   * @test iefDndCategoriesPositionsHandler::setCategoriesPositionsFromUserInput
   */
  public function testCategoriesPositionsFromUserInput() {
    $userInput = [
      'category-1' => 7,
      'category-2' => 3,
      'category-3' => 0,
    ];
    $this->iefDndCategoriesPositionsHandler->setCategoriesPositionsFromUserInput($userInput);
    $categoryPosition = $this->iefDndCategoriesPositionsHandler->getCategoriesPositions();
    self::assertEquals(1, $categoryPosition[7]);
    self::assertEquals(2, $categoryPosition[3]);
    self::assertEquals(3, $categoryPosition[0]);
  }

  /**
   * @test iefDndCategoriesPositionsHandler::setCategoriesPositionsFromEntityRowsData
   */
  public function testCategoriesPositionsFromRowsData() {

    $rowsData = [
      ['category-id' => NULL],
      // Index 0, Category id 2
      ['category-id' => 2],
      ['category-id' => 2],
      // Index 2, Category id 3
      ['category-id' => 3],
      // Index 3, Category id 1
      ['category-id' => 1],
      // Index 4, Category id 5
      ['category-id' => 5],
    ];

    $this->iefDndCategoriesPositionsHandler->setCategoriesPositionsFromEntityRowsData($rowsData);
    $categoryPosition = $this->iefDndCategoriesPositionsHandler->getCategoriesPositions();

    self::assertEquals(2, $categoryPosition[0]);
    self::assertEquals(3, $categoryPosition[2]);
    self::assertEquals(1, $categoryPosition[3]);
    self::assertEquals(5, $categoryPosition[4]);

  }

  /**
   * @test iefDndCategoriesPositionsHandler::getRelativeWeight
   */
  public function testRelativeCategoryOrder() {
    $rowsData = [
      ['weight' => 0],
      // category1Position
      ['weight' => 1],
      ['weight' => 2],
      // category2Position
      ['weight' => 3],
    ];
    // Category 1:
    $categoryPosition = IefDndCategoriesPositionHandler::getRelativeWeight($rowsData[0], ['is_category' => TRUE, 'weight' => 1]);
    self::assertLessThan(0, $categoryPosition);
    $categoryPosition = IefDndCategoriesPositionHandler::getRelativeWeight($rowsData[1], ['is_category' => TRUE, 'weight' => 1]);
    self::assertGreaterThan(0, $categoryPosition);
    $categoryPosition = IefDndCategoriesPositionHandler::getRelativeWeight($rowsData[2], ['is_category' => TRUE, 'weight' => 1]);
    self::assertGreaterThan(0, $categoryPosition);
    // Category 2:
    $categoryPosition = IefDndCategoriesPositionHandler::getRelativeWeight(['is_category' => TRUE, 'weight' => 3], $rowsData[2]);
    self::assertGreaterThan(0, $categoryPosition);
    $categoryPosition = IefDndCategoriesPositionHandler::getRelativeWeight(['is_category' => TRUE, 'weight' => 3], $rowsData[3]);
    self::assertLessThan(0, $categoryPosition);
    // Empty Category is always last in the list:
    $categoryPosition = IefDndCategoriesPositionHandler::getRelativeWeight(['is_category' => TRUE, 'weight' => NULL], $rowsData[3]);
    self::assertGreaterThan(0, $categoryPosition);
    $categoryPosition = IefDndCategoriesPositionHandler::getRelativeWeight($rowsData[3], ['is_category' => TRUE, 'weight' => NULL]);
    self::assertLessThan(0, $categoryPosition);
  }

  /**
   * @test iefDndCategoriesPositionsHandler::getTableCategoriesFromPosition
   */
  public function testTableCategoriesFromPosition() {

    $tableFieldIndexes = [0 => NULL, 1 => NULL, 2 => NULL, 3 => NULL, 4 => NULL];

    $userInputs = [
      'category-3' => 1,
      'category-2' => 3,
      'category-1' => 5,
    ];

    $categoriesTerms = [

      // POSITION 0:
      // Nothing.

      // TID 3, Position 1 (entity row 1 to 2):
      (object)[
        'tid' => 3,
        'weight' => 1,
        'name' => 'Category C',
      ],

      // TID 2, Position 3 (entity row 3 to 4):
      (object)[
        'tid' => 2,
        'weight' => 2,
        'name' => 'Category B',
      ],

      // TID 1, Position 5 (no entity on this row):
      (object) [
        'tid' => 1,
        'weight' => 3,
        'name' => 'Category A',
      ],
    ];

    // This object arguments are only used for form rendering and submits. Here we test sorting methods of the class.
    $this->iefDndCategoriesPositionsHandler = new IefDndCategoriesPositionHandler($categoriesTerms);

    $this->iefDndCategoriesPositionsHandler->setCategoriesPositionsFromUserInput($userInputs);
    $tableCategories = $this->iefDndCategoriesPositionsHandler->getTableCategoriesFromPosition($tableFieldIndexes);
    self::assertEquals(NULL, $tableCategories[0]);
    self::assertEquals(3, $tableCategories[1]);
    self::assertEquals(3, $tableCategories[2]);
    self::assertEquals(2, $tableCategories[3]);
    self::assertEquals(2, $tableCategories[4]);
    self::assertArrayNotHasKey(5, $tableCategories);

  }

}
