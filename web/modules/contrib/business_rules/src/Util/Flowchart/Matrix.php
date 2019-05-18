<?php

namespace Drupal\business_rules\Util\Flowchart;

use Drupal\business_rules\Entity\BusinessRule;
use Drupal\Core\Entity\EntityInterface;

/**
 * The matrix to handle the Business Rule flowchart.
 *
 * @package Drupal\business_rules\Util\Flowchart
 */
class Matrix {

  /**
   * The number of root items if root is instance of BusinessRule.
   *
   * @var int
   */
  protected $numberOfRootItems = 0;

  /**
   * The internal matrix.
   *
   * @var array
   */
  private $matrix = [];

  /**
   * The root element.
   *
   * @var \Drupal\business_rules\Util\Flowchart\Element
   */
  private $rootElement;

  /**
   * Matrix constructor.
   */
  public function __construct() {
    for ($y = 0; $y <= 100; $y++) {
      for ($x = 0; $x <= 100; $x++) {
        $this->matrix[$x][$y] = [
          'x'       => $x * 170 + 10,
          'y'       => $y * 90 + 10,
          'index_x' => $x,
          'index_y' => $y,
          'element' => NULL,
        ];
      }
    }
  }

  /**
   * Get all cell with elements from the matrix.
   *
   * @return array
   *   The elements in cells.
   */
  public function getAllCellWithElements() {
    $cells = [];
    for ($y = 0; $y <= 100; $y++) {
      for ($x = 0; $x <= 100; $x++) {
        if (is_object($this->matrix[$x][$y]['element'])) {
          $cells[] = $this->matrix[$x][$y];
        }
      }
    }

    return $cells;
  }

  /**
   * Get the element above in the matrix.
   *
   * @param \Drupal\business_rules\Util\Flowchart\Element $element
   *   The element.
   *
   * @return null|\Drupal\business_rules\Util\Flowchart\Element
   *   The element.
   */
  public function getElementAbove(Element $element) {
    $cell = $this->getCellByElementUuid($element->getUuid());

    $x = $cell['index_x'];
    $y = $cell['index_y'];

    // Check to avoid offset.
    if ($y === 0) {
      return NULL;
    }

    $cell_above = $this->matrix[$x][$y - 1];

    if ($cell_above['element'] instanceof Element) {
      return $cell_above['element'];
    }
    else {
      return NULL;
    }
  }

  /**
   * Get one matrix cell by Element UUID.
   *
   * @param string $uuid
   *   The element uuid.
   *
   * @return null|array
   *   The matrix cell or NULL if not found.
   */
  public function getCellByElementUuid($uuid) {
    for ($y = 0; $y <= 100; $y++) {
      for ($x = 0; $x <= 100; $x++) {
        $element = $this->matrix[$x][$y]['element'];
        if ($element instanceof Element) {
          if ($element->getUuid() == $uuid) {
            return $this->matrix[$x][$y];
          }
        }
      }
    }

    return NULL;
  }

  /**
   * Get matrix element by item.
   *
   * @param \Drupal\Core\Entity\EntityInterface $item
   *   The item.
   *
   * @return \Drupal\business_rules\Util\Flowchart\Element
   *   The Element.
   */
  public function getElementByItem(EntityInterface $item) {
    for ($y = 0; $y <= 100; $y++) {
      for ($x = 0; $x <= 100; $x++) {
        if (is_object($this->matrix[$x][$y]['element'])) {
          if ($this->matrix[$x][$y]['element']->getItem() === $item) {
            return $this->matrix[$x][$y]['element'];
          }
        }
      }
    }
  }

  /**
   * Check if one cell is with no element.
   *
   * @param int $x
   *   The X position.
   * @param int $y
   *   The Y position.
   *
   * @return bool
   *   TRUE|FALSE.
   */
  public function isCellEmpty($x, $y) {
    if (is_null($this->matrix[$x][$y]['element'])) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Put an element inside the matrix.
   *
   * @param \Drupal\business_rules\Util\Flowchart\Element $element
   *   The element.
   * @param \Drupal\business_rules\Util\Flowchart\Element $parent
   *   The parent element.
   * @param string $direction
   *   The direction: bottom|right|left|bottom-right|bottom-left.
   * @param int $off_x
   *   The X off set.
   * @param int $off_y
   *   The Y off set.
   *
   * @throws \Exception
   */
  public function putElement(Element $element, Element $parent, $direction, $off_x = 0, $off_y = 0) {
    if ($parent->getItem() === $this->getRootElement()->getItem()) {
      $parent_cell = $this->getCellByElementUuid($this->getRootElement()
        ->getUuid());
    }
    else {
      $parent_cell = $this->getCellByElementUuid($parent->getUuid());
    }

    if (is_null($parent_cell)) {
      throw new \Exception('Could not load parent');
    }

    $parent_x = $parent_cell['index_x'];
    $parent_y = $parent_cell['index_y'];

    // Only root's children can be at same X position as root.
    // After prepare all root elements, other items can be at same root's X
    // position on X,Y+1 cell.
    $root     = $this->getRootElement();
    $root_cel = $this->getCellByElementUuid($root->getUuid());
    $root_x   = $root_cel['index_x'];

    switch ($direction) {
      case 'bottom':
        if ($this->numberOfRootItems >= $parent_y && $root_x === $parent_x && $element->getParent() !== $root) {
          $off_x++;
        }
        if (is_null($this->matrix[$parent_x + $off_x][$parent_y + 1 + $off_y]['element'])) {
          $this->putElementInPosition($element, $parent_x + $off_x, $parent_y + 1 + $off_y);
        }
        else {
          $this->putElement($element, $parent, $direction, $off_x, $off_y + 1);
        }
        break;

      case 'right':
        if ($this->numberOfRootItems >= $parent_y && $root_x === ($parent_x + 1 + $off_x)) {
          $off_x++;
        }
        if (is_null($this->matrix[$parent_x + 1 + $off_x][$parent_y + $off_y]['element'])) {
          $this->putElementInPosition($element, $parent_x + 1 + $off_x, $parent_y + $off_y);
        }
        else {
          $this->putElement($element, $parent, $direction, $off_x, $off_y + 1);
        }
        break;

      case 'left':
        if ($this->numberOfRootItems >= $parent_y && $root_x === ($parent_x - 1 + $off_x)) {
          $off_x--;
        }
        if (($parent_x - 1 + $off_x) < 0 || is_null($this->matrix[$parent_x - 1 + $off_x][$parent_y + $off_y]['element'])) {
          $this->putElementInPosition($element, $parent_x - 1 + $off_x, $parent_y + $off_y);
        }
        else {
          $this->putElement($element, $parent, $direction, $off_x, $off_y + 1);
        }
        break;

      case 'bottom-right':
        if ($this->numberOfRootItems >= $parent_y && $root_x === ($parent_x + 1 + $off_x)) {
          $off_x++;
        }
        if (is_null($this->matrix[$parent_x + 1 + $off_x][$parent_y + 1 + $off_y]['element'])) {
          $this->putElementInPosition($element, $parent_x + 1 + $off_x, $parent_y + 1 + $off_y);
        }
        else {
          $this->putElement($element, $parent, $direction, $off_x, $off_y + 1);
        }
        break;

      case 'bottom-left':
        if ($this->numberOfRootItems >= $parent_y && $root_x === ($parent_x - 1 + $off_x)) {
          $off_x--;
        }
        if (($parent_x - 1 + $off_x) < 0 || is_null($this->matrix[$parent_x - 1 + $off_x][$parent_y + 1 + $off_y]['element'])) {
          $this->putElementInPosition($element, $parent_x - 1 + $off_x, $parent_y + 1 + $off_y);
        }
        else {
          $this->putElement($element, $parent, $direction, $off_x, $off_y + 1);
        }
        break;
    }

  }

  /**
   * Get the root element.
   *
   * @return \Drupal\business_rules\Util\Flowchart\Element
   *   The root element.
   */
  public function getRootElement() {
    return $this->rootElement;
  }

  /**
   * Put an element at one position in the matrix.
   *
   * @param \Drupal\business_rules\Util\Flowchart\Element $element
   *   The element.
   * @param int $x
   *   The X position.
   * @param int $y
   *   The Y position.
   *
   * @throws \Exception
   */
  private function putElementInPosition(Element $element, $x, $y) {
    if ($x < 0) {
      $this->shitAlToRight(-$x);
      $x = 0;
    }

    if (is_null($this->matrix[$x][$y]['element'])) {
      $this->matrix[$x][$y]['element'] = $element;
    }
    else {
      throw new \Exception("The position $x, $y is not empty.");
    }
  }

  /**
   * Shift all elements to right.
   *
   * @param int $distance
   *   The distance to shift.
   */
  private function shitAlToRight($distance = 1) {
    for ($y = 0; $y <= 100; $y++) {
      for ($x = 100; $x >= 0; $x--) {
        $cell = $this->matrix[$x][$y];
        if (!is_null($cell['element'])) {
          $this->putElementInPosition($cell['element'], $x + $distance, $y);
          $this->matrix[$x][$y]['element'] = NULL;
        }
      }
    }
  }

  /**
   * Put the root element at the matrix.
   *
   * @param \Drupal\business_rules\Util\Flowchart\Element $element
   *   The element.
   */
  public function putRootElement(Element $element) {
    $this->putElementInPosition($element, 0, 0);
    $this->rootElement = $element;

    if ($element->getItem() instanceof BusinessRule) {
      $this->numberOfRootItems = count($element->getItem()->getItems());
    }
  }

  /**
   * Shift cells that contains one element to bottom, left or right.
   *
   * @param \Drupal\business_rules\Util\Flowchart\Element $element
   *   The element.
   * @param string $direction
   *   The direction: bottom|left|right.
   * @param int $distance
   *   The distance to shift.
   */
  public function shift(Element $element, $direction, $distance = 1) {
    switch ($direction) {
      case 'bottom':
        $cells = $this->getBottomCells($element);
        $cells = array_reverse($cells);
        foreach ($cells as $cell) {
          $x       = $cell['index_x'];
          $y       = $cell['index_y'];
          $element = $cell['element'];

          $this->putElementInPosition($element, $x, $y + $distance);
          $this->matrix[$x][$y]['element'] = NULL;
        }
        break;

      case 'right':
        $cells = $this->getRightCells($element);
        $cells = array_reverse($cells);
        foreach ($cells as $cell) {
          $x       = $cell['index_x'];
          $y       = $cell['index_y'];
          $element = $cell['element'];

          $this->putElementInPosition($element, $x + $distance, $y);
          $this->matrix[$x][$y]['element'] = NULL;
        }
        break;

      case 'left':
        $cells = $this->getLeftCells($element);
        foreach ($cells as $cell) {
          $x       = $cell['index_x'];
          $y       = $cell['index_y'];
          $element = $cell['element'];

          $this->putElementInPosition($element, $x - $distance, $y);
          $this->matrix[$x][$y]['element'] = NULL;
        }
        break;
    }
  }

  /**
   * Get all not empty cells at bottom of one element.
   *
   * @param \Drupal\business_rules\Util\Flowchart\Element $element
   *   The element.
   *
   * @return array
   *   Array of cells
   */
  private function getBottomCells(Element $element) {
    $cell       = $this->getCellByElementUuid($element->getUuid());
    $index_x    = $cell['index_x'];
    $index_y    = $cell['index_y'];
    $elements[] = $cell;

    for ($y = $index_y; $y < 100; $y++) {
      if (!is_null($this->matrix[$index_x][$y + 1])) {
        $elements[] = $this->matrix[$index_x][$y + 1];
      }
      else {
        return $elements;
      }
    }

    return $elements;
  }

  /**
   * Get all not empty cells at right of one element.
   *
   * @param \Drupal\business_rules\Util\Flowchart\Element $element
   *   The element.
   *
   * @return array
   *   Array of cells
   */
  private function getRightCells(Element $element) {
    $cell       = $this->getCellByElementUuid($element->getUuid());
    $index_x    = $cell['index_x'];
    $index_y    = $cell['index_y'];
    $elements[] = $cell;

    for ($x = $index_x; $x < 100; $x++) {
      if (!is_null($this->matrix[$x + 1])) {
        $elements[] = $this->matrix[$x + 1][$index_y];
      }
      else {
        return $elements;
      }
    }

    return $elements;
  }

  /**
   * Get all not empty cells at left of one element.
   *
   * @param \Drupal\business_rules\Util\Flowchart\Element $element
   *   The element.
   *
   * @return array
   *   Array of cells
   */
  private function getLeftCells(Element $element) {
    $cell     = $this->getCellByElementUuid($element->getUuid());
    $index_x  = $cell['index_x'];
    $index_y  = $cell['index_y'];
    $elements = [];

    for ($x = $index_x; $x >= 0; $x--) {
      if (!is_null($this->matrix[$x - 1])) {
        $elements[] = $this->matrix[$x - 1][$index_y];
      }
      else {
        return $elements;
      }
    }

    return $elements;
  }

  /**
   * Check if the cell at right from the element is empty.
   *
   * @param \Drupal\business_rules\Util\Flowchart\Element $element
   *   The element.
   *
   * @return bool
   *   TRUE|FALSE.
   */
  public function rightCellIsEmpty(Element $element) {
    $cell    = $this->getCellByElementUuid($element->getUuid());
    $index_x = $cell['index_x'];
    $index_y = $cell['index_y'];

    $right_cell = $this->getCellByPosition($index_x + 1, $index_y);
    if (empty($right_cell['element'])) {
      $empty = TRUE;
    }
    else {
      $empty = FALSE;
    }

    return $empty;
  }

  /**
   * Get cell by position.
   *
   * @param int $x
   *   The X position.
   * @param int $y
   *   The Y position.
   *
   * @return array
   *   The cell
   */
  public function getCellByPosition($x, $y) {
    return $this->matrix[$x][$y];
  }

  /**
   * Check if the cell at left from the element is empty.
   *
   * @param \Drupal\business_rules\Util\Flowchart\Element $element
   *   The element.
   *
   * @return bool
   *   TRUE|FALSE.
   */
  public function leftCellIsEmpty(Element $element) {
    $cell    = $this->getCellByElementUuid($element->getUuid());
    $index_x = $cell['index_x'];
    $index_y = $cell['index_y'];

    if ($index_x == 0) {
      return TRUE;
    }

    $left_cell = $this->getCellByPosition($index_x - 1, $index_y);
    if (empty($left_cell['element'])) {
      $empty = TRUE;
    }
    else {
      $empty = FALSE;
    }

    return $empty;
  }

}
